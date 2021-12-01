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

// Course uses a non-replicated database, but we may log actions later.
const COURSEIMPORT_MAIN = 'crsimport';
const COURSEIMPORT_LOG = 'crsimport_log';

// Enrol and User have replicas for now.
const ENROLIMPORT_A = 'enrolimport_a';
const ENROLIMPORT_B = 'enrolimport_b';
const ENROLIMPORT_REPLICAS = array(ENROLIMPORT_A, ENROLIMPORT_B);
const ENROLIMPORT_ACTIVE_REPLICA_SETTING = 'enrolimport_activereplica';

const USERIMPORT_A = 'userimport_a';
const USERIMPORT_B = 'userimport_b';
const USERIMPORT_REPLICAS = array(USERIMPORT_A, USERIMPORT_B);
const USERIMPORT_ACTIVE_REPLICA_SETTING = 'userimport_activereplica';


/*** Course Import functions ***/

/** Get main table for reading and imports.
 *
 * @return string Table name (local_xmlsync_$tablename)
 */
function local_xmlsync_get_courseimport_main() {
    return COURSEIMPORT_MAIN;
}

/** Get log table for audit and review.
 *
 * @return string Table name (local_xmlsync_$logtablename)
 */
function local_xmlsync_get_courseimport_log() {
    return COURSEIMPORT_LOG;
}

/**
 * Return deserialized enrol import metadata array.
 *
 * @return array|null Metadata from import, if set.
 */
function local_xmlsync_get_courseimport_metadata() {
    $metadata = get_config('local_xmlsync', COURSEIMPORT_MAIN . "_metadata");
    if ($metadata) {
        return json_decode($metadata, true);
    } else {
        return null;
    }
}

/**
 * Ensure a table name is valid.
 *
 * With no replicas, this should be the standable import table name.
 *
 * @param string $tablename
 * @throws \Exception if not valid.
 * @return void
 */
function local_xmlsync_validate_courseimport($tablename) {
    if ($tablename !== COURSEIMPORT_MAIN) {
        throw new \Exception(get_string('error:invalidtable', 'local_xmlsync', $tablename));
    }

}

/*** Enrol Import functions ***/

/**
 * Get currently active replica name for reading.
 *
 * @return string Replica name (local_xmlsync_$replicaname).
 */
function local_xmlsync_get_enrolimport_active_replica(): string {
    $active = get_config('local_xmlsync', ENROLIMPORT_ACTIVE_REPLICA_SETTING);
    // Default to first replica if none is set.
    if (empty($active)) {
        return ENROLIMPORT_A;
    } else {
        return $active;
    }
}

/**
 * Get currently inactive replica for XML enrol imports.
 *
 * @return string Replica name (local_xmlsync_$replicaname).
 */
function local_xmlsync_get_enrolimport_inactive_replica(): string {
    if (local_xmlsync_get_enrolimport_active_replica() == ENROLIMPORT_A) {
        return ENROLIMPORT_B;
    } else {
        return ENROLIMPORT_A;
    }
}

/**
 * Return deserialized enrol import metadata array.
 *
 * @param string $replicaname valid replica name.
 * @return array|null Metadata from import, if set.
 */
function local_xmlsync_get_enrolimport_metadata($replicaname): ?array {
    local_xmlsync_validate_enrolimport_replica($replicaname);
    $metadata = get_config('local_xmlsync', "{$replicaname}_metadata");
    if ($metadata) {
        return json_decode($metadata, true);
    } else {
        return null;
    }
}

/**
 * Ensure a replica name is valid.
 *
 * A valid replica name maps to an import table in the database.
 * E.g.: 'enrolimport_a' <-> local_xmlsync_enrolimport_a
 *
 * @param string $replicaname
 * @throws \Exception if not valid.
 * @return void
 */
function local_xmlsync_validate_enrolimport_replica($replicaname): void {
    if (!in_array($replicaname, ENROLIMPORT_REPLICAS, true)) {
        throw new \Exception(get_string('error:invalidreplica', 'local_xmlsync', $replicaname));
    }
}

/**
 * Set active table for XML enrol imports.
 *
 * @param string $replicaname Valid replica table name.
 * @return void
 */
function local_xmlsync_set_enrolimport_active_replica($replicaname) {
    local_xmlsync_validate_enrolimport_replica($replicaname);
    set_config(ENROLIMPORT_ACTIVE_REPLICA_SETTING, $replicaname, 'local_xmlsync');
}


/*** User Import functions ***/

/**
 * Get currently active replica name for reading.
 *
 * @return string Replica name (local_xmlsync_$replicaname).
 */
function local_xmlsync_get_userimport_active_replica(): string {
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
function local_xmlsync_get_userimport_inactive_replica(): string {
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
function local_xmlsync_get_userimport_metadata($replicaname): ?array {
    local_xmlsync_validate_userimport_replica($replicaname);
    $metadata = get_config('local_xmlsync', "{$replicaname}_metadata");
    if ($metadata) {
        return json_decode($metadata, true);
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
function local_xmlsync_validate_userimport_replica($replicaname): void {
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

/*** Generic Functions ***/

/**
 * Issue warning emails to nominated users.
 *
 * A warning will not be issued unless a cooldown period has passed since the last warning.
 *
 * @param string $warningmessage A message to send to nominated recipients.
 * @return void
 */
function local_xmlsync_warn_import($warningmessage, $subject): void {
    $cooldown = get_config('local_xmlsync', 'email_cooldown');
    $lastwarning = get_config('local_xmlsync', 'lastwarningtimestamp');
    $now = (new \DateTimeImmutable('now'))->getTimestamp();

    $sendemail = false;

    // Do not re-send warning if within the cooldown period.
    if ($cooldown && $lastwarning) {
        $warningdelta = $now - $lastwarning;
        if ($warningdelta > $cooldown) {
            $sendemail = true;
        }
    }

    // Always note warning in task log.
    echo get_string('tasklogwarning', 'local_xmlsync', $warningmessage) . "\n";

    if ($sendemail) {
        $warnlist = local_xmlsync_get_warning_recipients();

        $data = new \core\message\message();
        $data->component         = 'moodle';
        $data->name              = 'instantmessage';
        $data->subject           = $subject;
        $data->userfrom          = \core_user::get_noreply_user();
        $data->fullmessage       = $warningmessage;
        $data->fullmessageformat = FORMAT_PLAIN;
        $data->contexturl        = new moodle_url('/admin/tasklogs.php', array('filter' => 'local_xmlsync'));
        $data->contexturlname    = 'Task log';

        foreach ($warnlist as $warnaddress) {
            // Use dummy user to mail to email addresses that may not have a user.
            $dummyemailuser = \core_user::get_noreply_user();
            $dummyemailuser->firstname = false; // Remove "Do not reply to this email" name.
            $dummyemailuser->email = $warnaddress;
            $dummyemailuser->emailstop = false;
            $data->userto = $dummyemailuser;

            message_send($data);
        }
        set_config('lastwarningtimestamp', $now, 'local_xmlsync');
    } else {
        echo get_string('emailcooldownskip', 'local_xmlsync') . "\n";
    }

}

/**
 * Get a list of email addresses to send old file warnings to.
 *
 * If the plugin config has no email addresses, fall back to mailing
 * the site's administrators.
 *
 * @return array Array of email addresses
 */
function local_xmlsync_get_warning_recipients(): array {
    global $CFG;
    $recipients = array();

    $settingrecipients = get_config('local_xmlsync', 'stale_warning_recipients');

    if ($settingrecipients) {
        // Split and trim comma-separated email values.
        $parts = explode(',', $settingrecipients);
        foreach ($parts as $part) {
            $address = trim($part);
            $recipients[] = $address;
        }
    } else {
        // Get siteadmin emails.
        $adminuids = explode(',', $CFG->siteadmins);
        foreach ($adminuids as $uid) {
            $user = \core_user::get_user($uid, 'email');
            $recipients[] = $user->email;
        }
    }

    return $recipients;
}
