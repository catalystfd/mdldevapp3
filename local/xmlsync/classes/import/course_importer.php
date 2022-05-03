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
use xmldb_table;
defined('MOODLE_INTERNAL') || die();


class course_importer extends base_importer {

    public $filename = 'moodle_crs.xml';

    public $import_temp_tablename = 'local_xmlsync_crsimport_tmp';

    public $import_main_tablename = 'local_xmlsync_crsimport';

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
        'ACTION'            => 'action',
    );

    public function get_records_to_create($importtable, $maintable)
    {
        global $DB;
        $params = array(self::ACTION_UPDATE);
        $sql = "
            SELECT import.*
              FROM {{$importtable}} import
         LEFT JOIN {{$maintable}} main
                ON main.course_idnumber = import.course_idnumber
             WHERE main.id IS NULL
               AND import.action = ?";
        $result = $DB->get_records_sql($sql, $params);
        return $result;
    }

    public function get_records_to_delete($importtable, $maintable)
    {
         global $DB;
         $params = array(self::ACTION_DELETE);
         $sql = "
            SELECT main.id
              FROM {{$importtable}} import
        INNER JOIN {{$maintable}} main
                ON main.course_idnumber = import.course_idnumber
             WHERE import.action = ?
         ";
        return $DB->get_records_sql($sql, $params);
    }

    public function get_records_to_update($importtable, $maintable)
    {
        global $DB;
        //Get list of fields to import from the import table
        //We also use the wheresql to only get records that have
        //actually effectively changed in any way - by checking
        //that any of the fields is actually != to the main table
        //You can see we return with the main table records id
        //so these returned records can go straight into an
        //update_record call
        list($selectsql, $wheresql) = $this->get_update_sql_helpers($importtable, $maintable);
        $params = array(self::ACTION_UPDATE);
        $sql = "
            SELECT main.id, {$selectsql}
              FROM {{$importtable}} import
        INNER JOIN {{$maintable}} main
                ON main.course_idnumber = import.course_idnumber
             WHERE import.action = ?
             AND ( {$wheresql} )
        ";
        return $DB->get_records_sql($sql, $params);
    }
}
