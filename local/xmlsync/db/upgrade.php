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
 * Database upgrades
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_xmlsync_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021112500) {

        // Define local_xmlsync_enrolimport_X replica tables to be created.
        $replicas = array(
            new xmldb_table('local_xmlsync_enrolimport_a'),
            new xmldb_table('local_xmlsync_enrolimport_b'),
        );

        foreach ($replicas as $table) {
            // Adding fields to table local_xmlsync_enrolimport_X replica.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('course_idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('role_shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('user_idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('visa_nsi', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, null);
            $table->add_field('ethnic_codes', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('ethnic_description', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('residency', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('under_25', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
            $table->add_field('maori', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
            $table->add_field('pacific', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
            $table->add_field('international', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');

            // Adding keys to table local_xmlsync_enrolimport_X replica.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            // Conditionally launch create table for local_xmlsync_enrolimport_X replica.
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        // Xmlsync savepoint reached.
        upgrade_plugin_savepoint(true, 2021112500, 'local', 'xmlsync');
    }
}


