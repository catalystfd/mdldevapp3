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
 * High-level utility functions for XML import tasks.
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const USERIMPORT_A = 'userimport_a';
const USERIMPORT_B = 'userimport_b';
const USERIMPORT_REPLICAS = array(USERIMPORT_A, USERIMPORT_B);

const USERIMPORT_ACTIVE_REPLICA_SETTING = 'userimport_activereplica';

/**
 * Get currently active replica name for reading.
 *
 * @return string Replica name (local_xmlsync_$replicaname).
 */
function local_xmlsync_get_userimport_active_replica() {
    $active = get_config('local_xmlsync', USERIMPORT_ACTIVE_REPLICA_SETTING);
    // Default to first replica if none is set.
    if (empty($active)) {
        return USERIMPORT_A;
    } else {
        return $active;
    }
}

/**
 * Get currently inactive replica for XML user imports.
 *
 * @return string Replica name (local_xmlsync_$replicaname).
 */
function local_xmlsync_get_userimport_inactive_replica() {
    if (local_xmlsync_get_userimport_active_replica() == USERIMPORT_A) {
        return USERIMPORT_B;
    } else {
        return USERIMPORT_A;
    }
}

/**
 * Return deserialized user import metadata array.
 *
 * @param string $replicaname valid replica name.
 * @return array|null Metadata from import, if set.
 */
function local_xmlsync_get_userimport_metadata($replicaname) {
    local_xmlsync_validate_userimport_replica($replicaname);
    $metadata = get_config('local_xmlsync', "{$replicaname}_metadata");
    if ($metadata) {
        return json_decode($metadata);
    } else {
        return null;
    }

}

/**
 * Ensure a replica name is valid.
 *
 * A valid replica name maps to an import table in the database.
 * E.g.: 'userimport_a' <-> local_xmlsync_userimport_a
 *
 * @param string $replicaname
 * @throws \Exception if not valid.
 * @return void
 */
function local_xmlsync_validate_userimport_replica($replicaname) {
    if (!in_array($replicaname, USERIMPORT_REPLICAS, true)) {
        throw new \Exception(get_string('error:invalidreplica', 'local_xmlsync', $replicaname));
    }
}

/**
 * Set active table for XML user imports.
 *
 * @param string $replicaname Valid replica table name.
 * @return void
 */
function local_xmlsync_set_userimport_active_replica($replicaname) {
    local_xmlsync_validate_userimport_replica($replicaname);
    set_config(USERIMPORT_ACTIVE_REPLICA_SETTING, $replicaname, 'local_xmlsync');
}
