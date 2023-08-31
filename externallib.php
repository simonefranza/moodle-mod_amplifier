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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

/**
 * Web Service API
 *
 * @package   mod_amplifier
 * @copyright University of Technology Graz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_amplifier_external extends external_api {

    /**
     * parameter definition
     *
     * @return external_function_parameters service function parameter definition
     */
    public static function submit_setup_parameters() {
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
     * return type definition
     *
     * @return external_value
     */
    public static function submit_setup_returns() {
        return new external_value(PARAM_TEXT, 'taxonomy for user in json format');
    }

    /**
     * get learning goal taxonomy as json
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
    public static function submit_setup(
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
            self::submit_setup_parameters(),
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

        self::validate_context(context_user::instance($USER->id));

        $setupid = 0;
        $sqlstmt = "SELECT id, finished FROM {amplifier_setup}
            WHERE course = ? AND coursemodule = ? AND instance = ? AND amp_user = ?";
        $params = [$courseid, $coursemoduleid, $instanceid, $userid];
        $amplifiersetuprecord = $DB->get_record_sql($sqlstmt, $params);
        if ($amplifiersetuprecord) {
            $amplifierusersetup = new stdClass;
            $amplifierusersetup->id = $amplifiersetuprecord->id;
            $amplifierusersetup->participantcode = $participantcode;
            $amplifierusersetup->finished = 1;
            $setupid = $amplifiersetuprecord->id;
            $DB->update_record('amplifier_setup', $amplifierusersetup);
        } else {
            $amplifierusersetup = new stdClass;
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
            $userreflection = new stdClass;
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
            $usergoal = new stdClass;
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

    /**
     * parameter definition
     *
     * @return external_function_parameters service function parameter definition
     */
    public static function save_reminder_parameters() {
        return new external_function_parameters(
            [
                'startdate' => new external_value(PARAM_INT, ''),
                'enddate' => new external_value(PARAM_INT, ''),
                'reminderhour' => new external_value(PARAM_INT, ''),
                'reminderminute' => new external_value(PARAM_INT, ''),
                'frequency' => new external_value(PARAM_INT, ''),
                'lastnotificationdate' => new external_value(PARAM_INT, ''),
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
     * return type definition
     *
     * @return external_value
     */
    public static function save_reminder_returns() {
        return new external_value(PARAM_TEXT, 'taxonomy for user in json format');
    }

    /**
     * Saves a users reflection reminder
     * @param [type] $startdate
     * @param [type] $enddate
     * @param [type] $reminderhour
     * @param [type] $reminderminute
     * @param [type] $frequency
     * @param [type] $lastnotificationdate
     * @param [type] $goal
     * @param [type] $user
     * @param [type] $course
     * @param [type] $coursemodule
     * @param [type] $instance
     * @param [type] $participantcode
     * @return void
     */
    public static function save_reminder(
        $startdate,
        $enddate,
        $reminderhour,
        $reminderminute,
        $frequency,
        $lastnotificationdate,
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
            self::save_reminder_parameters(),
            array(
                'startdate' => $startdate,
                'enddate' => $enddate,
                'reminderhour' => $reminderhour,
                'reminderminute' => $reminderminute,
                'frequency' => $frequency,
                'lastnotificationdate' => $lastnotificationdate,
                'goal' => $goal,
                'user' => $user,
                'course' => $course,
                'coursemodule' => $coursemodule,
                'instance' => $instance,
                'participantcode' => $participantcode,
            )
        );

        self::validate_context(context_user::instance($USER->id));

        $sqlstmt = "SELECT id
        FROM {amplifier_reminder}
        WHERE amp_user = ? and course = ? and coursemodule = ? and instance = ? and goal = ?";
        $params = [$user, $course, $coursemodule, $instance, $goal];
        $ampeminderrecord = $DB->get_record_sql($sqlstmt, $params);
        if ($ampeminderrecord) {
            $ampuserreminder = new stdClass;
            $ampuserreminder->id = $ampeminderrecord->id;
            $ampuserreminder->startdate = $startdate;
            $ampuserreminder->enddate = $enddate;
            $ampuserreminder->reminderhour = $reminderhour;
            $ampuserreminder->reminderminute = $reminderminute;
            $ampuserreminder->frequency = $frequency;
            $ampuserreminder->lastnotificationdate = $lastnotificationdate;
            $ampuserreminder->amp_user = $user;
            $ampuserreminder->course = $course;
            $ampuserreminder->coursemodule = $coursemodule;
            $ampuserreminder->instance = $instance;
            $ampuserreminder->participantcode = $participantcode;
            $DB->update_record('amplifier_reminder', $ampuserreminder);
        } else {
            $ampuserreminder = new stdClass;
            $ampuserreminder->startdate = $startdate;
            $ampuserreminder->enddate = $enddate;
            $ampuserreminder->reminderhour = $reminderhour;
            $ampuserreminder->reminderminute = $reminderminute;
            $ampuserreminder->frequency = $frequency;
            $ampuserreminder->lastnotificationdate = $lastnotificationdate;
            $ampuserreminder->goal = $goal;
            $ampuserreminder->amp_user = $user;
            $ampuserreminder->course = $course;
            $ampuserreminder->coursemodule = $coursemodule;
            $ampuserreminder->instance = $instance;
            $ampuserreminder->participantcode = $participantcode;
            $DB->insert_record('amplifier_reminder', $ampuserreminder);
        }

        $jsontaxonomy = "{}";
        return $jsontaxonomy;
    }


    /**
     * parameter definition
     *
     * @return external_function_parameters service function parameter definition
     */
    public static function submit_reflections_parameters() {
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
     * return type definition
     *
     * @return external_value
     */
    public static function submit_reflections_returns() {
        return new external_value(PARAM_TEXT, 'taxonomy for user in json format');
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
    public static function submit_reflections(
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
            self::submit_reflections_parameters(),
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

        self::validate_context(context_user::instance($USER->id));
        $reflections = json_decode($reflections);

        foreach ($reflections as $reflection) {
            $userreflection = new stdClass;
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
