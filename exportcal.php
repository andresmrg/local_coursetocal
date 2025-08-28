<?php
require(__DIR__ . '/../../config.php');

global $CFG, $USER, $DB;

require_login();

$password = $DB->get_record('user', ['id' => $USER->id], 'password');

$eventid = optional_param('eventid', 0, PARAM_INT);
if (empty($eventid)) {
    throw new moodle_exception('missingparam', 'error', '', 'eventid');
}

if (empty($CFG->calendar_exportsalt)) {
    throw new moodle_exception('generalexceptionmessage', 'error', '', 'Calendar export salt is not set.');
}

$params = [
    'userid'    => $USER->id,
    'authtoken' => sha1($USER->id . $password->password . $CFG->calendar_exportsalt),
    'eventid'   => $eventid,
];

redirect(new moodle_url('/local/coursetocal/export.php', $params));
