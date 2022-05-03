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

namespace local_xmlsync\task;
defined('MOODLE_INTERNAL') || die();

class course_import_task extends \core\task\scheduled_task {
    /**
     * Task description.
     *
     * @return string
     */
    public function get_name() : string {
        return get_string('courseimport:crontask', 'local_xmlsync');
    }

    /**
     * Execute import task.
     *
     * Import data from XML into inactive replica table.
     * If the import is successful, set replica to active.
     *
     * @return void
     */
    public function execute() : void {
        global $CFG;

        require_once($CFG->dirroot . '/local/xmlsync/locallib.php');

        $importer = new \local_xmlsync\import\course_importer();
        echo get_string('courseimport:starttask', 'local_xmlsync') . "\n";
        $importer->import();
        $importer->sync();
        echo get_string('courseimport:completetask', 'local_xmlsync') . "\n";
    }

}
