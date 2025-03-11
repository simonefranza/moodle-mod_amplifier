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
 * Class for the external service submit_reflections.
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
use mod_amplifier\local\amplifier;

/**
 * Class for the external service submit_reflections.
 *
 * @package    mod_amplifier
 * @category   external
 * @copyright  2025 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_reflections extends \core_external\external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'reflection' => new external_value(PARAM_TEXT, ''),
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
     * Saves a users reflection response for a learning goal
     *
     * @param string $reflection
     * @param number $amplifiergoalid
     * @param number $instanceid
     * @return void
     */
    public static function execute(
        $reflection,
        $amplifiergoalid,
        $instanceid,
    ) {
        global $USER, $DB;

        // Parameter validation.
        self::validate_parameters(
            self::execute_parameters(),
            [
                'reflection' => $reflection,
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

        // Check if LGW is valid.
        $amp = new amplifier($instanceid);
        if (!$amp->is_lgw_valid()) {
          throw new \moodle_exception('exception:lgw_missing', 'mod_amplifier',
              new \moodle_url('/course/view.php', ['id' => $cm->course]));
        }

        // Ignore empty reflections.
        if ($reflection === '') {
            return "Reflection is empty, ignored.";
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

        $newreflection = new \stdClass;
        $newreflection->amplifiergoalid = $amplifiergoalid;
        $newreflection->response = $reflection;
        $newreflection->timecreated = time() * 1000;

        $DB->insert_record('amplifier_reflections', $newreflection);

        return "OK";
    }
}
