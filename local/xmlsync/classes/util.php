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
            $course->visible = $matchingrecord->visibility;
        }
    }
}
