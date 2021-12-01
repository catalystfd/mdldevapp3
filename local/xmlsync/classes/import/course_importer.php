<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * XML course import task
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xmlsync\import;
defined('MOODLE_INTERNAL') || die();


class course_importer extends base_importer {
    const COURSEIMPORT_FILENAME = 'moodle_crs.xml';

    /**
     * Import count from last import, if any.
     * Set during task initialisation.
     * @var int|null
     */
    public $lastimportcount = null;

    /**
     * Source timestamp from last import, if any.
     * Set during task initialisation.
     * @var int|null
     */
    public $lastsourcetimestamp = null;

    /**
     * Mapping from incoming XML field names to database column names.
     * Note: ACTION is handled separately.
     */
    public $rowmapping = array(
        'COURSE_IDNUMBER'   => 'course_idnumber',
        'COURSE_FULLNAME'   => 'course_fullname',
        'COURSE_SHORTNAME'  => 'course_shortname',
        'COURSE_TEMPLATE'   => 'course_template',
        'COURSE_VISIBILITY' => 'course_visibility',
    );

    /**
     * Constructor.
     */
    public function __construct() {
        $this->filepath = $this->get_filepath(get_config('local_xmlsync', 'syncpath'), self::COURSEIMPORT_FILENAME);
        $this->reader = new \XMLReader();
        if (!$this->reader->open($this->filepath)) {
            throw new \Exception(get_string('error:noopen', 'local_xmlsync', $this->filepath));
        }
    }

    /**
     * Import XML rowset into the nominated table.
     *
     * Using a blue/green table structure, new data should be imported into the non-active replica.
     *
     * @param string $tablename table name suffix.
     * @param bool $flush Whether to flush existing entries before import.
     * @return bool True when a live table import was completed, false on a dry run.
     */
    public function import($tablename = COURSEIMPORT_MAIN, $flush = true) {
        global $DB;

        $importtable = "local_xmlsync_{$tablename}";
        $logtable = "local_xmlsync_{$tablename}_log";

        if (empty($tablename)) {
            echo get_string('warning:dryrun', 'local_xmlsync') . "\n";
            $liveimport = false;
        } else {
            local_xmlsync_validate_courseimport($tablename);

            $importtable = "local_xmlsync_{$tablename}";
            $liveimport = true;

            if ($flush) {
                echo get_string('courseimport:flushentries', 'local_xmlsync', $importtable) . "\n";
                $DB->delete_records($importtable);
                echo get_string('importingstart', 'local_xmlsync') . "\n";
            }

        }

        $reader = $this->reader;  // Shorthand.

        // Ensure we have the right top-level node.
        $reader->read();
        assert($reader->name == self::XMLROWSET);

        // Parse time and convert to Unix timestamp.
        $sourcetimestamp = (new \DateTimeImmutable($reader->getAttribute("timestamp")))->getTimestamp();

        $metadata = array(
            "sourcefile" => $reader->getAttribute("sourcefile"),
            "sourcetimestamp" => $sourcetimestamp,
        );
        $importcount = 0;
        // Tally count by action.
        $actioncounts = array(
            self::ACTION_UPDATE => 0,
            self::ACTION_DELETE => 0,
        );

        // Check for stale file import: warn, but continue processing.
        $stalethreshold = get_config('local_xmlsync', 'stale_threshold'); // Difference in seconds.
        $now = (new \DateTimeImmutable('now'))->getTimestamp();
        $filedelta = ($now - $sourcetimestamp); // Difference in seconds.
        if ($filedelta > $stalethreshold) {
            local_xmlsync_warn_import(
                get_string('courseimport:stalefile', 'local_xmlsync')
                . "\n\n"
                . get_string('courseimport:stalefile_timestamp', 'local_xmlsync', $reader->getAttribute("timestamp"))
                . "\n",
                get_string('courseimport:stalemailsubject', 'local_xmlsync')
            );
        }

        // Check for last timestamp match: skip processing if equal.
        if ($this->lastsourcetimestamp) {
            if ($this->lastsourcetimestamp == $sourcetimestamp) {
                echo get_string('warning:timestampmatch', 'local_xmlsync') . "\n";
                return false;
            }
        }

        // Traverse the XML document, looking for rows and a rowcount at the end.
        while ($reader->read()) {
            // Parse from element start tags.
            if ($reader->nodeType == \XMLReader::ELEMENT) {
                if ($reader->name == self::XMLROW) {
                    $rowdata = array();
                    $logdata = array();
                    $rownode = $reader->expand();
                    foreach (array_keys($this->rowmapping) as $xmlfield) {
                        $this->import_rowfield($rowdata, $rownode, $xmlfield);
                        $this->import_rowfield($logdata, $rownode, $xmlfield);
                    }

                    $rowaction = $this->get_row_element($rownode, self::XMLACTION);
                    $logdata['rowaction'] = $rowaction;
                    // Use consistent timestamp for import run.
                    $logdata['rowprocessed'] = $now;

                    if ($liveimport) {
                        if ($rowaction == self::ACTION_UPDATE) {
                            // TODO: Check for existing idnumber when we move to deltas.
                            $DB->insert_record($importtable, $rowdata);
                            echo "Update:";
                            var_dump($importtable, $rowdata);
                            $actioncounts[self::ACTION_UPDATE]++;
                        } else if ($rowaction == self::ACTION_DELETE) {
                            // TODO: Deletion
                            // TODO: Check for existing idnumber when we move to deltas.
                            // Warn to console if not found.
                            echo "This would be a deletion.\n";
                            var_dump($rowaction);
                            $actioncounts[self::ACTION_DELETE]++;
                        } else {
                            throw new \Exception(get_string('error:unknownaction', 'local_xmlsync', $rowaction));
                        }
                        // Log action.
                        $DB->insert_record($logtable, $logdata);
                    }

                    $importcount++;

                } else if ($reader->name == self::XMLROWCOUNT) {
                    $metadata["rowcount"] = (int) $reader->readString();
                }
            }
        }

        $metadata['actioncounts'] = $actioncounts;
        $metadata['importcount'] = $importcount;
        $metadata['importedtime'] = (new \DateTimeImmutable('now'))->getTimestamp();
        ksort($metadata);

        if ($liveimport) {
            // When successful, update settings for import metadata.
            echo get_string('importingrowcount', 'local_xmlsync', $importcount) . "\n";
            set_config("{$tablename}_metadata", json_encode($metadata), 'local_xmlsync');
            return true;
        } else {
            // This should only happen in diagnostic dry runs.
            echo get_string('dryruncomplete', 'local_xmlsync') . "\n";
            echo get_string('dryrunmetadata', 'local_xmlsync', json_encode($metadata)) . "\n";
            return false;
        }
    }
}
