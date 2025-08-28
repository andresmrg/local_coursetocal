<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License, either version 3 of the License, or
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
 * Build signed export URL for a single coursetocal event and redirect to exporter.
 *
 * @package    local_coursetocal
 * @copyright  2020 LMS Doctor
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

global $CFG, $USER, $DB;

require_login(); // We need a logged-in user to derive the token.

$eventid = optional_param('eventid', 0, PARAM_INT);
if (empty($eventid)) {
    // Modern Moodle: throw an exception instead of print_error().
    throw new moodle_exception('missingparam', 'error', '', 'eventid');
}

if (empty($CFG->calendar_exportsalt)) {
    throw new moodle_exception('generalexceptionmessage', 'error', '', 'Calendar export salt is not set.');
}

// Get the current user's password hash to build the authtoken.
$rec = $DB->get_record('user', ['id' => $USER->id], 'id, password', MUST_EXIST);

// Same token format used by core calendar export.
$token = sha1($USER->id . $rec->password . $CFG->calendar_exportsalt);

$params = [
    'userid'    => $USER->id,
    'authtoken' => $token,
    'eventid'   => $eventid,
];

// Send the browser to the actual exporter which emits the ICS.
redirect(new moodle_url('/local/coursetocal/export.php', $params));
