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
 * Admin settings for XML import task
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_xmlsync', get_string('pluginname', 'local_xmlsync'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_xmlsync/syncpath',
        get_string('settings:syncpath', 'local_xmlsync'),
        get_string('settings:syncpath_desc', 'local_xmlsync'), '', PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext('local_xmlsync/import_count_threshold',
        get_string('settings:import_count_threshold', 'local_xmlsync'),
        get_string('settings:import_count_threshold_desc', 'local_xmlsync'),
        0, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext('local_xmlsync/import_batch_threshold',
    get_string('settings:import_batch_threshold', 'local_xmlsync'),
    get_string('settings:import_batch_threshold_desc', 'local_xmlsync'),
    \local_xmlsync\import\base_importer::BATCH_COUNT, PARAM_INT
    ));

    $settings->add(new admin_setting_configduration('local_xmlsync/stale_threshold',
        get_string('settings:stale_threshold', 'local_xmlsync'),
        get_string('settings:stale_threshold_desc', 'local_xmlsync'), (24 * 3600), 3600 // One day default.
    ));

    $settings->add(new admin_setting_configtext('local_xmlsync/stale_warning_recipients',
        get_string('settings:stale_warning_recipients', 'local_xmlsync'),
        get_string('settings:stale_warning_recipients_desc', 'local_xmlsync'),
        '', PARAM_TEXT
    ));

    $settings->add(new admin_setting_configduration('local_xmlsync/email_cooldown',
        get_string('settings:email_cooldown', 'local_xmlsync'),
        get_string('settings:email_cooldown_desc', 'local_xmlsync'), 3600, 3600 // One hour default.
    ));

}
