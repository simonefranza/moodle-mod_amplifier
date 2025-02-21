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
 * Class for the external service save_reminder.
 *
 * @package    mod_amplifier
 * @category   external
 * @copyright  2025 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_amplifier\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Class for the external service save_reminder.
 *
 * @package    mod_amplifier
 * @category   external
 * @copyright  2025 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_reminder extends \core_external\external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'startdate' => new external_value(PARAM_INT, ''),
                'enddate' => new external_value(PARAM_INT, ''),
                'reminderhour' => new external_value(PARAM_INT, ''),
                'reminderminute' => new external_value(PARAM_INT, ''),
                'frequency' => new external_value(PARAM_INT, ''),
                'amplifiergoalid' => new external_value(PARAM_INT, ''),
                'instanceid' => new external_value(PARAM_INT, ''),
            ]
        );
    }

    /**
     * Returns description of return values
     * @return external_value
     */
    public static function execute_returns() {
        return new external_value(PARAM_TEXT, 'Taxonomy for user in JSON format');
    }

    /**
     * Saves a users reflection reminder
     *
     * @param number $startdate
     * @param number $enddate
     * @param number $reminderhour
     * @param number $reminderminute
     * @param number $frequency
     * @param number $amplifiergoalid
     * @param number $instanceid
     * @return void
     */
    public static function execute(
        $startdate,
        $enddate,
        $reminderhour,
        $reminderminute,
        $frequency,
        $amplifiergoalid,
        $instanceid,
    ) {
        global $USER, $DB;

        // Parameter validation.
        self::validate_parameters(
            self::execute_parameters(),
            [
                'startdate' => $startdate,
                'enddate' => $enddate,
                'reminderhour' => $reminderhour,
                'reminderminute' => $reminderminute,
                'frequency' => $frequency,
                'amplifiergoalid' => $amplifiergoalid,
                'instanceid' => $instanceid,
            ]
        );

        // Capability check.
        $userid = $USER->id;
        $cm = get_coursemodule_from_instance('amplifier', $instanceid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/amplifier:setupgoals', $context);

        // Custom validation: $startdate <= $enddate.
        if ($startdate > $enddate) {
            throw new \invalid_parameter_exception('The start date must be before the end date.');
        }
        // Custom validation: 0 <= $reminderhour <= 23.
        if ($reminderhour < 0 || $reminderhour > 23) {
            throw new \invalid_parameter_exception('The reminder hour is invalid.');
        }

        // Custom validation: 0 <= $reminderminute <= 59.
        if ($reminderminute < 0 || $reminderminute > 59) {
            throw new \invalid_parameter_exception('The reminder minute is invalid.');
        }

        // Make sure that instance exists and user has done setup.
        $params = [
            "instanceid" => $instanceid,
            "userid" => $userid,
            "amplifiergoalid" => $amplifiergoalid,
        ];
        $stmt = "SELECT *
                  FROM {amplifier_goals} goals
                  JOIN {amplifier} amplifier ON amplifier.id = goals.amplifierid
                 WHERE goals.id = :amplifiergoalid
                   AND goals.userid = :userid
                   AND amplifier.id = :instanceid";
        if (!$DB->record_exists_sql($stmt, $params)) {
            throw new \invalid_parameter_exception("You didn't do the setup or the amplifier instance doesn't exist.");
        }

        $params = ["amplifiergoalid" => $amplifiergoalid];
        $reminderrecord = $DB->get_record('amplifier_reminders', $params);

        $update = new \stdClass;
        $update->amplifiergoalid = $amplifiergoalid;
        $update->startdate = $startdate;
        $update->enddate = $enddate;
        $update->reminderhour = $reminderhour;
        $update->reminderminute = $reminderminute;
        $update->frequency = $frequency;
        $update->lastnotificationdate = 0;
        if ($reminderrecord) {
            $update->id = $reminderrecord->id;
            $DB->update_record('amplifier_reminders', $update);
        } else {
            $DB->insert_record('amplifier_reminders', $update);
        }

        return "OK";
    }
}

