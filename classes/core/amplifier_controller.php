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

namespace mod_amplifier\core;

use stdClass;

/**
 * Training Amplifier Controller
 *
 * controls everything ;)
 *
 * @package   mod_amplifier
 * @copyright University of Technology Graz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class amplifier_controller {

    /**
     * course id
     *
     * @var int
     */
    private $courseid;

    /**
     * user id
     *
     * @var int
     */
    private $userid;

    /**
     * course module id
     *
     * @var int
     */
    private $coursemoduleid;

    /**
     * instance id
     *
     * @var int
     */
    private $instanceid;

    /**
     * participant code
     *
     * @var
     */
    private $participantcode;

    /**
     * @var
     * Topic shortname of reflective question set within the amplifier setup
     */
    public static $topicshortname = "QUESTIONS_INITIAL";

    /**
     * @var
     * Topic shortname of reflective questions for a learning goal
     */
    public static $goalstopicshortname = "QUESTIONS_GOALS";

    /**
     * @var
     * Flag indicating if the amplifier setup has been finished
     */
    private $finished;

    /**
     * @var
     * The amplifier setup identifier
     */
    private $setupid;

    /**
     * @var
     * The selected user goals
     */
    private $usergoals = "";

    /**
     * ctor of widget_renderable
     *
     * @param [type] $courseid
     * @param [type] $userid
     * @param [type] $coursemoduleid
     * @param [type] $instanceid
     */
    public function __construct($courseid, $userid, $coursemoduleid, $instanceid) {
        global $DB, $OUTPUT;

        $this->courseid = $courseid;
        $this->userid = $userid;
        $this->coursemoduleid = $coursemoduleid;
        $this->instanceid = $instanceid;
        $this->participantcode = "undefined";

        // Amplifier setup for this user exists?
        $sqlstmt = "SELECT id, participantcode, finished
        FROM {amplifier_setup}
        WHERE course = ?
        AND coursemodule = ?
        AND instance = ?
        AND amp_user = ?";
        $params = [$this->courseid, $this->coursemoduleid, $this->instanceid, $this->userid];
        $amplifierusersetup = $DB->get_record_sql($sqlstmt, $params);

        if ($amplifierusersetup !== false) {
            $this->participantcode = $amplifierusersetup->participantcode;
            $this->finished = $amplifierusersetup->finished;
            $this->setupid = $amplifierusersetup->id;
        } else {
            $amplifierusersetup = new stdClass;
            $amplifierusersetup->amp_user = $this->userid;
            $amplifierusersetup->course = $this->courseid;
            $amplifierusersetup->coursemodule = $this->coursemoduleid;
            $amplifierusersetup->instance = $this->instanceid;
            $amplifierusersetup->participantcode = "undefined";
            $amplifierusersetup->reflectiontopicshortname = self::$topicshortname;
            $amplifierusersetup->goalstopicshortname = self::$goalstopicshortname;
            $amplifierusersetup->finished = 0;
            $this->setupid = $DB->insert_record('amplifier_setup', $amplifierusersetup);
            $this->finished = 0;
        }

        if ($this->finished) {

            $renderedrq = "";
            $sqlstmt = "SELECT goal.id as goalid, goal.lgw_title as reflectionquestion, topic.id as topicid
                FROM {learninggoalwidget_i_goals} goals, {learninggoalwidget_topic} topic, {learninggoalwidget_goal} goal
                WHERE goals.lgw_course = ? AND goals.lgw_topic = topic.id AND topic.lgw_title = ? AND goals.lgw_goal = goal.id";
            $params = [$this->courseid, self::$goalstopicshortname];
            $rqrecords = $DB->get_records_sql($sqlstmt, $params);
            foreach ($rqrecords as $rqrecord) {
                $renderedrq .= $OUTPUT->render_from_template(
                    'mod_amplifier/widget/amplifier-reflective-question',
                    [
                        'amplifier_reflective_question_headline' => "",
                        'amplifier_reflective_question_intro' => "",
                        'amplifier_reflective_question_topicid' => $rqrecord->topicid,
                        'amplifier_reflective_question_goalid' => $rqrecord->goalid,
                        'amplifier_reflective_question_questiontext' => $rqrecord->reflectionquestion,
                        'amplifier_button_next' => get_string('amplifier_button_next', 'mod_amplifier'),
                        'amplifier_placeholder_thoughts' => get_string('amplifier_placeholder_thoughts', 'mod_amplifier'),
                    ]
                );
            }

            $sqlstmt = "SELECT goal.id as goalid,
            goal.lgw_title as goaltitle,
            topic.id as topicid,
            topic.lgw_title as topictitle,
            goals.goal as amplifiergoalid
            FROM {amplifier_setup_goals} goals,
            {learninggoalwidget_topic} topic,
            {learninggoalwidget_goal} goal
            WHERE goals.course = ?
            AND goals.coursemodule = ?
            AND goals.instance = ?
            AND goals.amp_user = ?
            AND goals.topic = topic.id
            AND goals.goal = goal.id";
            $params = [$this->courseid, $this->coursemoduleid, $this->instanceid, $this->userid];
            $usergoalrecords = $DB->get_records_sql($sqlstmt, $params);
            foreach ($usergoalrecords as $usergoal) {

                $templatecontext = [];

                $sqlstmt = "SELECT startdate, enddate, reminderhour, reminderminute, frequency
                FROM {amplifier_reminder}
                WHERE goal = ?
                AND course = ?
                AND coursemodule = ?
                AND instance = ?
                AND amp_user = ?";
                $params = [$usergoal->amplifiergoalid, $this->courseid, $this->coursemoduleid, $this->instanceid, $this->userid];
                $ampreminderrecord = $DB->get_record_sql($sqlstmt, $params);
                if ($ampreminderrecord) {
                    $templatecontext['reminder'] = true;
                    $templatecontext['reminderstartdate-day'] = date("d", $ampreminderrecord->startdate / 1000);
                    $templatecontext['reminderstartdate-month'] = date("m", $ampreminderrecord->startdate / 1000);
                    $templatecontext['reminderstartdate-year'] = date("Y", $ampreminderrecord->startdate / 1000);
                    $templatecontext['reminderstartdate-hour'] = date("H", $ampreminderrecord->startdate / 1000);
                    $templatecontext['reminderstartdate-minute'] = date("i", $ampreminderrecord->startdate / 1000);
                    $templatecontext['reminderenddate-day'] = date("d", $ampreminderrecord->enddate / 1000);
                    $templatecontext['reminderenddate-month'] = date("m", $ampreminderrecord->enddate / 1000);
                    $templatecontext['reminderenddate-year'] = date("Y", $ampreminderrecord->enddate / 1000);
                    $templatecontext['reminderenddate-hour'] = date("H", $ampreminderrecord->enddate / 1000);
                    $templatecontext['reminderenddate-minute'] = date("i", $ampreminderrecord->enddate / 1000);
                    $templatecontext['reminderstartdate'] = $ampreminderrecord->startdate;
                    $templatecontext['reminderenddate'] = $ampreminderrecord->enddate;
                    $templatecontext['reminderhour'] = $ampreminderrecord->reminderhour;
                    $templatecontext['reminderminute'] = $ampreminderrecord->reminderminute;
                    $templatecontext['reminderfrequency'] = $ampreminderrecord->frequency;
                }

                $templatecontext['topictitle'] = $usergoal->topictitle;
                $templatecontext['goaltitle'] = $usergoal->goaltitle;
                $templatecontext['topicid'] = $usergoal->topicid;
                $templatecontext['goalid'] = $usergoal->goalid;
                $templatecontext['amplifiergoalid'] = $usergoal->amplifiergoalid;
                $templatecontext['amplifier_calendar'] = $OUTPUT->image_url('amplifier_calendar', 'amplifier');
                $templatecontext['reflectivequestions'] = $renderedrq;
                $templatecontext['amplifier_submit_reflections_headline'] =
                get_string('amplifier_submit_reflections_headline', 'mod_amplifier');
                $templatecontext['amplifier_submit_text_2'] =
                get_string('amplifier_setup_submit_text_2', 'mod_amplifier');
                $templatecontext['amplifier_button_submit'] =
                get_string('amplifier_button_submit_reflection', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_headline'] =
                get_string('amplifier_reminder_settings_headline', 'mod_amplifier');
                $templatecontext['amplifier_reminder_frequency_daily'] =
                get_string('amplifier_reminder_frequency_daily', 'mod_amplifier');
                $templatecontext['amplifier_reminder_frequency_weekly'] =
                get_string('amplifier_reminder_frequency_weekly', 'mod_amplifier');
                $templatecontext['amplifier_reminder_frequency_monthly'] =
                get_string('amplifier_reminder_frequency_monthly', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_startdate_label'] =
                get_string('amplifier_reminder_settings_startdate_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_enddate_label'] =
                get_string('amplifier_reminder_settings_enddate_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_time_label'] =
                get_string('amplifier_reminder_settings_time_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month1_label'] =
                get_string('amplifier_reminder_settings_month1_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month2_label'] =
                get_string('amplifier_reminder_settings_month2_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month3_label'] =
                get_string('amplifier_reminder_settings_month3_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month4_label'] =
                get_string('amplifier_reminder_settings_month4_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month5_label'] =
                get_string('amplifier_reminder_settings_month5_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month6_label'] =
                get_string('amplifier_reminder_settings_month6_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month7_label'] =
                get_string('amplifier_reminder_settings_month7_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month8_label'] =
                get_string('amplifier_reminder_settings_month8_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month9_label'] =
                get_string('amplifier_reminder_settings_month9_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month10_label'] =
                get_string('amplifier_reminder_settings_month10_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month11_label'] =
                get_string('amplifier_reminder_settings_month11_label', 'mod_amplifier');
                $templatecontext['amplifier_reminder_settings_month12_label'] =
                get_string('amplifier_reminder_settings_month12_label', 'mod_amplifier');
                $templatecontext['amplifier_button_submit_reflection'] =
                get_string('amplifier_button_submit_reflection', 'mod_amplifier');

                $this->usergoals .= $OUTPUT->render_from_template(
                    'mod_amplifier/widget/amplifier-user-goal',
                    $templatecontext);
            }

        }
    }

    /**
     * render the training amplifier widget
     * @param [type] $templatecontext
     */
    public function render($templatecontext) {
        global $DB, $OUTPUT;

        $renderedrq = "";
        $sqlstmt = "SELECT goal.id as goalid, goal.lgw_title as reflectionquestion, topic.id as topicid
            FROM {learninggoalwidget_i_goals} goals, {learninggoalwidget_topic} topic, {learninggoalwidget_goal} goal
            WHERE goals.lgw_course = ? AND goals.lgw_topic = topic.id AND topic.lgw_title = ? AND goals.lgw_goal = goal.id";
        $params = [$this->courseid, self::$topicshortname];
        $rqrecords = $DB->get_records_sql($sqlstmt, $params);
        foreach ($rqrecords as $rqrecord) {
            $renderedrq .= $OUTPUT->render_from_template(
                'mod_amplifier/widget/amplifier-reflective-question',
                [
                    'amplifier_reflective_question_headline' => get_string('amplifier_reflective_question_headline',
                    'mod_amplifier'),
                    'amplifier_reflective_question_intro' => get_string('amplifier_reflective_question_intro', 'mod_amplifier'),
                    'amplifier_reflective_question_topicid' => $rqrecord->topicid,
                    'amplifier_reflective_question_goalid' => $rqrecord->goalid,
                    'amplifier_reflective_question_questiontext' => $rqrecord->reflectionquestion,
                    'amplifier_button_next' => get_string('amplifier_button_next', 'mod_amplifier'),
                    'amplifier_placeholder_thoughts' => get_string('amplifier_placeholder_thoughts', 'mod_amplifier'),
                ]
            );
        }

        $renderedlgselection = "";

        $renderedsellgs = "";
        $topicid = 0;
        $sqlstmt = "SELECT goal.id as goalid, goal.lgw_title as goaltitle, topic.id as topicid, topic.lgw_title as topictitle
            FROM {learninggoalwidget_i_goals} goals, {learninggoalwidget_topic} topic, {learninggoalwidget_goal} goal
            WHERE goals.lgw_course = ? AND goals.lgw_topic = topic.id AND topic.lgw_title != ? AND topic.lgw_title != ?
            AND goals.lgw_goal = goal.id ORDER BY topic.lgw_title";
        $params = [$this->courseid, self::$goalstopicshortname, self::$topicshortname];
        $predefinedlgsrecords = $DB->get_records_sql($sqlstmt, $params);
        foreach ($predefinedlgsrecords as $predefinedlgrecord) {

            if ($topicid != $predefinedlgrecord->topicid) {

                $renderedsellgs .= $OUTPUT->render_from_template(
                    'mod_amplifier/widget/amplifier-predefined-learning-topic',
                    [
                        'amplifier_predefined_learning_topic_label' => $predefinedlgrecord->topictitle,
                    ]
                );

            }

            $renderedsellgs .= $OUTPUT->render_from_template(
                'mod_amplifier/widget/amplifier-predefined-learning-goal',
                [
                    'amplifier_predefined_learning_goal_topicid' => $predefinedlgrecord->topicid,
                    'amplifier_predefined_learning_goal_goalid' => $predefinedlgrecord->goalid,
                    'amplifier_predefined_learning_topic_label' => $predefinedlgrecord->topictitle,
                    'amplifier_predefined_learning_goal_label' => $predefinedlgrecord->goaltitle,
                ]
            );

            $topicid = $predefinedlgrecord->topicid;

        }

        $renderedlgselection .= $OUTPUT->render_from_template(
            'mod_amplifier/widget/amplifier-learning-goal-selection',
            [
                'amplifier_learning_goal_selection_headline' => get_string('amplifier_learning_goal_selection_headline',
                'mod_amplifier'),
                'amplifier_learning_goal_selection_intro' => get_string('amplifier_learning_goal_selection_intro',
                'mod_amplifier'),
                'predefined_learning_goals' => $renderedsellgs,
                'amplifier_button_next' => get_string('amplifier_button_next', 'mod_amplifier'),
            ]
        );

        $templatecontext['participantcode'] = $this->participantcode;
        $templatecontext['reflective_question_fieldsets'] = $renderedrq;

        $templatecontext['learning_goal_selection_fieldset'] = $renderedlgselection;

        $templatecontext['amplifier_welcome_headline'] = get_string('amplifier_welcome_headline', 'mod_amplifier');
        $templatecontext['amplifier_welcome_text_1'] = get_string('amplifier_welcome_text_1', 'mod_amplifier');
        $templatecontext['amplifier_welcome_text_2'] = get_string('amplifier_welcome_text_2', 'mod_amplifier');
        $templatecontext['amplifier_welcome_text_3'] = get_string('amplifier_welcome_text_3', 'mod_amplifier');
        $templatecontext['amplifier_setup_submit_headline'] = get_string('amplifier_setup_submit_headline', 'mod_amplifier');
        $templatecontext['amplifier_setup_submit_text_1'] = get_string('amplifier_setup_submit_text_1', 'mod_amplifier');

        $templatecontext['amplifier_setup_participantcode_headline'] = get_string('amplifier_setup_participantcode_headline',
        'mod_amplifier');
        $templatecontext['amplifier_setup_participantcode_description'] = get_string('amplifier_setup_participantcode_description',
        'mod_amplifier');
        $templatecontext['amplifier_setup_participantcode_input_label'] = get_string('amplifier_setup_participantcode_input_label',
        'mod_amplifier');

        $templatecontext['amplifier'] = get_string('amplifier', 'mod_amplifier');

        $templatecontext['amplifier_button_submit'] = get_string('amplifier_button_submit_setup', 'mod_amplifier');
        $templatecontext['amplifier_button_next'] = get_string('amplifier_button_next', 'mod_amplifier');
        $templatecontext['amplifier_button_previous'] = get_string('amplifier_button_previous', 'mod_amplifier');

        $templatecontext['amplifier_setup_finished'] = $this->finished;

        $templatecontext['usergoals'] = $this->usergoals;

        return $OUTPUT->render_from_template(
            'mod_amplifier/widget/amplifier-widget',
            $templatecontext
        );

    }

}
