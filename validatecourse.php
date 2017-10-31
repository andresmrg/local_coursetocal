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
 * Validate if a event exist.
 *
 * @package    local_coursetocal
 * @copyright  2017 Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$eventid = required_param('eventid', PARAM_INT);

$isacourse = local_coursetocal_is_a_course($eventid);
echo json_encode($isacourse);


/**
 * Returns the event id for a course.
 *
 * @param  int $courseid
 * @return object
 */
function local_coursetocal_is_a_course($eventid) {
    global $DB;
    $conditions = array('id' => $eventid, 'eventtype' => 'ctc_site');
    return $DB->get_record('event', $conditions,  $fields='id, eventtype, uuid');
}