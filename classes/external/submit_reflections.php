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
                'reflectiondate' => new external_value(PARAM_INT, ''),
                'reflections' => new external_value(PARAM_TEXT, ''),
                'goal' => new external_value(PARAM_INT, ''),
                'user' => new external_value(PARAM_INT, 'ID of the logged in user'),
                'course' => new external_value(PARAM_INT, 'ID of the course'),
                'coursemodule' => new external_value(PARAM_INT, ''),
                'instance' => new external_value(PARAM_INT, ''),
                'participantcode' => new external_value(PARAM_TEXT, ''),
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
     * @param [type] $reflectiondate
     * @param [type] $reflections
     * @param [type] $goal
     * @param [type] $user
     * @param [type] $course
     * @param [type] $coursemodule
     * @param [type] $instance
     * @param [type] $participantcode
     * @return void
     */
    public static function execute(
        $reflectiondate,
        $reflections,
        $goal,
        $user,
        $course,
        $coursemodule,
        $instance,
        $participantcode
    ) {
        global $USER, $DB;

        // Parameter validation.
        self::validate_parameters(
            self::execute_parameters(),
            array(
                'reflectiondate' => $reflectiondate,
                'reflections' => $reflections,
                'goal' => $goal,
                'user' => $user,
                'course' => $course,
                'coursemodule' => $coursemodule,
                'instance' => $instance,
                'participantcode' => $participantcode
            )
        );

        self::validate_context(\context_user::instance($USER->id));
        $reflections = json_decode($reflections);

        foreach ($reflections as $reflection) {
            $userreflection = new \stdClass;
            $userreflection->reflectedat = $reflectiondate;
            $userreflection->response = $reflection;
            $userreflection->goal = $goal;
            $userreflection->amp_user = $user;
            $userreflection->course = $course;
            $userreflection->coursemodule = $coursemodule;
            $userreflection->instance = $instance;
            $userreflection->participantcode = $participantcode;
            $DB->insert_record('amplifier_reflection', $userreflection);
        }

        $jsontaxonomy = "{}";
        return $jsontaxonomy;
    }
}

