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
 * Helper class to handle calendar and course events.
 *
 * @package    local_coursetocal
 * @copyright  2020 LMS DOCTOR
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_coursetocal;

use moodle_url;
use html_writer;
use core_course_list_element;
use str_replace;
use stdClass;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Helper class to handle calendar and course events.
 *
 * @package    local_coursetocal
 * @copyright  2020 LMS DOCTOR
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Create calendar event.
     *
     * @param  object $event
     * @return void
     */
    public static function create_event($event) {

        $config         = get_config('local_coursetocal');
        $configexport = (isset($config->exportcal)) ? $config->exportcal : get_string('exportcal', 'local_coursetocal');
        $courseinfo     = $event->get_data();
        $details        = $event->get_record_snapshot('course', $courseinfo['courseid']);
        $dateinfo       = self::get_course_dates($details->id);
        $summaryfile    = self::get_coursesummaryfile($details);

        $candocategory = self::validate_category($details->category);

        if (!$candocategory) {
            return;
        }

        $attr2 = array('class' => 'd-block col-3 mt-2 btn btn btn-primary');
        $courseurl = new moodle_url("/course/view.php?id=" . $courseinfo['courseid']);
        $linkurl = html_writer::link($courseurl, $config->title, $attr2);

        $data = self::build_data(
            $details->fullname,
            $details->id,
            $details->startdate,
            $details->enddate,
            $details->visible
        );

        $event = \calendar_event::create($data);
        $eventid = self::get_eventid($details->id);

        // Capture the eventid to generate the link to export.
        $params = array('eventid' => $eventid->id);
        $calurl = new moodle_url('/local/coursetocal/exportcal.php', $params);
        $attr = array('class' => 'd-block col-3 mt-2 btn btn btn-default');
        $linkurl .= html_writer::link($calurl, $configexport, $attr);
        $event->description = $details->summary . "<br>" . $summaryfile . $linkurl;

        $event->update($data);

    }

    protected static function build_data($fullname, $uuid, $tstart, $tend, $visible) {
        $data = new stdClass();

        $data->eventtype       = 'site';
        $data->type            = '-99';
        $data->name            = $fullname;
        $data->uuid            = $uuid;
        $data->courseid        = 1;
        $data->groupid         = 0;
        $data->userid          = 2;
        $data->modulename      = 0;
        $data->instance        = 0;
        $data->timestart       = $tstart;
        $data->visible         = (empty($visible)) ? 0 : 1;
        $data->timeduration    = $tend - $tstart;
        $data->component       = 'local_coursetocal';

        return $data;
    }

    /**
     * Display course summary.
     * @param  object $course
     * @return string
     */
    public static function get_coursesummaryfile($course) {
        global $CFG;

        $course = new core_course_list_element($course);
        $output = '';
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
    public static function update_event($event) {

        $config     = get_config('local_coursetocal');
        $configexport = (isset($config->exportcal)) ? $config->exportcal : get_string('exportcal', 'local_coursetocal');
        $courseinfo = $event->get_data();
        $details    = $event->get_record_snapshot('course', $courseinfo['courseid']);
        $summaryfile  = self::get_coursesummaryfile($details);

        $candocategory = self::validate_category($details->category);

        // Attempt to get an existing event id.
        $eventid = self::get_eventid($courseinfo['courseid']);

        if (!$candocategory && !empty($eventid)) {
            $event = \calendar_event::load($eventid);
            $event->name = $courseinfo['other']['fullname'];
            $event->timestart = $details->startdate;
            $event->repeatid = 0;
            $event->delete();
        } else if (!$candocategory) {
            return;
        }

        // Create object.
        $attr2 = array('class' => 'd-block col-3 mt-2 btn btn btn-primary');
        $courseurl  = new moodle_url("/course/view.php?id=" . $courseinfo['courseid']);
        $linkurl    = html_writer::link($courseurl, $config->title, $attr2);

        $params = array('eventid' => $eventid->id);
        $calurl = new moodle_url('/local/coursetocal/exportcal.php', $params);
        $attr = array('class' => 'd-block col-3 mt-2 btn btn btn-default');
        $linkurl .= html_writer::link($calurl, $configexport, $attr);

        $data = new stdClass;
        $data->name            = $details->fullname;
        $data->description     = $details->summary . "<br>" . $summaryfile . $linkurl;
        $data->timestart       = $details->startdate;
        $data->timeduration    = $details->enddate - $details->startdate;
        // We need to calculate the enddate ourselves if the User has enabled...
        // ..."Calculate the end date from the number of sections" in a weekly-format course.
        if (empty($details->enddate)) {
            $data->timeduration = 0; // Punctual event...
            if ($details->format == 'weeks') { // ...unless it is in fact a weekly-format course.
                global $DB;
                $coursesectionnb = $DB->count_records('course_sections', array('course' => $details->id));
                // The number of sections for one course has always one dummy more to be substracted...
                // ...to get the real number of sections.
                $data->timeduration = ($coursesectionnb - 1) * 3600 * 24 * 7; // So in seconds.
            }
        }
        $data->type            = '-99';
        $data->eventtype       = 'site';
        $data->modulename      = 0;
        $data->visible         = (empty($details->visible)) ? 0 : 1;

        if (empty($eventid)) {
            self::create_event($event);
            $eventid = self::get_eventid($courseinfo['courseid']);
        }

        // Review if the category where the course is, can update in the calendar.
        $candocategory = self::validate_category($details->category);
        if (!$candocategory) {
            return;
        }

        $event = \calendar_event::load($eventid);
        $event->update($data);

    }

    /**
     * Delete calendar event.
     *
     * @param  object $event
     * @return void
     */
    public static function delete_event($event) {

        $courseinfo = $event->get_data();
        $details    = $event->get_record_snapshot('course', $courseinfo['courseid']);

        $candocategory = self::validate_category($details->category);
        if (!$candocategory) {
            return;
        }

        $eventid = self::get_eventid($courseinfo['courseid']);
        $events = \calendar_event::load($eventid);
        $events->delete();

    }

    /**
     * Update all courses calendar events when cron runs.
     *
     * @return bool
     */
    public static function sync_events() {
    global $CFG, $DB;

    // Get a real admin user id (avoid userid=0 invaliduser errors).
    $adminids = array_map('intval', explode(',', $CFG->siteadmins));
    $siteadminid = reset($adminids) ?: 2; // fallback if parsing fails

    // Build course SQL safely.
    $config = get_config('local_coursetocal');
    $sql1 = "SELECT id,category,fullname,startdate,enddate,summary,visible FROM {course}";
    $params = [];
    if (!empty($config->categories)) {
        $cats = array_filter(array_map('intval', preg_split('/,/', $config->categories)));
        if (!empty($cats)) {
            list($inSql, $inParams) = $DB->get_in_or_equal($cats, SQL_PARAMS_QM);
            $sql1 .= " WHERE category $inSql";
            $params = array_merge($params, $inParams);
        }
    }

    $courses = $DB->get_records_sql($sql1, $params);

    mtrace('Course to cal events will begin to sync.');

    $transaction = $DB->start_delegated_transaction();
    $newuuids = [];

    foreach ($courses as $course) {
        if ((int)$course->id === 1) { // Skip front page course record.
            continue;
        }
        $newuuids[] = (int)$course->id;

        $summaryfile  = self::get_coursesummaryfile($course);
        $tday         = time();

        $data = (object)[
            'name'         => $course->fullname,
            'description'  => null,              // set after we know $event->id
            'format'       => FORMAT_HTML,
            'courseid'     => 1,                 // front-page event
            'uuid'         => (int)$course->id,
            'groupid'      => 0,
            'userid'       => 2,      // real user (not 0)
            'repeatid'     => 0,
            'modulename'   => 0,
            'instance'     => 0,
            'eventtype'    => 'site',
            'type'         => '-99',             // plugin marker
            'timestart'    => (int)$course->startdate,
            'timeduration' => max(0, (int)$course->enddate - (int)$course->startdate),
            'timemodified' => $tday,
            'sequence'     => 1,
            'visible'      => empty($course->visible) ? 0 : 1,
            'component'    => 'local_coursetocal', // important
        ];

        $existsid = $DB->get_field_sql(
            'SELECT id FROM {event} WHERE uuid=? AND eventtype=? AND type=?',
            [$data->uuid, 'site', '-99']
        );

        if ($existsid) {
            $data->id = (int)$existsid;
            $event = \calendar_event::load($data->id);

            // Build description with links (needs $event->id).
            $attr2 = ['class' => 'd-block col-3 mt-2 btn btn btn-primary'];
            $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
            $linkurl   = \html_writer::link($courseurl, get_string('gotocourse', 'local_coursetocal'), $attr2);

            $params = ['eventid' => $event->id];
            $attr   = ['class' => 'd-block col-3 mt-2 btn btn btn-default'];
            $linkurl .= \html_writer::link(
                new \moodle_url('/local/coursetocal/exportcal.php', $params),
                get_string('exportcal', 'local_coursetocal'), $attr
            );
            $data->description = $course->summary . "<br>" . $summaryfile . $linkurl;

            $event->update($data);
            mtrace('Events updated for the course ' . $course->fullname);
        } else {
            $event = \calendar_event::create($data);

            $attr2 = ['class' => 'd-block col-3 mt-2 btn btn btn-primary'];
            $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
            $linkurl   = \html_writer::link($courseurl, get_string('gotocourse', 'local_coursetocal'), $attr2);

            $params = ['eventid' => $event->id];
            $attr   = ['class' => 'd-block col-3 mt-2 btn btn btn-default'];
            $linkurl .= \html_writer::link(
                new \moodle_url('/local/coursetocal/exportcal.php', $params),
                get_string('exportcal', 'local_coursetocal'), $attr
            );
            $data->description = $course->summary . "<br>" . $summaryfile . $linkurl;

            $event->update($data);
            mtrace('Events created for the course ' . $course->fullname);
        }
    }

    // Prune stale events (courses no longer selected). Only after we built the new set.
    if (!empty($newuuids)) {
        list($inSql, $inParams) = $DB->get_in_or_equal($newuuids, SQL_PARAMS_QM, '', false);
        $DB->delete_records_select('event',
            "eventtype='site' AND type='-99' AND component='local_coursetocal' AND uuid $inSql",
            $inParams
        );
    }

    $transaction->allow_commit();
    mtrace('Sync finished. You can close this window.');
    return true;
}

    /**
     * Returns the event id for a course.
     *
     * @param  int $courseid
     * @return object
     */
    public static function get_eventid($courseid) {
        global $DB;
        return $DB->get_record('event', array('uuid' => $courseid, 'courseid' => 1), 'id');
    }

    /**
     * Validates if a course belongs to categories to create calendar events.
     *
     * @param  int $coursecategory Category id.
     * @return bool
     */
    protected static function validate_category($coursecategory) {

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
    public static function get_course_dates($courseid) {
        global $DB;
        return $DB->get_record('course', array('id' => $courseid), 'id, summary, startdate, enddate');
    }

    /**
     * Updates the course when an event is updated.
     * @param  mixed $event
     * @return void
     */
    public static function update_course($event) {
        global $DB;

        // We need the config title to find the course link in the descripton
        // of the event and remove it, the course description should not
        // have the link.
        $config = get_config('local_coursetocal');
        $configtitle = (isset($config->title)) ? $config->title : get_string('gotocourse', 'local_coursetocal');

        $e          = $event->get_data();
        $details    = $event->get_record_snapshot('event', $e['objectid']);

        // If the event is not -99, it is not a course event.
        if ($details->type != '-99') {
            return;
        }

        $startdate  = $details->timestart;
        $enddate    = $details->timeduration + $startdate;

        $data               = new stdClass;
        $data->id           = $details->uuid;
        $data->fullname     = $details->name;
        $data->startdate    = $startdate;
        $data->enddate      = $enddate;

        $DB->update_record('course', $data);

    }

    /**
     * Returns the event id for a course.
     *
     * @param  int $courseidul
     * @return object
     */
    public static function is_a_course($eventid) {
        global $DB;
        $conditions = array('id' => $eventid, 'eventtype' => 'ctc_site');
        return $DB->get_record('event', $conditions,  'id, eventtype, uuid');
    }

    /**
     * Returns the courses categories.
     *
     * @return array
     */
    public static function get_course_categories() {
        global $DB;
        $catlist = $DB->get_records('course_categories');
        $categories = [];
        foreach ($catlist as $r) {
            $categories[$r->id] = $r->name;
        }
        return $categories;
    }

}
