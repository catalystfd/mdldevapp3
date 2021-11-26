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
 * XML enrol import task
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xmlsync\import;
defined('MOODLE_INTERNAL') || die();


class enrol_importer extends base_importer {
    const ENROL_IMPORT_FILENAME = 'moodle_enr.xml';
    const XMLROWSET = "ROWSET";
    const XMLROW = "ROW";
    const XMLROWCOUNT = "ROWCOUNT";

    /**
     * Mapping from incoming XML field names to database column names.
     */
    public $rowmapping = array(
        'COURSE_IDNUMBER'    => 'course_idnumber',
        'USERNAME'           => 'username',
        'ROLE_SHORTNAME'     => 'role_shortname',
        'USER_IDNUMBER'      => 'user_idnumber',
        'ETHNIC_CODES'       => 'ethnic_codes',
        'ETHNIC_DESCRIPTION' => 'ethnic_description',
        'RESIDENCY'          => 'residency',
        'UNDER_25'           => 'under_25',
        'MAORI'              => 'maori',
        'PACIFIC'            => 'pacific',
        'INTERNATIONAL'      => 'international',
    );

    /**
     * Constructor.
     */
    public function __construct() {
        $this->filepath = $this->get_filepath(get_config('local_xmlsync', 'syncpath'), self::ENROL_IMPORT_FILENAME);
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
     * @param string $replicaname Replica table name suffix.
     * @param bool $flush Whether to flush existing entries before import.
     * @return bool True when a live table import was completed, false on a dry run.
     */
    public function import($replicaname = null, $flush = true) {
        global $DB;

        if (empty($replicaname)) {
            echo get_string('warning:dryrun', 'local_xmlsync') . "\n";
            $liveimport = false;
        } else {
            local_xmlsync_validate_enrolimport_replica($replicaname);

            $importtable = "local_xmlsync_{$replicaname}";
            $liveimport = true;

            if ($flush) {
                echo get_string('enrolimport:flushentries', 'local_xmlsync', $importtable) . "\n";

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

        // Check for stale file import: warn, but continue processing.
        $stalethreshold = get_config('local_xmlsync', 'stale_threshold'); // Difference in seconds.
        $now = (new \DateTimeImmutable('now'))->getTimestamp();
        $filedelta = ($now - $sourcetimestamp); // Difference in seconds.
        if ($filedelta > $stalethreshold) {
            local_xmlsync_warn_import(
                get_string('enrolimport:stalefile', 'local_xmlsync')
                . "\n\n"
                . get_string('enrolimport:stalefile_timestamp', 'local_xmlsync', $reader->getAttribute("timestamp"))
                . "\n",
                get_string('enrolimport:stalemailsubject', 'local_xmlsync')
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
                    $rownode = $reader->expand();
                    foreach (array_keys($this->rowmapping) as $xmlfield) {
                        $this->import_rowfield($rowdata, $rownode, $xmlfield);
                    }

                    if ($liveimport) {
                        $DB->insert_record($importtable, $rowdata);
                    }

                    $importcount++;

                } else if ($reader->name == self::XMLROWCOUNT) {
                    $metadata["rowcount"] = (int) $reader->readString();
                }
            }
        }

        // Ensure imported row count matches expected tally.
        if ($importcount != $metadata["rowcount"]) {
            throw new \Exception("Row count mismatch: imported {$importcount} rows, expected {$metadata["rowcount"]} rows.");
        }

        // Ensure imported row count hasn't drifted too far from any previous value.
        if ($this->lastimportcount) {
            $countdelta = $importcount - $this->lastimportcount;
            $maxdelta = get_config('local_xmlsync', 'import_count_threshold');
            // Skip the check if threshold is set to 0.
            if ($maxdelta > 0 && $maxdelta < abs($countdelta)) {
                throw new \Exception(get_string('error:importcountoverthreshold', 'local_xmlsync', array(
                    'countdelta' => $countdelta,
                    'maxdelta' => $maxdelta,
                )));
            }
        }

        $metadata['importcount'] = $importcount;
        $metadata['importedtime'] = (new \DateTimeImmutable('now'))->getTimestamp();
        ksort($metadata);

        if ($liveimport) {
            // When successful, update settings for import metadata.
            echo get_string('importingrowcount', 'local_xmlsync', $importcount) . "\n";
            set_config("{$replicaname}_metadata", json_encode($metadata), 'local_xmlsync');
            return true;
        } else {
            // This should only happen in diagnostic dry runs.
            echo get_string('dryruncomplete', 'local_xmlsync') . "\n";
            echo get_string('dryrunmetadata', 'local_xmlsync', json_encode($metadata)) . "\n";
            return false;
        }
    }

}
