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
 * Settings
 *
 * @package    local_coursetocal
 * @copyright  2017 Andres Ramos <andres.ramos@lmsdoctor.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once('locallib.php');

global $DB;

if ($hassiteconfig) {

    // Update events when coming to the settings to make sure changes take effect.
    local_coursetocal_cron();

    $settings = new admin_settingpage( 'local_coursetocal', get_string('pluginname', 'local_coursetocal') );
    $ADMIN->add('localplugins', $settings);

    $catlist = $DB->get_records_sql("SELECT * FROM {course_categories} WHERE visible = 1");
    $categories = array();
    foreach ($catlist as $r){
        $categories[$r->id] = $r->name;
    }

    $settings->add(
        new admin_setting_configmultiselect(
            'local_coursetocal/categories',
            get_string('categoriestoshow','local_coursetocal'),
            '',
            '',
            $categories
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_coursetocal/title',
            get_string('linktitle', 'local_coursetocal'),
            get_string('pluginname', 'local_coursetocal'),
            ''
        )
    );

}





