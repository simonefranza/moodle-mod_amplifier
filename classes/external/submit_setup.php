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
                'courseid' => new external_value(PARAM_INT, 'ID of the course'),
                'userid' => new external_value(PARAM_INT, 'ID of the logged in user'),
                'coursemoduleid' => new external_value(PARAM_INT, ''),
                'instanceid' => new external_value(PARAM_INT, ''),
                'participantcode' => new external_value(PARAM_TEXT, ''),
                'reflections' => new external_value(PARAM_TEXT, ''),
                'learninggoals' => new external_value(PARAM_TEXT, ''),
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
     * submit setup
     *
     * @param [type] $courseid
     * @param [type] $userid
     * @param [type] $coursemoduleid
     * @param [type] $instanceid
     * @param [type] $participantcode
     * @param [type] $reflections
     * @param [type] $learninggoals
     * @return void
     */
    public static function execute(
        $courseid,
        $userid,
        $coursemoduleid,
        $instanceid,
        $participantcode,
        $reflections,
        $learninggoals
    ) {
        global $USER, $DB;

        // Parameter validation.
        self::validate_parameters(
            self::execute_parameters(),
            array(
                'courseid' => $courseid,
                'userid' => $userid,
                'coursemoduleid' => $coursemoduleid,
                'instanceid' => $instanceid,
                'participantcode' => $participantcode,
                'reflections' => $reflections,
                'learninggoals' => $learninggoals,
            )
        );

        self::validate_context(\context_user::instance($USER->id));

        $setupid = 0;
        $params = [
            "course" => $courseid,
            "coursemodule" => $coursemoduleid,
            "instance" => $instanceid,
            "amp_user" => $userid,
        ];
        $amplifiersetuprecord = $DB->get_record('amplifier_setup', $params);
        if ($amplifiersetuprecord) {
            $amplifierusersetup = new \stdClass;
            $amplifierusersetup->id = $amplifiersetuprecord->id;
            $amplifierusersetup->participantcode = $participantcode;
            $amplifierusersetup->finished = 1;
            $setupid = $amplifiersetuprecord->id;
            $DB->update_record('amplifier_setup', $amplifierusersetup);
        } else {
            $amplifierusersetup = new \stdClass;
            $amplifierusersetup->amp_user = $userid;
            $amplifierusersetup->course = $courseid;
            $amplifierusersetup->coursemodule = $coursemoduleid;
            $amplifierusersetup->instance = $instanceid;
            $amplifierusersetup->participantcode = $participantcode;
            $amplifierusersetup->reflectiontopicshortname = mod_amplifier\core\amplifier_controller::$reflectiontopicshortname;
            $amplifierusersetup->goalstopicshortname = mod_amplifier\core\amplifier_controller::$goalstopicshortname;
            $amplifierusersetup->finished = 1;
            $setupid = $DB->insert_record('amplifier_setup', $amplifierusersetup);
        }

        $reflections = json_decode($reflections);
        $learninggoals = json_decode($learninggoals);

        foreach ($reflections as $reflection) {
            $userreflection = new \stdClass;
            $userreflection->amp_user = $userid;
            $userreflection->course = $courseid;
            $userreflection->coursemodule = $coursemoduleid;
            $userreflection->instance = $instanceid;
            $userreflection->participantcode = $participantcode;
            $userreflection->setup = $setupid;
            $userreflection->topic = $reflection->topicid;
            $userreflection->goal = $reflection->goalid;
            $userreflection->response = $reflection->userResponse;
            $DB->insert_record('amplifier_setup_reflection', $userreflection);
        }

        foreach ($learninggoals as $learninggoal) {
            $usergoal = new \stdClass;
            $usergoal->amp_user = $userid;
            $usergoal->course = $courseid;
            $usergoal->coursemodule = $coursemoduleid;
            $usergoal->instance = $instanceid;
            $usergoal->participantcode = $participantcode;
            $usergoal->setup = $setupid;
            $usergoal->topic = $learninggoal->topicid;
            $usergoal->goal = $learninggoal->goalid;
            $DB->insert_record('amplifier_setup_goals', $usergoal);
        }

        $jsontaxonomy = "{}";
        return $jsontaxonomy;
    }
}

