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
        '' => '',
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
        // TODO: implement.
        return false;
    }

}
