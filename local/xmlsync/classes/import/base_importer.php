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
 * Base XML importer
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xmlsync\import;

use dml_exception;
use coding_exception;
use Exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

abstract class base_importer {
    // Constants common to all importers / XML formats.
    const XMLROWSET = "ROWSET";
    const XMLROW = "ROW";
    const XMLROWCOUNT = "ROWCOUNT";
    const XMLACTION = "ACTION";

    const ACTION_UPDATE = "U"; // Includes inserts.
    const ACTION_DELETE = "D";

    const ROW_INSERT = 1;
    const ROW_UPDATE = 2;
    const ROW_DELETE = 3;
    const ROW_NOTEXIST = 4;

    //Default amount of records to read from the XML file before inserting into the db via insert_records
    const BATCH_COUNT = 1000;

    /**
     * The filename of the source file for this importer
     * @var string|null
     */
    public $filename = null;

    /**
     * The table the xml document is imported to
     * @var string|null
     */
    public $import_temp_tablename = null;
    /**
     * The table that contains the result of all
     * delta files that are applied
     * @var string|null
     */
    public $import_main_tablename = null;

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
     *
     * Override this in subclasses.
     * @var int[]|null
     */
    public $rowmapping = null;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->filepath = $this->get_filepath(get_config('local_xmlsync', 'syncpath'), $this->filename);
        $this->reader = new \XMLReader();
        if (!$this->reader->open($this->filepath)) {
            throw new \Exception(get_string('error:noopen', 'local_xmlsync', $this->filepath));
        }
    }

    /**
     * Helper: join up filepaths.
     *
     * @param string $basepath
     * @param string $filename
     * @throws \Exception when syncpath is empty.
     * @return string
     */
    protected function get_filepath($basepath, $filename) : string {
        $parts = array($basepath, $filename);

        if (empty($basepath)) {
            throw new \Exception(get_string('error:nosyncpath', 'local_xmlsync'));
        }

        // Deal with doubled slashes.
        return preg_replace('#/+#', '/', join('/', $parts));
    }

    /**
     * After we have completed the import of the XML delta file into
     * a table, we can now compare it against the master table
     * and do any necessary delete or updates in bulk
     * @return bool true if any changes were made, otherwise false
     */
    public function sync() {
        global $DB;



        $transaction = $DB->start_delegated_transaction();
        try {
            $importtable = $this->import_temp_tablename;
            $maintable = $this->import_main_tablename;
            $metadata = new stdClass();
            $metadata->importtable = $importtable;
            $metadata->maintable = $maintable;
            $metadata->pre_sync_total = $DB->count_records($maintable);
            mtrace(get_string('sync:start', 'local_xmlsync', $metadata));

            $creates = $this->get_records_to_create($importtable, $maintable);
            $metadata->insertcount = $this->insert_records($maintable, $creates);

            $updates = $this->get_records_to_update($importtable, $maintable);
            $metadata->updatecount = $this->update_records($maintable, $updates);

            $deletes = $this->get_records_to_delete($importtable, $maintable);
            $metadata->deletecount = $this->delete_records($maintable, $deletes);

            $metadata->post_sync_total = $DB->count_records($maintable);

            //The previous total minus deleted + any inserted does not equal the post sync total, something is wrong!
            if( ( ( $metadata->pre_sync_total - $metadata->deletecount ) + $metadata->insertcount ) != $metadata->post_sync_total ) {
                throw new moodle_exception(get_string('sync:countinvalid', 'local_xmlsync', $metadata));
            }

            $delta = abs($metadata->pre_sync_total - $metadata->post_sync_total);
            $metadata->delta = $delta;

            $maxdelta = get_config('local_xmlsync', 'import_count_threshold');
            $metadata->maxdelta = $maxdelta;
            if ($maxdelta && $maxdelta > 0 && $maxdelta < $delta) {
                //There is too much change in the table for our tastes, lets fail out instead
                throw new moodle_exception(get_string('error:importcountoverthreshold', 'local_xmlsync', $metadata));
            }
        }
        catch (Exception $e) {
            //If anything goes wrong we throw away the attempted update to the main table, then rethrow the
            //exception for task handling to deal with.
            $transaction->rollback($e);
            throw $e;
        }
        mtrace(get_string('sync:complete', 'local_xmlsync', $metadata));
        //We're now happy that the sync completed in a clean manner and commit the change to be visible
        $transaction->allow_commit();

    }

    protected function sanity_check_import_table($importtable) {
        //Child classes can implement custom sanity checks here and create warnings
        return;
    }

    /**
     * Given two tables, return an array of records that need to be created
     * they should be in the form the main table expects
     * @param mixed $importtable 
     * @param mixed $maintable 
     * @return stdClass[] Array of records to create, should be in the form the maintable variable expects
     */
    abstract function get_records_to_create($importtable, $maintable);


    /**
     * Given two tables, return an array of records that need to be updated in place
     * they should be in the form the main table expects
     * @param mixed $importtable 
     * @param mixed $maintable 
     * @return stdClass[] Array of records to update, should be in the form the maintable variable expects, and have the id record of the maintable item to update
     */
    abstract function get_records_to_update($importtable, $maintable);

    /**
     * Given two tables, return an array of records that need to be deleted from the main table
     * at the very least the record must have an id field corresponding to the main table record to delete
     * @param mixed $importtable 
     * @param mixed $maintable 
     * @return stdClass[] Array of records to update, **must** have the id record of the maintable item to remove
     */
    abstract function get_records_to_delete($importtable, $maintable);

    /**
     * Given an import temporary table and a main table, this will calculate
     * A select sql fragment that selects every column from the temp table that
     * is present in the maintable (except the id).
     * 
     * A where sql fragment that checks if any column in the maintable does not matches
     * every column in the import table (except the id column)
     * 
     * Together these can be used as part of individual import classes update records
     * check to get only the minimal record needing changes
     * @param mixed $importtable 
     * @param mixed $maintable 
     * @return string[] array($selectsql, $wheresql) fragments
     * @throws coding_exception 
     */
    protected function get_update_sql_helpers($importtable, $maintable) {
        global $DB;
        $select = [];
        $where = [];
        $columns = $DB->get_columns($maintable);
        foreach($columns as $column) {
            if($column->name == 'id') {
                continue;
            }
            
            //Account for mariadb/mysql not being case sensitive matching by default
            $sql = $DB->sql_equal('import.'.$column->name, 'main.'.$column->name, true, true, true);
            $select[] ='import.'.$column->name;
            $where[] = $sql;
        }
        $selectsql = implode(',', $select);
        $wheresql = implode(' OR ', $where);
        return array($selectsql, $wheresql);
    }

    /**
     * Get the list of ID's to delete and then delete them with a
     * delete records set call
     * @param mixed $table 
     * @param mixed $records 
     * @return int 
     * @throws coding_exception 
     */
    protected function delete_records($table, $records) {
        global $DB;
        $deleteids = array();
        //Get a list of ID's to remove
        foreach($records as $record) {
            if(!isset($record->id) || $record->id == 0) {
                //Something is very wrong, only a developer can fix this
                throw new coding_exception("We were fed a record to delete that did not contain a valid id field" + print_r($record, true));
            }
            $deleteids[] = $record->id;
        }
        //If there are any, we do the work
        if(count($deleteids) > 0) {
            list($lsql, $params) = $DB->get_in_or_equal($deleteids);
            $sql = 'id '.$lsql;
            $DB->delete_records_select($table, $sql, $params);
            return count($deleteids);//total deleted;
        }
        //Nothing to do
        return 0;
    }

    /**
     * Here, we just update records one by one
     * @param mixed $table 
     * @param mixed $records 
     * @return int 
     */
    protected function update_records($table, $records) {
        global $DB;
        foreach($records as $record) {
            $DB->update_record($table, $record);
        }
        return count($records);
    }

    protected function insert_records($table, $records) {
        global $DB;
        $DB->insert_records($table, $records);
        return count($records);
    }
    /**
     * We have an XML document, now we import it straight into
     * the temporary table, usually we flush this table completely
     * before doing this import, as it is a delta file and does not
     * have any relation to previous files.
     * 
     * @return bool true or false depending on if the import was a live import or not
     */
    public function import($flush = true) {
        global $DB;
        $importtable = $this->import_temp_tablename;
        
        if ($flush) {
            mtrace(get_string('import:flushentries', 'local_xmlsync', $importtable) . "\n");

            $DB->delete_records($importtable);
        }

        mtrace(get_string('importingstart', 'local_xmlsync', $importtable) . "\n");
        $reader = $this->reader;  // Shorthand.

        // Ensure we have the right top-level node.
        $reader->read();
        if($reader->name != self::XMLROWSET) {
            //Moodle task logging will catch this and auto backoff or try again
            throw new moodle_exception("The xml import document is not well formed and does not start with the top level node "+self::XMLROWSET);
        }

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
            $filename = get_string('import:filename', 'local_xmlsync', $this->filename);
            local_xmlsync_warn_import(
                get_string('import:stalefile', 'local_xmlsync')
                . "\n\n"
                . $filename
                . "\n"
                . get_string('import:stalefile_timestamp', 'local_xmlsync', $reader->getAttribute("timestamp"))
                . "\n",
                get_string('import:stalemailsubject', 'local_xmlsync')
            );
        }

        // Check for last timestamp match: skip processing if equal.
        if ($this->lastsourcetimestamp) {
            if ($this->lastsourcetimestamp == $sourcetimestamp) {
                echo get_string('warning:timestampmatch', 'local_xmlsync') . "\n";
                return false;
            }
        }

        $to_import = [];//bulk imports
        $batchsize = get_config('local_xmlsync', 'import_batch_threshold');
        if(!$batchsize) {
            $batchsize = self::BATCH_COUNT;
        }
        $batch_count = 0;
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

                    $batch_count++;
                    $importcount++;
                    $to_import[] = $rowdata;
                    if($batch_count > $batchsize) {
                        $DB->insert_records($importtable, $to_import);
                        $to_import = [];
                        $batch_count = 0;
                    }


                } else if ($reader->name == self::XMLROWCOUNT) {
                    $metadata["rowcount"] = (int) $reader->readString();
                }
                else {

                    //throw new moodle_exception("Unknown element in the xml document " . $reader->name);
                }
            }
        }
        /**
         * Capture any remaining import records left over to import
         */
        if(count($to_import) > 0) {
            $DB->insert_records($importtable, $to_import);
            $to_import = [];
            $batch_count = 0;
        }

        // Ensure imported row count matches expected tally.
        if($importcount != $metadata["rowcount"]) {
            throw new moodle_exception("Row count mismatch: imported {$importcount} rows, expected {$metadata["rowcount"]} rows.");
        }



        $metadata['importcount'] = $importcount;
        $metadata['importedtime'] = (new \DateTimeImmutable('now'))->getTimestamp();
        ksort($metadata);

        $counts = $this->count_import_types($importtable);
        // Ensure imported row count matches expected tally.
        if($counts->totalcount != $metadata["rowcount"]) {
            throw new moodle_exception("Row count mismatch: after import we had {$counts->totalcount} rows in the database, expected {$metadata["rowcount"]} rows.");
        }
        mtrace(get_string('import:sanitycheck', 'local_xmlsync'));
        $this->sanity_check_import_table($importtable);

        $counts->importcount = $importcount;
        mtrace(get_string('import:rowcount', 'local_xmlsync', $counts) . "\n");
        return true;
    }

    protected function count_import_types($tablename) {
        global $DB;
        $result = new stdClass();
        $result->totalcount = $DB->count_records($tablename);
        $result->updatecount = $DB->count_records($tablename, array('action' => self::ACTION_UPDATE));
        $result->deletecount = $DB->count_records($tablename, array('action' => self::ACTION_DELETE));
        return $result;
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
        $nodes = $node->getElementsByTagName($xmlfield);
        if($nodes->length == 0) {
            $nodevalue = null;//No value contained in the db
        }else {
            $nodevalue = $nodes[0]->nodeValue;
        }
        

        if (substr_compare($columnname, "_dt", -strlen("_dt")) == 0) {
            // Special handling for timestamps.
            $nodevalue = (int) $nodevalue;
        }

        $nodevale = $this->validate_parameter($nodevalue, $columnname);

        $rowdata[$columnname] = $nodevalue;
    }

    /**
     * Allow subclasses to do custom validation/handling of a rowdata parameter if necessary
     * @param mixed $nodevalue 
     * @param mixed $columname 
     * @return void 
     */
    public function validate_parameter($nodevalue, $columname) {
        return $nodevalue;
    }
  
    /**
     * Helper: get a specific element from within a row body.
     *
     * Assumes unique element names in a row.
     *
     * @param \DOMNode $node
     * @param string $xmlfield
     * @return mixed
     */
    public function get_row_element($node, $xmlfield) {
        return($node->getElementsByTagName($xmlfield)[0]->nodeValue);
    }

}
