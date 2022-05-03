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
$string['settings:import_count_threshold'] = 'Import count change threshold';
$string['settings:import_count_threshold_desc'] = 'If the number of import rows changes by more than this amount, the import will fail. (0 = no checking)';
$string['settings:stale_threshold'] = 'Stale import threshold';
$string['settings:stale_threshold_desc'] = 'An import file older that this will be considered "stale". Attempting to import a stale file will send a warning notification. (0 hours = no checking)';
$string['settings:stale_warning_recipients'] = 'Stale import warning recipients';
$string['settings:stale_warning_recipients_desc'] = 'A comma-separated list of email addresses to send stale import warnings to. If no addresses are given, site administrators will be notified.';
$string['settings:email_cooldown'] = 'Email cooldown period';
$string['settings:email_cooldown_desc'] = 'The minimum elapsed time between sending warning emails. (Warnings will still be noted in the task log.)';
$string['settings:import_batch_threshold'] = 'The maximum batchsize during the import step';
$string['settings:import_batch_threshold_desc'] = 'Defines how many records should be read from the xml file and then inserted at once into the database, a higher value is better for performance, but be aware that too large and you might exhaust memory available to the cron task';
$string['dryruncomplete'] = 'Dry run complete.';
$string['dryrunmetadata'] = 'Metadata: {$a}';
$string['importingstart'] = 'Importing records into {$a}...';
$string['tasklogwarning'] = 'WARNING: {$a}';
$string['emailcooldownskip'] = 'Warning email has already been sent within cooldown period. Skipping further email.';


$string['import:flushentries'] = 'Removing any existing course entries from import temp table {$a}';
$string['import:filename'] = 'The file in question is - {$a}';
$string['import:stalefile'] = "The supplied import XML file is older than expected.\nThe import task will continue, but a newer file should be uploaded if available.";
$string['import:stalefile_timestamp'] = 'ROWSET timestamp given: {$a}';
$string['import:stalemailsubject'] = 'Warning: Old XML file encountered in import {$a}';
$string['import:sanitycheck'] = 'Sanity checking delta file';
$string['import:rowcount'] = '{$a->importcount} rows imported, of which {$a->updatecount} were update actions and {$a->deletecount} were delete actions';

$string['sync:start'] = 'Now aligning the primary table {$a->maintable} with the delta from the import table {$a->importtable}';
$string['sync:complete'] = 'Alignment complete, new main table record count {$a->post_sync_total}, delta change was {$a->delta}, {$a->deletecount} records were deleted, {$a->updatecount} records were updated, {$a->insertcount} records were created';

$string['courseimport:crontask'] = 'Import Course XML file from SFTP';
$string['courseimport:starttask'] = 'Importing courses into table';
$string['courseimport:completetask'] = 'Course import complete.';

$string['enrolimport:crontask'] = 'Import Enrol XML file from SFTP';
$string['enrolimport:starttask'] = 'Importing enrolment data';
$string['enrolimport:completetask'] = 'Enrol import complete.';

$string['userimport:crontask'] = 'Import User XML file from SFTP';
$string['userimport:starttask'] = 'Importing users';
$string['userimport:completetask'] = 'User import complete.';

$string['error:importcountoverthreshold'] = 'Number of rows in import has exceeded safety threshold (+/- {$a->maxdelta}). Count changed by {$a->delta} rows.';
$string['error:invalidreplica'] = 'Invalid replica table: {$a}';
$string['error:noopen'] = 'Could not open file {$a}.';
$string['error:nosyncpath'] = 'Sync file directory path is not set. Please configure in the settings.';
$string['error:invalidtable'] = 'Invalid table: {$a}';
$string['error:unknownaction'] = 'Unknown action in import file: \'{$a}\'';

$string['warning:timestampmatch'] = 'Timestamp exactly matches previously imported file. Importing skipped.';
$string['warning:dryrun'] = 'Dry run: no table name specified.';
