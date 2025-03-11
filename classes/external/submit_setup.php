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
 * Class for the external service submit_setup.
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
 * Class for the external service submit_setup.
 *
 * @package    mod_amplifier
 * @category   external
 * @copyright  2025 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submit_setup extends \core_external\external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'instanceid' => new external_value(PARAM_INT, ''),
                'learninggoals' => new external_value(PARAM_TEXT, ''),
            ]
        );
    }

    /**
     * Returns description of return values
     * @return external_value
     */
    public static function execute_returns() {
        return new external_value(PARAM_TEXT, 'OK or error');
    }

    /**
     * submit setup
     *
     * @param number $instanceid
     * @param array $learninggoals
     * @return void
     */
    public static function execute(
        $instanceid,
        $learninggoals
    ) {
        global $USER, $DB;

        // Parameter validation.
        self::validate_parameters(
            self::execute_parameters(),
            [
                'instanceid' => $instanceid,
                'learninggoals' => $learninggoals,
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

        // Check if user already did setup.
        $numexistinggoals = $DB->count_records('amplifier_goals', [
          'userid' => $userid,
          'amplifierid' => $instanceid,
        ]);
        if ($numexistinggoals) {
            // There are already goals setup.
            throw new \moodle_exception('exception:setup_done', 'mod_amplifier',
                new \moodle_url('/course/view.php', ['id' => $cm->course]));
        }

        $learninggoals = json_decode($learninggoals);
        $records = [];

        foreach ($learninggoals as $learninggoal) {
            $numgoals = $DB->count_records('learninggoalwidget_goals', [
                'id' => $learninggoal->goalid,
                'topicid' => $learninggoal->topicid,
            ]);
            if ($numgoals !== 1) {
                continue;
            }
            $usergoal = new \stdClass;
            $usergoal->amplifierid = $instanceid;
            $usergoal->lgwgoalid = $learninggoal->goalid;
            $usergoal->userid = $userid;
            $records[] = $usergoal;
        }
        $DB->insert_records('amplifier_goals', $records);

        return "OK";
    }
}

