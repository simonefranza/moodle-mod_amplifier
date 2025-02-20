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
            'amplifier_reflections',
            [
                'amplifiergoalid' => 'privacy:metadata:amplifier_reflections:amplifiergoalid',
                'response' => 'privacy:metadata:amplifier_reflections:response',
                'timecreated' => 'privacy:metadata:amplifier_reflections:timecreated',
            ],
            'privacy:metadata:amplifier_reflections'
        );

        $items->add_database_table(
            'amplifier_reminders',
            [
                'amplifiergoalid' => 'privacy:metadata:amplifier_reminders:amplifiergoalid',
                'startdate' => 'privacy:metadata:amplifier_reminders:startdate',
                'enddate' => 'privacy:metadata:amplifier_reminders:enddate',
                'reminderhour' => 'privacy:metadata:amplifier_reminders:reminderhour',
                'reminderminute' => 'privacy:metadata:amplifier_reminders:reminderminute',
                'lastnotificationdate' => 'privacy:metadata:amplifier_reminders:lastnotificationdate',
            ],
            'privacy:metadata:amplifier_reminders'
        );

        $items->add_database_table(
            'amplifier_goals',
            [
                'amplifierid' => 'privacy:metadata:amplifier_goals:amplifierid',
                'lgwgoalid' => 'privacy:metadata:amplifier_goals:lgwgoalid',
                'userid' => 'privacy:metadata:amplifier_goals:userid',
            ],
            'privacy:metadata:amplifier_goals'
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
        $sql = "SELECT DISTINCT c.id
                           FROM {context} c
                           JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                           JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                           JOIN {amplifier_goals} amp ON amp.amplifierid = cm.instance
                          WHERE amp.userid = :userid";
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
        $sql = "SELECT DISTINCT amp.userid
                           FROM {course_modules} cm
                           JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                           JOIN {amplifier_goals} amp ON amp.amplifierid = cm.instance
                          WHERE cm.id = :cmid";
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

        // Selected goals.
        $sql = "SELECT ampgoals.amplifierid AS instance,
                       ampgoals.userid AS userid,
                       lgwtopic.title AS topictitle,
                       lgwgoals.title AS goaltitle,
                       c.id AS contextid,
                       cm.id AS cmid
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {amplifier_goals} ampgoals ON ampgoals.amplifierid = cm.instance
            INNER JOIN {learninggoalwidget_goals} lgwgoals ON ampgoals.lgwgoalid = lgwgoals.id
            INNER JOIN {learninggoalwidget_topics} lgwtopics ON lgwgoals.topicid = lgwtopics.id
                 WHERE c.id {$contextsql}
                   AND ampgoals.userid = :userid";

        // Export user selected goals.
        $selectedusergoals = $DB->get_recordset_sql($sql, $params);
        $data = new \stdClass;
        $data->selectedgoals = [];
        foreach ($selectedusergoals as $selectedgoalrecord) {
            $context = $contextlist->current();
            $selectedgoal = new \stdClass;
            $selectedgoal->instance = $selectedgoalrecord->instance;
            $selectedgoal->userid = $selectedgoalrecord->userid;
            $selectedgoal->topictitle = $selectedgoalrecord->topictitle;
            $selectedgoal->goaltitle = $selectedgoalrecord->goaltitle;
            $selectedgoal->contextid = $selectedgoalrecord->contextid;
            $selectedgoal->cmid = $selectedgoalrecord->cmid;
            $data->selectedgoals[] = $selectedgoal;
        }
        writer::with_context($context)->export_data(['selectedgoals'], $data);
        $selectedusergoals->close();

        // Export user reminders.
        $sql = "SELECT ampgoals.amplifierid AS instance,
                       ampgoals.userid AS userid,
                       lgwtopics.title AS topictitle,
                       lgwgoals.title AS goaltitle,
                       ampremind.startdate,
                       ampremind.enddate,
                       ampremind.reminderhour,
                       ampremind.reminderminute,
                       ampremind.frequency,
                       ampremind.lastnotificationdate,
                       c.id AS contextid,
                       cm.id AS cmid
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {amplifier_goals} ampgoals ON ampgoals.amplifierid = cm.instance
            INNER JOIN {amplifier_reminders} ampremind ON ampgoals.id = ampremind.amplifiergoalid
            INNER JOIN {learninggoalwidget_goals} lgwgoals ON ampgoals.lgwgoalid = lgwgoals.id
            INNER JOIN {learninggoalwidget_topics} lgwtopics ON lgwgoals.topicid = lgwtopics.id
                 WHERE c.id {$contextsql}
                   AND ampgoals.userid = :userid";

        $reminders = $DB->get_recordset_sql($sql, $params);
        $data = new \stdClass;
        $data->reminders = [];
        foreach ($reminders as $reminderrecord) {
            $context = $contextlist->current();
            $reminder = new \stdClass;
            $reminder->instance = $reminderrecord->instance;
            $reminder->userid = $reminderrecord->userid;
            $reminder->topictitle = $reminderrecord->topictitle;
            $reminder->goaltitle = $reminderrecord->goaltitle;
            $reminder->startdate = $reminderrecord->startdate;
            $reminder->enddate = $reminderrecord->enddate;
            $reminder->reminderhour = $reminderrecord->reminderhour;
            $reminder->reminderminute = $reminderrecord->reminderminute;
            $reminder->frequency = $reminderrecord->frequency;
            $reminder->lastnotificationdate = $reminderrecord->lastnotificationdate;
            $reminder->contextid = $reminderrecord->contextid;
            $reminder->cmid = $reminderrecord->cmid;
            $data->reminders[] = $reminder;
        }
        writer::with_context($context)->export_data(['reminders'], $data);
        $reminders->close();

        // Export user reflections.
        $sql = "SELECT ampgoals.amplifierid AS instance,
                       ampgoals.userid AS userid,
                       lgwtopics.title AS topictitle,
                       lgwgoals.title AS goaltitle,
                       ampref.timecreated,
                       ampref.response,
                       c.id AS contextid,
                       cm.id AS cmid
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {amplifier_goals} ampgoals ON ampgoals.amplifierid = cm.instance
            INNER JOIN {amplifier_reflections} ampref ON ampgoals.id = ampref.amplifiergoalid
            INNER JOIN {learninggoalwidget_goals} lgwgoals ON ampgoals.lgwgoalid = lgwgoals.id
            INNER JOIN {learninggoalwidget_topics} lgwtopics ON lgwgoals.topicid = lgwtopics.id
                 WHERE c.id {$contextsql}
                   AND ampgoals.userid = :userid";

        $reflections = $DB->get_recordset_sql($sql, $params);
        $data = new \stdClass;
        $data->reflections = [];
        foreach ($reflections as $reflectionrecord) {
            $context = $contextlist->current();
            $reflection = new \stdClass;
            $reflection->instance = $reflectionrecord->instance;
            $reflection->userid = $reflectionrecord->userid;
            $reflection->topictitle = $reflectionrecord->topictitle;
            $reflection->goaltitle = $reflectionrecord->goaltitle;
            $reflection->reflectiondate = $reflectionrecord->reflectiondate;
            $reflection->response = $reflectionrecord->response;
            $reflection->contextid = $reflectionrecord->contextid;
            $reflection->cmid = $reflectionrecord->cmid;
            $data->reflections[] = $reflection;
        }
        writer::with_context($context)->export_data(['reflections'], $data);
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

        $goalids = $DB->get_fieldset_select('amplifier_goals', 'id', 'amplifierid = ?', [$cm->instance]);

        if (empty($goalids)) {
            // Nothing to delete.
            return;
        }
        // Convert goal IDs into a safe SQL IN clause.
        list($goalidssql, $goalidsparams) = $DB->get_in_or_equal($goalids, SQL_PARAMS_NAMED);

        $DB->delete_records_select('amplifier_reminders', "amplifiergoalid $goalidssql", $goalidsparams);
        $DB->delete_records_select('amplifier_reflections', "amplifiergoalid $goalidssql", $goalidsparams);
        $DB->delete_records_select('amplifier_goals', "id $goalidssql", $goalidsparams);
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

            self::delete_data_for_user_int($cm->instance, $user->id);

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
            self::delete_data_for_user_int($cm->instance, $userid);
        }
    }

    /**
     * Delete a single user
     *
     * @param instance $instance The course module instance
     * @param user $userid The user id
     */
    private static function delete_data_for_user_int($instance, $userid) {

        global $DB;

        $params = [
            "userid" => $userid,
            "amplifierid" => $instance,
        ];
        $goalids = $DB->get_fieldset_select(
            'amplifier_goals',
            'id',
            'userid = :userid AND amplifierid = :amplifierid',
            $params
        );

        if (empty($goalids)) {
            // Nothing to delete.
            return;
        }
        // Convert goal IDs into a safe SQL IN clause.
        list($goalidssql, $goalidsparams) = $DB->get_in_or_equal($goalids, SQL_PARAMS_NAMED);

        $DB->delete_records_select('amplifier_reminders', "amplifiergoalid $goalidssql", $goalidsparams);
        $DB->delete_records_select('amplifier_reflections', "amplifiergoalid $goalidssql", $goalidsparams);
        $DB->delete_records_select('amplifier_goals', "id $goalidssql", $goalidsparams);
    }
}
