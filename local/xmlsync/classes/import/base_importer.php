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
defined('MOODLE_INTERNAL') || die();

abstract class base_importer {
    // Constants common to all importers / XML formats.
    const XMLROWSET = "ROWSET";
    const XMLROW = "ROW";
    const XMLROWCOUNT = "ROWCOUNT";
    const XMLACTION = "ACTION";

    const ACTION_UPDATE = "U"; // Includes inserts.
    const ACTION_DELETE = "D";

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
     * @var int|null
     */
    public $rowmapping = null;

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
