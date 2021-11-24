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

namespace local_xmlsync\task;
defined('MOODLE_INTERNAL') || die();

class enrol_import_task extends \core\task\scheduled_task {
    /**
     * Task description.
     *
     * @return string
     */
    public function get_name() : string {
        return get_string('enrolimport:crontask', 'local_xmlsync');
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

        $importer = new \local_xmlsync\import\enrol_importer();

        // Fetch last import count and timestamp from active replica's metadata, if present.
        $active = local_xmlsync_get_enrolimport_active_replica();
        $activemeta = local_xmlsync_get_enrolimport_metadata($active);
        if ($activemeta) {
            if (array_key_exists('importcount', $activemeta)) {
                $importer->lastimportcount = $activemeta['importcount'];
            }
            if (array_key_exists('sourcetimestamp', $activemeta)) {
                $importer->lastsourcetimestamp = $activemeta['sourcetimestamp'];
            }
        }

        $inactive = local_xmlsync_get_enrolimport_inactive_replica();

        echo get_string('enrolimport:starttask', 'local_xmlsync', $inactive) . "\n";
        $importcompleted = $importer->import($inactive);

        // If a successful import took place, set new active replica table.
        if ($importcompleted) {
            echo get_string('enrolimport:completetask', 'local_xmlsync') . "\n";
            echo get_string('setactivereplica', 'local_xmlsync', $inactive) . "\n";
            local_xmlsync_set_enrolimport_active_replica($inactive);
        }
    }

}
