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
 * Events
 *
 * @package    local_coursetocal
 * @copyright  2017 Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array (
        'eventname'   => '\core\event\course_updated',
        'callback'    => 'local_coursetocal_update_event',
        'includefile' => '/local/coursetocal/locallib.php',
    ),

    array (
        'eventname'   => '\core\event\course_created',
        'callback'    => 'local_coursetocal_create_event',
        'includefile' => '/local/coursetocal/locallib.php',
    ),

    array (
        'eventname'   => '\core\event\course_restored',
        'callback'    => 'local_coursetocal_create_event',
        'includefile' => '/local/coursetocal/locallib.php',
    ),

    array (
        'eventname'   => '\core\event\course_deleted',
        'callback'    => 'local_coursetocal_delete_event',
        'includefile' => '/local/coursetocal/locallib.php',
    ),

    array (
        'eventname'   => '\core\event\course_category_updated',
        'callback'    => 'local_coursetocal_cron',
        'includefile' => '/local/coursetocal/locallib.php',
    ),

    array (
        'eventname'   => '\core\event\calendar_event_updated',
        'callback'    => 'local_coursetocal_update_course',
        'includefile' => '/local/coursetocal/locallib.php',
    ),
);




