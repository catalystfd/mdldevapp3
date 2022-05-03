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
    
    public $filename = 'moodle_enr.xml';

    public $import_temp_tablename = 'local_xmlsync_enrlimport_tmp';

    public $import_main_tablename = 'local_xmlsync_enrlimport';
    /**
     * Mapping from incoming XML field names to database column names.
     * Note: ACTION is handled separately.
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
        'ACTION'             => 'action',
    );

    public function get_records_to_create($importtable, $maintable)
    {
        global $DB;
        $params = array(self::ACTION_UPDATE);
        $sql = "
            SELECT import.*
              FROM {{$importtable}} import
         LEFT JOIN {{$maintable}} main
                ON main.course_idnumber = import.course_idnumber AND main.user_idnumber = import.user_idnumber
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
                ON main.course_idnumber = import.course_idnumber AND main.user_idnumber = import.user_idnumber
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
                ON main.course_idnumber = import.course_idnumber AND main.user_idnumber = import.user_idnumber
             WHERE import.action = ?
             AND ( {$wheresql} )
        ";
        return $DB->get_records_sql($sql, $params);
    }

    public function sanity_check_import_table($importtable) {
        global $DB;
        $idsql = $DB->sql_concat_join("' '", array('course_idnumber', 'user_idnumber'));
        $sql = "SELECT * FROM (SELECT $idsql AS id, count(id) as appearances, course_idnumber, user_idnumber
                  FROM {{$importtable}}
              GROUP BY course_idnumber, user_idnumber) as dupes
                 WHERE dupes.appearances > 1";
        $dupes = $DB->get_records_sql($sql);
        foreach($dupes as $dupe) {
            mtrace("user idnumber, course idnumber combination appeared more than once - user_idnumber:$dupe->user_idnumber, course_idnumber: $dupe->course_idnumber, total appearances $dupe->appearances");
        }
    }

}
