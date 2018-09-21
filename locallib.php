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
 * This file holds the functions.
 *
 * @package    local_coursetocal
 * @copyright  2017 Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->libdir . '/coursecatlib.php');

/**
 * Create calendar event.
 *
 * @param  object $event
 * @return void
 */
function local_coursetocal_create_event($event) {

    $config         = get_config('local_coursetocal');
    $courseinfo     = $event->get_data();
    $details        = $event->get_record_snapshot('course', $courseinfo['courseid']);
    $dateinfo       = local_coursetocal_get_course_dates($details->id);
    $summaryfile    = local_coursetocal_get_coursesummaryfile($details);

    $candocategory = local_coursetocal_validate_category($details->category);

    if (!$candocategory) {
        return;
    }

    $courseurl    = new moodle_url("/course/view.php?id=" . $courseinfo['courseid']);
    $linkurl = html_writer::link($courseurl, $config->title);

    $event = new stdClass();
    $event->eventtype       = 'site';
    $event->type            = '-99';
    $event->name            = $details->fullname;
    $event->description     = $details->summary . "<br>" . $summaryfile . $linkurl;
    $event->uuid            = $details->id;
    $event->courseid        = 1;
    $event->groupid         = 0;
    $event->userid          = 2;
    $event->modulename      = 0;
    $event->instance        = 0;
    $event->timestart       = $details->startdate;
    $event->visible         = 1;
    $event->timeduration    = $details->enddate - $details->startdate;

    calendar_event::create($event);

}

/**
 * Display course summary.
 * @param  object $course
 * @return string
 */
function local_coursetocal_get_coursesummaryfile($course) {
    global $CFG;

    $course         = new course_in_list($course);
    $output         = '';
    foreach ($course->get_course_overviewfiles() as $file) {
        if ($file->is_valid_image()) {
            $imagepath = '/' . $file->get_contextid() .
                    '/' . $file->get_component() .
                    '/' . $file->get_filearea() .
                    $file->get_filepath() .
                    $file->get_filename();
            $imageurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $imagepath,
                    false);
            $output = html_writer::tag('div',
                    html_writer::empty_tag('img', array('src' => $imageurl)),
                    array('class' => 'courseimage'));
            $output .= html_writer::empty_tag('br');
            $output .= html_writer::empty_tag('br');
            // Use the first image found.
            break;
        } else {
            $filepath = '/' . $file->get_contextid() .
                    '/' . $file->get_component() .
                    '/' . $file->get_filearea() .
                    $file->get_filepath() .
                    $file->get_filename();
            $fileurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $filepath, false);
            $output = html_writer::link($fileurl, $file->get_filename());
            $output .= html_writer::empty_tag('br');
            $output .= html_writer::empty_tag('br');
            break;
        }
    }
    return $output;

}

/**
 * Update calendar event.
 *
 * @param  object $event
 * @return void
 */
function local_coursetocal_update_event($event) {

    $config     = get_config('local_coursetocal');
    $courseinfo = $event->get_data();
    $details    = $event->get_record_snapshot('course', $courseinfo['courseid']);
    $summaryfile  = local_coursetocal_get_coursesummaryfile($details);

    $candocategory = local_coursetocal_validate_category($details->category);

    // Attempt to get an existing event id.
    $eventid = local_coursetocal_get_eventid($courseinfo['courseid']);

    if (!$candocategory && !empty($eventid)) {
        $event = calendar_event::load($eventid);
        $event->delete();
    } else if (!$candocategory) {
        return;
    }

    // Create object.
    $courseurl  = new moodle_url("/course/view.php?id=" . $courseinfo['courseid']);
    $linkurl    = html_writer::link($courseurl, $config->title);

    $data = new stdClass;
    $data->name            = $details->fullname;
    $data->description     = $details->summary . "<br>" . $summaryfile . $linkurl;
    $data->timestart       = $details->startdate;
    $data->timeduration    = $details->enddate - $details->startdate;
    $data->type            = '-99';
    $data->eventtype       = 'site';

    if (empty($eventid)) {
        local_coursetocal_create_event($event);
        $eventid = local_coursetocal_get_eventid($courseinfo['courseid']);
    }

    $event = calendar_event::load($eventid);
    $event->update($data);

}

/**
 * Delete calendar event.
 *
 * @param  object $event
 * @return void
 */
function local_coursetocal_delete_event($event) {

    $courseinfo = $event->get_data();
    $details    = $event->get_record_snapshot('course', $courseinfo['courseid']);

    $candocategory = local_coursetocal_validate_category($details->category);
    if (!$candocategory) {
        return;
    }

    $eventid = local_coursetocal_get_eventid($courseinfo['courseid']);
    $events = calendar_event::load($eventid);
    $events->delete();

}

/**
 * Update all courses calendar events when cron runs.
 *
 * @return boolean
 */
function local_coursetocal_cron() {
    global $CFG, $DB;

    $DB->delete_records('event', array('eventtype' => 'site', 'type' => '-99'));

    // Get config.
    $config = get_config('local_coursetocal');

    // Validate if there are categories.
    if (empty($config->categories)) {
        $sql1 = "SELECT id,category,fullname,startdate,enddate,summary,visible FROM {course}";
    } else {
        $cats = preg_split('/,/', $config->categories);
        $sql1 = "SELECT id,category,fullname,startdate,enddate,summary,visible FROM {course}";

        $where = " WHERE ";
        foreach ($cats as $cat) {
            $where .= " category = $cat OR";
        }

        if ($cats) {
            $where = substr($where, 0, -2);
            $sql1 .= $where;
        }
    }

    $courses = $DB->get_records_sql($sql1);

    // Get standard course by default to set public events.
    $cid            = $DB->get_field_sql("SELECT id FROM {course} WHERE category = ?", array(0));
    $configtitle    = (isset($config->title)) ? $config->title : get_string('gotocourse', 'local_coursetocal');

    // For each course update the event.
    foreach ($courses as $course) {

        if ($course->id == 1) {
            continue;
        }

        $courseurl  = new moodle_url("/course/view.php?id=" . $course->id);
        $linkurl    = html_writer::link($courseurl, $configtitle);
        $summaryfile  = local_coursetocal_get_coursesummaryfile($course);

        $tday = getdate();
        $data = new stdClass();
        $data->name         = $course->fullname;
        $data->description  = " " . $course->summary . "<br>" . $summaryfile . $linkurl;
        $data->format       = 1;
        $data->courseid     = 1;
        $data->uuid         = $course->id;
        $data->groupid      = 0;
        $data->userid       = 2;
        $data->repeatid     = 0;
        $data->modulename   = 0;
        $data->instance     = 0;
        $data->eventtype    = 'site';
        $data->type         = '-99';
        $data->timestart    = $course->startdate;
        $data->timeduration = $course->enddate - $course->startdate;
        $data->timemodified = $tday['0'];
        $data->sequence     = 1;
        $data->visible      = $course->visible;

        // If exist the event then update.
        $sql = 'SELECT id from {event} WHERE uuid = ? AND eventtype = ? AND type = ?';
        if ($DB->record_exists_sql($sql, array( $course->id, 'site', '-99' ))) {
            $data->id = $DB->get_field_sql($sql, array( $course->id, 'site', '-99') );
            $DB->update_record('event', $data);
        } else {
            $lastinsertid = $DB->insert_record('event', $data);
        }

    }

    return true;
}

/**
 * Returns the event id for a course.
 *
 * @param  int $courseid
 * @return object
 */
function local_coursetocal_get_eventid($courseid) {
    global $DB;
    return $DB->get_record('event', array('uuid' => $courseid), 'id');
}

/**
 * Validates if a course belongs to categories to create calendar events.
 *
 * @param  int $coursecategory Category id.
 * @return boolean
 */
function local_coursetocal_validate_category($coursecategory) {

    // Check if the course can be added to the calendar based on the category.
    $config     = get_config('local_coursetocal');
    $categories = preg_split('/,/', $config->categories);

    $candocategory = false;
    foreach ($categories as $category) {
        if ($category == $coursecategory) {
            $candocategory = true;
        }
    }

    return $candocategory;

}

/**
 * Returns course start and enddate.
 *
 * @param  int $courseid
 * @return object
 */
function local_coursetocal_get_course_dates($courseid) {
    global $DB;
    return $DB->get_record('course', array('id' => $courseid), 'id, summary, startdate, enddate');
}

/**
 * Updates the course when an event is updated.
 * @param  mixed $event
 * @return void
 */
function local_coursetocal_update_course($event) {
    global $DB;

    // We need the config title to find the course link in the descripton
    // of the event and remove it, the course description should not
    // have the link.
    $config         = get_config('local_coursetocal');
    $configtitle    = (isset($config->title)) ? $config->title : get_string('gotocourse', 'local_coursetocal');

    $e          = $event->get_data();
    $details    = $event->get_record_snapshot('event', $e['objectid']);

    // If the event is not -99, it is not a course event.
    if ($details->type != '-99') {
        return;
    }

    $summary    = $details->description;
    $startdate  = $details->timestart;
    $enddate    = $details->timeduration + $startdate;

    $courseurl   = new moodle_url("/course/view.php?id=" . $details->uuid);
    $linkurl     = html_writer::link($courseurl, $configtitle);

    // Get the course details to be able to get the course summary file.
    // Once we get the file we could simply remove it from the summary
    // because we don't want this on the course description.
    $coursedetail = $event->get_record_snapshot('course', $details->uuid);
    $summaryfile  = local_coursetocal_get_coursesummaryfile($coursedetail);

    // Remove link.
    $linkremoved = str_replace($linkurl, "", $summary);

    // Remove summary file.
    $description = str_replace($summaryfile, "", $linkremoved);
    $description = str_replace("<br /><br />", "", $description);

    $data               = new stdClass;
    $data->id           = $details->uuid;
    $data->fullname     = $details->name;
    $data->summary      = $description;
    $data->startdate    = $startdate;
    $data->enddate      = $enddate;

    $DB->update_record('course', $data);

}



