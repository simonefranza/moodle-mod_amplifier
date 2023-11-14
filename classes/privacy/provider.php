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
 * Privacy Subsystem implementation for Learning Goals Widget Activity.
 *
 * @package   mod_amplifier
 * @copyright University of Technology Graz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_amplifier\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;

/**
 * Privacy Subsystem for Learning Goals Widget Activity
 *
 * @copyright University of Technology Graz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin\provider interface.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider {


    /**
     * Returns meta data about this system.
     *
     * @param  collection $items The initialised collection to add items to.
     * @return collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items): collection {

        $items->add_database_table(
            'amplifier_reflection',
            [
                'course' => 'privacy:metadata:amplifier_reflection:course',
                'coursemodule' => 'privacy:metadata:amplifier_reflection:coursemodule',
                'instance' => 'privacy:metadata:amplifier_reflection:instance',
                'user' => 'privacy:metadata:amplifier_reflection:user',
                'participantcode' => 'privacy:metadata:amplifier_reflection:participantcode',
                'goal' => 'privacy:metadata:amplifier_reflection:goal',
                'response' => 'privacy:metadata:amplifier_reflection:response',
            ],
            'privacy:metadata:amplifier_reflection'
        );

        $items->add_database_table(
            'amplifier_reminder',
            [
                'course' => 'privacy:metadata:amplifier_reflection:course',
                'coursemodule' => 'privacy:metadata:amplifier_reflection:coursemodule',
                'instance' => 'privacy:metadata:amplifier_reflection:instance',
                'user' => 'privacy:metadata:amplifier_reflection:user',
                'participantcode' => 'privacy:metadata:amplifier_reflection:participantcode',
                'goal' => 'privacy:metadata:amplifier_reflection:goal',
            ],
            'privacy:metadata:amplifier_reminder'
        );

        $items->add_database_table(
            'amplifier_setup_goals',
            [
                'course' => 'privacy:metadata:amplifier_setup_goals:course',
                'coursemodule' => 'privacy:metadata:amplifier_setup_goals:coursemodule',
                'instance' => 'privacy:metadata:amplifier_setup_goals:instance',
                'user' => 'privacy:metadata:amplifier_setup_goals:user',
                'participantcode' => 'privacy:metadata:amplifier_setup_goals:participantcode',
                'goal' => 'privacy:metadata:amplifier_setup_goals:goal',
            ],
            'privacy:metadata:amplifier_setup_goals'
        );

        $items->add_database_table(
            'amplifier_setup_reflection',
            [
                'course' => 'privacy:metadata:amplifier_setup_reflection:course',
                'coursemodule' => 'privacy:metadata:amplifier_setup_reflection:coursemodule',
                'instance' => 'privacy:metadata:amplifier_setup_reflection:instance',
                'user' => 'privacy:metadata:amplifier_setup_reflection:user',
                'participantcode' => 'privacy:metadata:amplifier_setup_reflection:participantcode',
                'goal' => 'privacy:metadata:amplifier_setup_reflection:goal',
                'response' => 'privacy:metadata:amplifier_setup_reflection:response',
            ],
            'privacy:metadata:amplifier_setup_reflection'
        );

        $items->add_database_table(
            'amplifier_setup',
            [
                'course' => 'privacy:metadata:amplifier_setup:course',
                'coursemodule' => 'privacy:metadata:amplifier_setup:coursemodule',
                'instance' => 'privacy:metadata:amplifier_setup:instance',
                'user' => 'privacy:metadata:amplifier_setup:user',
                'participantcode' => 'privacy:metadata:amplifier_setup:participantcode',
                'finished' => 'privacy:metadata:amplifier_setup:finished',
            ],
            'privacy:metadata:amplifier_setup'
        );

        return $items;
    }

    /**
     * Get the list of contexts where the specified user is using the training amplifier.
     *
     * @param  int $userid The user to search.
     * @return contextlist     $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $resultset = new contextlist();

        // Users who are using the training amplifier.
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {amplifier_setup} amp ON amp.instance = cm.instance
                 WHERE amp.amp_user = :userid AND amp.finished = 1";
        $params = ['contextlevel' => CONTEXT_MODULE, 'modname' => 'amplifier', 'userid' => $userid];
        $resultset->add_from_sql($sql, $params);

        return $resultset;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'cmid'    => $context->instanceid,
            'modname' => 'amplifier',
        ];

        // Users who reflected on learning goals.
        $sql = "SELECT amp.amp_user as userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {amplifier_setup} amp ON amp.instance = cm.instance
                  JOIN {amplifier_reflection} ampref ON ampref.amp_user = amp.amp_user
                 WHERE cm.id = :cmid AND amp.finished = 1";
        $userlist->add_from_sql('userid', $sql, $params);

        \core_question\privacy\provider::get_users_in_context_from_sql($userlist, 'amp', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;
        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $params = [
            'contextlevel'      => CONTEXT_MODULE,
            'modname'           => 'amplifier',
            'userid'          => $userid,
        ];
        $params += $contextparams;

        // Selected goals
        $sql = "SELECT
        ampset.course AS course,
        ampset.instance AS instance,
        ampset.amp_user AS user,
        ampset.participantcode AS participantcode,
        lgwtopic.title AS topictitle,
        lgwgoal.title AS goaltitle,
        c.id AS contextid,
        cm.id AS cmid
        FROM {context} c
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
        INNER JOIN {amplifier_setup} ampset ON ampset.instance = cm.instance
        JOIN {amplifier_setup_goals} ampgoals ON ampgoals.setup = ampset.id
        JOIN {learninggoalwidget_topic} lgwtopic ON ampgoals.topic = lgwtopic.id
        JOIN {learninggoalwidget_goal} lgwgoal ON ampgoals.goal = lgwgoal.id AND ampgoals.topic = lgwgoal.topic
        WHERE c.id {$contextsql}";

        // Export user selected goals
        $selectedusergoals = $DB->get_recordset_sql($sql, $params);
        $data = new stdClass;
        $data->selectedgoals = [];
        foreach ($selectedusergoals as $selectedgoalrecord) {
            $context = $contextlist->current();
            $selectedgoal = new stdClass;
            $selectedgoal->course = $selectedgoalrecord->course;
            $selectedgoal->instance = $selectedgoalrecord->instance;
            $selectedgoal->participantcode = $selectedgoalrecord->participantcode;
            $selectedgoal->topictitle = $selectedgoalrecord->topictitle;
            $selectedgoal->goaltitle = $selectedgoalrecord->goaltitle;
            \array_push($data->selectedgoals, $selectedgoal);
        }
        writer::with_context($context)
            ->export_data(['selectedgoals'], $data);
        $selectedusergoals->close();

        // Export user reminders
        $sql = "SELECT
        ampset.course AS course,
        ampset.instance AS instance,
        ampset.amp_user AS user,
        ampset.participantcode AS participantcode,
        lgwtopic.title AS topictitle,
        lgwgoal.title AS goaltitle,
        from_unixtime(ampremind.startdate/1000) as startdate,
        from_unixtime(ampremind.enddate/1000) as enddate,
        ampremind.reminderhour,
        ampremind.reminderminute,
        ampremind.frequency,
        from_unixtime(ampremind.lastnotificationdate/1000) as lastnotificationdate,
        c.id AS contextid,
        cm.id AS cmid
        FROM {context} c
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
        INNER JOIN {amplifier_setup} ampset ON ampset.instance = cm.instance
        JOIN {amplifier_setup_goals} ampgoals ON ampgoals.setup = ampset.id
        JOIN {amplifier_reminder} ampremind ON ampgoals.goal = ampremind.goal
        JOIN {learninggoalwidget_topic} lgwtopic ON ampgoals.topic = lgwtopic.id
        JOIN {learninggoalwidget_goal} lgwgoal ON ampgoals.goal = lgwgoal.id AND ampgoals.topic = lgwgoal.topic
        WHERE c.id {$contextsql}";

        $reminders = $DB->get_recordset_sql($sql, $params);
        $data = new stdClass;
        $data->reminders = [];
        foreach ($reminders as $reminderrecord) {
            $context = $contextlist->current();
            $reminder = new stdClass;
            $reminder->course = $reminderrecord->course;
            $reminder->instance = $reminderrecord->instance;
            $reminder->participantcode = $reminderrecord->participantcode;
            $reminder->topictitle = $reminderrecord->topictitle;
            $reminder->goaltitle = $reminderrecord->goaltitle;
            $reminder->startdate = $reminderrecord->startdate;
            $reminder->enddate = $reminderrecord->enddate;
            $reminder->reminderhour = $reminderrecord->reminderhour;
            $reminder->reminderminute = $reminderrecord->reminderminute;
            $reminder->frequency = $reminderrecord->frequency;
            $reminder->lastnotificationdate = $reminderrecord->lastnotificationdate;
            \array_push($data->reminders, $reminder);
        }
        writer::with_context($context)
            ->export_data(['reminders'], $data);
        $reminders->close();

        // Export user reflections
        $sql = "SELECT
        ampset.course AS course,
        ampset.instance AS instance,
        ampset.amp_user AS user,
        ampset.participantcode AS participantcode,
        lgwtopic.title AS topictitle,
        lgwgoal.title AS goaltitle,
        from_unixtime(ampref.reflectedat/1000) as reflectiondate,
        ampref.response,
        c.id AS contextid,
        cm.id AS cmid
        FROM {context} c
        INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
        INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
        INNER JOIN {amplifier_setup} ampset ON ampset.instance = cm.instance
        JOIN {amplifier_setup_goals} ampgoals ON ampgoals.setup = ampset.id
        JOIN {amplifier_reflection} ampref ON ampgoals.goal = ampref.goal
        JOIN {learninggoalwidget_topic} lgwtopic ON ampgoals.topic = lgwtopic.id
        JOIN {learninggoalwidget_goal} lgwgoal ON ampgoals.goal = lgwgoal.id AND ampgoals.topic = lgwgoal.topic
        WHERE c.id {$contextsql}";

        $reflections = $DB->get_recordset_sql($sql, $params);
        $data = new stdClass;
        $data->reflections = [];
        foreach ($reflections as $reflectionrecord) {
            $context = $contextlist->current();
            $reflection = new stdClass;
            $reflection->course = $reflectionrecord->course;
            $reflection->instance = $reflectionrecord->instance;
            $reflection->participantcode = $reflectionrecord->participantcode;
            $reflection->topictitle = $reflectionrecord->topictitle;
            $reflection->goaltitle = $reflectionrecord->goaltitle;
            $reflection->reflectiondate = $reflectionrecord->reflectiondate;
            $reflection->response = $reflectionrecord->response;
            \array_push($data->reflections, $reflection);
        }
        writer::with_context($context)
            ->export_data(['reflections'], $data);
        $reflections->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only amplifier module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('amplifier', $context->instanceid);
        if (!$cm) {
            // Only amplifier module will be handled.
            return;
        }

        $DB->delete_records('amplifier_setup', array(
            'coursemodule' => $cm->id,
            'instance' => $cm->instance
        ));
        $DB->delete_records('amplifier_setup_reflection', array(
            'coursemodule' => $cm->id,
            'instance' => $cm->instance
        ));
        $DB->delete_records('amplifier_setup_goals', array(
            'coursemodule' => $cm->id,
            'instance' => $cm->instance
        ));
        $DB->delete_records('amplifier_reminder', array(
            'coursemodule' => $cm->id,
            'instance' => $cm->instance
        ));
        $DB->delete_records('amplifier_reflection', array(
            'coursemodule' => $cm->id,
            'instance' => $cm->instance
        ));
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                // Only amplifier module will be handled.
                continue;
            }

            $cm = get_coursemodule_from_id('amplifier', $context->instanceid);
            if (!$cm) {
                // Only amplifier module will be handled.
                continue;
            }

            // Fetch the details of the data to be removed.
            $user = $contextlist->get_user();

            self::delete_data_for_user_int($cm->id, $cm->instance, $user->id);

        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            // Only amplifier module will be handled.
            return;
        }

        $cm = get_coursemodule_from_id('amplifier', $context->instanceid);
        if (!$cm) {
            // Only amplifier module will be handled.
            return;
        }

        $userids = $userlist->get_userids();

        foreach ($userids as $userid) {
            self::delete_data_for_user_int($cm->id, $cm->instance, $userid);
        }
    }

    /**
     * Delete a single user
     *
     * @param cmid $cmid The course module
     * @param instance $instance The course module instance
     * @param user $user The user id
     */
    private static function delete_data_for_user_int($cmid, $instance, $user) {

        global $DB;

        $DB->delete_records('amplifier_setup', array(
            'coursemodule' => $cmid,
            'instance' => $instance,
            'user' => $user
        ));
        $DB->delete_records('amplifier_setup_reflection', array(
            'coursemodule' => $cmid,
            'instance' => $instance,
            'user' => $user
        ));
        $DB->delete_records('amplifier_setup_goals', array(
            'coursemodule' => $cmid,
            'instance' => $instance,
            'user' => $user
        ));
        $DB->delete_records('amplifier_reminder', array(
            'coursemodule' => $cmid,
            'instance' => $instance,
            'user' => $user
        ));
        $DB->delete_records('amplifier_reflection', array(
            'coursemodule' => $cmid,
            'instance' => $instance,
            'user' => $user
        ));
    }
}
