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
 * Utility functions that don't fit elsewhere.
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xmlsync;

defined('MOODLE_INTERNAL') || die();

class util {
    /**
     * Hook for enrol_database to set course visibility.
     *
     * If an xmlsync record with a matching idnumber is found, set course visibility accordingly.
     *
     * Required core change injected into enrol/database/lib.php sync_courses:
     *     \local_xmlsync\util::enrol_database_course_hook($course);
     *
     * WR#371794
     *
     * @param stdClass $course
     * @return void
     */
    public static function enrol_database_course_hook(&$course) {
        global $DB;
        $select = $DB->sql_like('course_idnumber', ':idnum', false); // Case insensitive.
        $params = array('idnum' => $course->idnumber);
        $matchingrecord = $DB->get_record_select('local_xmlsync_crsimport', $select, $params);
        if ($matchingrecord) {
            $course->visible = $matchingrecord->course_visibility;
        }
    }

    /**
     * Hook for enrol_database: Check whether course with idnumber has entry in course import.
     *
     * WR#371793
     *
     * @param string $idnumber
     * @return boolean
     */
    public static function enrol_database_template_check($idnumber) : bool {
        global $DB;
        $select = $DB->sql_like('course_idnumber', ':idnum', false); // Case insensitive.
        $params = array('idnum' => $idnumber);
        $matchingrecord = $DB->get_record_select('local_xmlsync_crsimport', $select, $params);

        if ($matchingrecord && $matchingrecord->course_template != '') {
            return true;
        }

        return false;
    }

    /**
     * Hook for enrol_database: clone course from template.
     *
     * If:
     * - an xmlsync record with a matching idnumber is found
     * - its template field is a valid course idnumber
     * Then clone the template course content into the new course, minus user data.
     *
     * Required core change injected into enrol/database/lib.php sync_courses:
     *     \local_xmlsync\util::enrol_database_template_hook($course);
     *
     * WR#371793
     *
     * @param stdClass $course
     * @return void
     */
    public static function enrol_database_template_hook($course) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $select = $DB->sql_like('course_idnumber', ':idnum', false); // Case insensitive.
        $params = array('idnum' => $course->idnumber);
        $matchingrecord = $DB->get_record_select('local_xmlsync_crsimport', $select, $params);
        if ($matchingrecord) {
            $templatecourse = $DB->get_record('course', array('idnumber' => $matchingrecord->course_template));

            if ($templatecourse) {
                echo "Found matching record and template course.\n";
                echo "Cloning from '{$templatecourse->fullname}' into '{$course->fullname}':\n";

                // Make a fake course copy form.
                $dummyform = array(
                    'courseid' => $templatecourse->id,  // Copying from here.
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname,
                    'category' => $course->category,
                    'visible' => $course->visible,
                    'startdate' => $course->startdate,
                    'enddate' => $course->enddate,
                    'idnumber' => $course->idnumber,
                    'userdata' => '0',  // Do not copy user data.
                    'role_1' => '1', // Keep managers?
                    'role_5' => '0', // Drop students.
                );
                // Cast to stdClass object.
                $mdata = (object) $dummyform;

                $backupcopy = new \core_backup\copy\copy($mdata);
                $backupcopy->create_copy();
            }
        }
    }
}
