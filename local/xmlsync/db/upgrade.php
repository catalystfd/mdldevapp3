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

    if ($oldversion < 2021112600) {

        // Define field visa_nsi to be dropped from local_xmlsync_enrolimport_X replicas.
        $replicas = array(
            new xmldb_table('local_xmlsync_enrolimport_a'),
            new xmldb_table('local_xmlsync_enrolimport_b'),
        );
        $field = new xmldb_field('visa_nsi');

        foreach ($replicas as $table) {
            // Conditionally launch drop field visa_nsi.
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Xmlsync savepoint reached.
        upgrade_plugin_savepoint(true, 2021112600, 'local', 'xmlsync');
    }

    if ($oldversion < 2021120102) {

        // Define table local_xmlsync_crsimport to be created.
        $table = new xmldb_table('local_xmlsync_crsimport');

        // Adding fields to table local_xmlsync_crsimport_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_fullname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_template', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_visibility', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table local_xmlsync_crsimport.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_xmlsync_crsimport.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_xmlsync_crsimport_log to be created.
        $table = new xmldb_table('local_xmlsync_crsimport_log');

        // Adding fields to table local_xmlsync_crsimport_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('rowaction', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rowprocessed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('course_idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_fullname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_template', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_visibility', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table local_xmlsync_crsimport_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_xmlsync_crsimport_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Xmlsync savepoint reached.
        upgrade_plugin_savepoint(true, 2021120102, 'local', 'xmlsync');
    }
    if ($oldversion < 2022050400) {

        // Define table local_xmlsync_userimport_a to be dropped.
        $table = new xmldb_table('local_xmlsync_userimport_a');

        // Conditionally launch drop table for local_xmlsync_userimport_a.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('local_xmlsync_userimport_b');

        // Conditionally launch drop table for local_xmlsync_userimport_b.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        // Define table local_xmlsync_enrolimport_a to be dropped.
        $table = new xmldb_table('local_xmlsync_enrolimport_a');

        // Conditionally launch drop table for local_xmlsync_enrolimport_a.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        // Define table local_xmlsync_enrolimport_b to be dropped.
        $table = new xmldb_table('local_xmlsync_enrolimport_b');

        // Conditionally launch drop table for local_xmlsync_enrolimport_b.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table local_xmlsync_crsimport_log to be dropped.
        $table = new xmldb_table('local_xmlsync_crsimport_log');

        // Conditionally launch drop table for local_xmlsync_crsimport_log.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Xmlsync savepoint reached.
        upgrade_plugin_savepoint(true, 2022050400, 'local', 'xmlsync');
    }

    if ($oldversion < 2022050401) {

        // Define table local_xmlsync_userimport to be created.
        $table = new xmldb_table('local_xmlsync_userimport');

        // Adding fields to table local_xmlsync_userimport.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('password', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('city', XMLDB_TYPE_CHAR, '120', null, XMLDB_NOTNULL, null, null);
        $table->add_field('country', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lang', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('department', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('phone1', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('phone2', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('middlename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('activation_dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('deactivate_dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('archive_dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('purge_dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_xmlsync_userimport.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_xmlsync_userimport.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_xmlsync_userimport_tmp to be created.
        $table = new xmldb_table('local_xmlsync_userimport_tmp');

        // Adding fields to table local_xmlsync_userimport_tmp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('password', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('city', XMLDB_TYPE_CHAR, '120', null, XMLDB_NOTNULL, null, null);
        $table->add_field('country', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lang', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('department', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('phone1', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('phone2', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('middlename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('activation_dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('deactivate_dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('archive_dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('purge_dt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('action', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, '?');

        // Adding keys to table local_xmlsync_userimport_tmp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_xmlsync_userimport_tmp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_xmlsync_enrlimport to be created.
        $table = new xmldb_table('local_xmlsync_enrlimport');

        // Adding fields to table local_xmlsync_enrlimport.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('role_shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ethnic_codes', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ethnic_description', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('residency', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('under_25', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
        $table->add_field('maori', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
        $table->add_field('pacific', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
        $table->add_field('international', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');

        // Adding keys to table local_xmlsync_enrlimport.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_xmlsync_enrlimport.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_xmlsync_enrlimport_tmp to be created.
        $table = new xmldb_table('local_xmlsync_enrlimport_tmp');

        // Adding fields to table local_xmlsync_enrlimport_tmp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('role_shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ethnic_codes', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ethnic_description', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('residency', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('under_25', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
        $table->add_field('maori', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
        $table->add_field('pacific', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
        $table->add_field('international', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL, null, '?');
        $table->add_field('action', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, '?');

        // Adding keys to table local_xmlsync_enrlimport_tmp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_xmlsync_enrlimport_tmp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_xmlsync_crsimport to be created.
        $table = new xmldb_table('local_xmlsync_crsimport');

        // Adding fields to table local_xmlsync_crsimport.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_fullname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_template', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_visibility', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table local_xmlsync_crsimport.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_xmlsync_crsimport.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_xmlsync_crsimport_tmp to be created.
        $table = new xmldb_table('local_xmlsync_crsimport_tmp');

        // Adding fields to table local_xmlsync_crsimport_tmp.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_idnumber', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_fullname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_shortname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_template', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_visibility', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('action', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, '?');

        // Adding keys to table local_xmlsync_crsimport_tmp.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_xmlsync_crsimport_tmp.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Xmlsync savepoint reached.
        upgrade_plugin_savepoint(true, 2022050401, 'local', 'xmlsync');
    }
    if ($oldversion < 2022050402) {

        // Changing nullability of field course_visibility on table local_xmlsync_crsimport to null.
        $table = new xmldb_table('local_xmlsync_crsimport');
        $field = new xmldb_field('course_visibility', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'course_template');

        // Launch change of nullability for field course_visibility.
        $dbman->change_field_notnull($table, $field);


        // Changing nullability of field course_visibility on table local_xmlsync_crsimport_tmp to null.
        $table = new xmldb_table('local_xmlsync_crsimport_tmp');
        $field = new xmldb_field('course_visibility', XMLDB_TYPE_INTEGER, '1', null, null, null, '1', 'course_template');

        // Launch change of nullability for field course_visibility.
        $dbman->change_field_notnull($table, $field);

        // Xmlsync savepoint reached.
        upgrade_plugin_savepoint(true, 2022050402, 'local', 'xmlsync');
    }
    if ($oldversion < 2022050500) {

        // Changing nullability of field password on table local_xmlsync_userimport to null.
        $table = new xmldb_table('local_xmlsync_userimport');
        $field = new xmldb_field('password', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'username');

        // Launch change of nullability for field password
        $dbman->change_field_notnull($table, $field);


        // Changing nullability of field password on table local_xmlsync_userimport to null.
        $table = new xmldb_table('local_xmlsync_userimport_tmp');
        $field = new xmldb_field('password', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'username');

        // Launch change of nullability for field password
        $dbman->change_field_notnull($table, $field);

        // Xmlsync savepoint reached.
        upgrade_plugin_savepoint(true, 2022050500, 'local', 'xmlsync');
    }

    return true;
}


