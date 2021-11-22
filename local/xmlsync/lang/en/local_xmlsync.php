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
 * Language strings for XML Import.
 *
 * @package    local_xmlsync
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'XML file import tasks';

$string['settings:syncpath'] = 'Sync file directory';
$string['settings:syncpath_desc'] = 'Absolute path to the directory where XML import files are uploaded to.';

$string['dryrun'] = 'Dry run: no replica name specified.';
$string['dryruncomplete'] = 'Dry run complete.';
$string['dryrunmetadata'] = 'Metadata: {$a}';

$string['importingrowcount'] = '{$a} rows imported.';
$string['importingstart'] = 'Importing...';
$string['setactivereplica'] = 'Setting replica as active: {$a}';

$string['userimport:crontask'] = 'Import User XML file from SFTP.';
$string['userimport:starttask'] = 'Importing users into: {$a}';
$string['userimport:completetask'] = 'User import complete.';
$string['userimport:flushentries'] = 'Removing any existing user entries from: {$a}';

$string['error:noopen'] = 'Could not open file {$a}.';
$string['error:nosyncpath'] = 'Sync file directory path is not set. Please configure in the settings.';
$string['error:invalidreplica'] = 'Invalid replica table: {$a}';
