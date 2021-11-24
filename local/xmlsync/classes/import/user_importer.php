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
 * XML user import task
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xmlsync\import;
defined('MOODLE_INTERNAL') || die();


class user_importer {
    const USER_IMPORT_FILENAME = 'moodle_per.xml';
    const XMLROWSET = "ROWSET";
    const XMLROW = "ROW";
    const XMLROWCOUNT = "ROWCOUNT";

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
     */
    public $rowmapping = array(
        'USERNAME'      => 'username',
        'PASSWORD'      => 'password',
        'EMAIL'         => 'email',
        'FIRSTNAME'     => 'firstname',
        'LASTNAME'      => 'lastname',
        'CITY'          => 'city',
        'COUNTRY'       => 'country',
        'LANG'          => 'lang',
        'DESCRIPTION'   => 'description',
        'IDNUMBER'      => 'idnumber',
        'INSTITUTION'   => 'institution',
        'DEPARTMENT'    => 'department',
        'PHONE1'        => 'phone1',
        'PHONE2'        => 'phone2',
        'MIDDLENAME'    => 'middlename',
        'ACTIVATION_DT' => 'activation_dt',
        'DEACTIVATE_DT' => 'deactivate_dt',
        'ARCHIVE_DT'    => 'archive_dt',
        'PURGE_DT'      => 'purge_dt',
    );

    /**
     * Constructor.
     */
    public function __construct() {
        $this->filepath = $this->get_filepath(get_config('local_xmlsync', 'syncpath'));
        $this->reader = new \XMLReader();
        if (!$this->reader->open($this->filepath)) {
            throw new \Exception(get_string('error:noopen', 'local_xmlsync', $this->filepath));
        }
    }

    /**
     * Helper: join up filepaths.
     *
     * @param string $basepath
     * @throws \Exception when syncpath is empty.
     * @return string
     */
    protected function get_filepath($basepath) : string {
        $parts = array($basepath, self::USER_IMPORT_FILENAME);

        if (empty($basepath)) {
            throw new \Exception(get_string('error:nosyncpath', 'local_xmlsync'));
        }

        // Deal with doubled slashes.
        return preg_replace('#/+#', '/', join('/', $parts));
    }

    /**
     * Insert XML value into row data, mapping to table column keys.
     *
     * @param array &$rowdata Array to gather field values.
     * @param \DOMNode $node
     * @param string $xmlfield
     * @return void
     */
    public function import_rowfield(&$rowdata, $node, $xmlfield) {
        $columnname = $this->rowmapping[$xmlfield];
        $nodevalue = $node->getElementsByTagName($xmlfield)[0]->nodeValue;

        if (substr_compare($columnname, "_dt", -strlen("_dt")) == 0) {
            // Special handling for timestamps.
            $nodevalue = (int) $nodevalue;
        }

        $rowdata[$columnname] = $nodevalue;

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
            local_xmlsync_validate_userimport_replica($replicaname);

            $importtable = "local_xmlsync_{$replicaname}";
            $liveimport = true;

            if ($flush) {
                echo get_string('userimport:flushentries', 'local_xmlsync', $importtable) . "\n";
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
            local_xmlsync_warn_userimport(
                get_string('userimport:stalefile', 'local_xmlsync')
                . "\n\n"
                . get_string('userimport:stalefile_timestamp', 'local_xmlsync', $reader->getAttribute("timestamp"))
                . "\n"
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
