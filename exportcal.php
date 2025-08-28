<?php
require(__DIR__ . '/../../config.php');

global $CFG, $USER, $DB;

// Must be logged in to generate a token-bound export.
require_login();

// Fetch current user's password hash (for the token).
$rec = $DB->get_record('user', ['id' => $USER->id], 'id, password', MUST_EXIST);

// Required param from the link.
$eventid = optional_param('eventid', 0, PARAM_INT);
if (empty($eventid)) {
    print_error('missingparam', 'error', '', 'eventid');
}

// Token must use the site’s calendar export salt.
if (empty($CFG->calendar_exportsalt)) {
    print_error('generalexceptionmessage', 'error', '', 'Calendar export salt is not set in admin settings.');
}

$params = [
    'userid'    => $USER->id,
    'authtoken' => sha1($USER->id . $rec->password . $CFG->calendar_exportsalt),
    'eventid'   => $eventid,
];

// Optional debug line while testing (check web server error log):
// error_log('coursetocal redirect -> ' . (new moodle_url('/local/coursetocal/export.php', $params))->out(false));

// Send browser to the actual exporter (which should emit the ICS).
redirect(new moodle_url('/local/coursetocal/export.php', $params));
