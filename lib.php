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
 * Library of interface functions and constants.
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_amplifier\core\amplifier_controller;

/**
 * Saves a new instance of the mod_amplifier into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param  stdClass $data An object from the form.
 * @return int The id of the newly inserted record.
 */
function amplifier_add_instance(stdClass $data): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->name = $data->name;
    $data->intro = $data->intro;
    $data->id = $DB->insert_record('amplifier', $data);

    return $data->id;
}

/**
 * Updates an instance of the mod_amplifier in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param  stdClass                        $data  An object from the form in mod_form.php.
 * @return bool True if successful, false otherwise.
 */
function amplifier_update_instance(stdClass $data): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->name = $data->name;
    $data->intro = $data->intro;

    return $DB->update_record('amplifier', $data);
}

/**
 * Removes an instance of the mod_amplifier from the database.
 *
 * @param  int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function amplifier_delete_instance(int $id): bool {
    global $DB;

    $activity = $DB->get_record('amplifier', ['id' => $id]);
    if (!$activity) {
        return false;
    }

    $DB->delete_records('amplifier', ['id' => $id]);

    return true;
}

/**
 * Shows the Training Amplifier Widget on the course page.
 *
 * @param cm_info $cm Course-module object
 */
function amplifier_cm_info_view(cm_info $cm) {

    global $USER;

    $amplifiercontroller = new amplifier_controller($cm->course, $USER->id, $cm->id, $cm->instance);

    $templatecontext['courseId'] = $cm->course;
    $templatecontext['courseModuleId'] = $cm->id;
    $templatecontext['instanceId'] = $cm->instance;
    $templatecontext['userId'] = $USER->id;

    $amplifierwidget = $amplifiercontroller->render($templatecontext);

    $cm->set_content($amplifierwidget, true);
}
