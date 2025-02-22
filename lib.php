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
use mod_amplifier\output\widget\widget_renderable;

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
    $data->id = $DB->insert_record('amplifier', $data);

    return $data->id;
}

/**
 * Updates an instance of the mod_amplifier in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param  stdClass $data An object from the form in mod_form.php.
 * @return bool True if successful, false otherwise.
 */
function amplifier_update_instance(stdClass $data): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    // Don't allow to change lgw instance (to avoid further complexity).
    $amplifierrecord = $DB->get_record('amplifier', ['id' => $data->id]);
    if (!$amplifierrecord) {
        throw new moodle_exception('exception:instance_not_found', 'mod_amplifier',
            new moodle_url('/course/view.php', ['id' => $data->course]));
    }

    if ((int)$data->learninggoalwidgetid !== (int)$amplifierrecord->learninggoalwidgetid) {
        throw new moodle_exception('exception:change_lgw', 'mod_amplifier',
            new moodle_url('/course/view.php', ['id' => $data->course]));
    }

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

    $goalids = $DB->get_fieldset_select('amplifier_goals', 'id', 'amplifierid = :amplifierid', ['amplifierid' => $id]);

    if (!empty($goalids)) {
        // Convert goal IDs into a safe SQL IN clause.
        list($goalidssql, $goalidsparams) = $DB->get_in_or_equal($goalids, SQL_PARAMS_NAMED);

        $DB->delete_records_select('amplifier_reminders', "amplifiergoalid $goalidssql", $goalidsparams);
        $DB->delete_records_select('amplifier_reflections', "amplifiergoalid $goalidssql", $goalidsparams);
        $DB->delete_records_select('amplifier_goals', "id $goalidssql", $goalidsparams);
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
    global $PAGE;
    $canview = has_capability(
        'mod/amplifier:view',
        context_module::instance($cm->get_course_module_record()->id)
    );

    if (!$canview) {
        if (isguestuser()) {
            $cm->set_content(get_string('guestaccess', 'mod_amplifier'), false);
        } else {
            $cm->set_content(get_string('noaccess', 'mod_amplifier'), false);
        }
        return;
    }

    $renderable = new widget_renderable($cm->instance);
    $widgetrenderer = $PAGE->get_renderer('mod_amplifier', 'widget');
    $cm->set_content($widgetrenderer->render($renderable), true);
}
