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

        // Amplifier setup for this user exists?
        $params = [
            'course' => $this->courseid,
            'coursemodule' => $this->coursemoduleid,
            'instance' => $this->instanceid,
            'amp_user' => $this->userid
        ];
        $amplifierusersetup = $DB->get_record('amplifier_setup', $params);

        if ($amplifierusersetup !== false) {
            $this->finished = $amplifierusersetup->finished;
            $this->setupid = $amplifierusersetup->id;
            self::$topicshortname = $amplifierusersetup->reflectiontopicshortname;
            self::$goalstopicshortname = $amplifierusersetup->goalstopicshortname;
        } else {
            $amplifierusersetup = new \stdClass;
            $amplifierusersetup->amp_user = $this->userid;
            $amplifierusersetup->course = $this->courseid;
            $amplifierusersetup->coursemodule = $this->coursemoduleid;
            $amplifierusersetup->instance = $this->instanceid;
            $amplifierusersetup->reflectiontopicshortname = self::$topicshortname;
            $amplifierusersetup->goalstopicshortname = self::$goalstopicshortname;
            $amplifierusersetup->finished = 0;
            $this->setupid = $DB->insert_record('amplifier_setup', $amplifierusersetup);
            $this->finished = 0;
        }

        if ($this->finished) {
            $sqlstmt = "SELECT lgwgoals.id as goalid,
                               lgwgoals.title as goaltitle,
                               lgwtopics.id as topicid,
                               lgwtopics.title as topictitle
                          FROM {amplifier_setup_goals} ampgoals
                     LEFT JOIN {learninggoalwidget_goals} lgwgoals ON ampgoals.goal = lgwgoals.id
                     LEFT JOIN {learninggoalwidget_topics} lgwtopics ON ampgoals.topic = lgwtopics.id
                         WHERE ampgoals.course = :courseid
                           AND ampgoals.coursemodule = :coursemodule
                           AND ampgoals.instance = :instance
                           AND ampgoals.amp_user = :userid";
            $params = [
                "courseid" => $this->courseid,
                "coursemodule" => $this->coursemoduleid,
                "instance" => $this->instanceid,
                "userid" => $this->userid,
            ];
            $usergoalrecords = $DB->get_records_sql($sqlstmt, $params);
            foreach ($usergoalrecords as $usergoal) {

                $renderedrq = "";
                $templatecontext = [];
                $params = [
                    "goal" => $usergoal->goalid,
                    "course" => $this->courseid,
                    "coursemodule" => $this->coursemoduleid,
                    "instance" => $this->instanceid,
                    "amp_user" => $this->userid,
                ];
                $ampreminderrecord = $DB->get_record('amplifier_reminder', $params);
                if ($ampreminderrecord) {
                    $templatecontext['reminder'] = true;
                    $templatecontext['reminderstartdate'] = $ampreminderrecord->startdate;
                    $templatecontext['reminderenddate'] = $ampreminderrecord->enddate;
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
                $templatecontext['amplifiergoalid'] = $usergoal->goalid;
                $templatecontext['amplifier_calendar'] = $OUTPUT->image_url('amplifier_calendar', 'amplifier');

                $renderedrq .= $OUTPUT->render_from_template(
                    'mod_amplifier/widget/amplifier-reflective-question',
                    [
                        'amplifier_reflective_question_headline' => "",
                        'amplifier_reflective_question_intro' => "",
                        'amplifier_reflective_question_topicid' => $usergoal->topicid,
                        'amplifier_reflective_question_goalid' => $usergoal->goalid,
                        'amplifier_reflective_question_questiontext' => $usergoal->goaltitle,
                        'amplifier_button_next' => get_string('amplifier_button_next', 'mod_amplifier'),
                        'amplifier_placeholder_thoughts' => get_string('amplifier_placeholder_thoughts', 'mod_amplifier'),
                    ]
                );
                // This is presented before "Please click on "Save" to finish your reflecion session."
                // when clicking on the reflection of a goal.
                // Component amplifier-reflective-question.
                $templatecontext['reflectivequestions'] = $renderedrq;
                $templatecontext['amplifier_submit_reflections_headline'] =
                get_string('amplifier_submit_reflections_headline', 'mod_amplifier');
                $templatecontext['amplifier_reflection_text_1'] =
                get_string('amplifier_reflection_text_1', 'mod_amplifier');
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
                $templatecontext['amplifier_button_submit_reflection'] =
                get_string('amplifier_button_submit_reflection', 'mod_amplifier');

                $templatecontext['dayOptions'] = [];
                for ($i = 1; $i <= 31; $i++) {
                    $templatecontext['dayOptions'][] = ['value' => $i, 'display' => $i];
                }
                $templatecontext['monthOptions'] = [];
                for ($i = 1; $i <= 12; $i++) {
                    $monthstrname = "amplifier_reminder_settings_month" . $i . "_label";
                    $templatecontext['monthOptions'][] = [
                        "value" => $i,
                        "label" => get_string($monthstrname, 'mod_amplifier'),
                    ];
                }
                $templatecontext['yearOptions'] = [];
                $currentYear = (int) date("Y");
                for ($i = 0; $i < 4; $i++) {
                  $templatecontext['yearOptions'][] = ['value' => $currentYear + $i, 'display' => $currentYear + $i];
                }
                $templatecontext['hourOptions'] = [];
                for ($i = 0; $i < 24; $i++) {
                    $templatecontext['hourOptions'][] = ['value' => $i, 'display' => $i];
                }
                $templatecontext['minuteOptions'] = [];
                for ($i = 0; $i < 4; $i++) {
                    $templatecontext['minuteOptions'][] = [
                        'value' => $i * 15,
                        'display' => sprintf('%02d', $i * 15),
                    ];
                }

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
        $lgwid = $this->get_lgwid_from_course();

        $renderedlgselection = "";

        $renderedsellgs = "";
        $topicid = 0;
//        $sqlstmt = "SELECT goal.id as goalid, goal.title as goaltitle, topic.id as topicid, topic.title as topictitle
//                      FROM {learninggoalwidget_i_goals} goals, {learninggoalwidget_topic} topic, {learninggoalwidget_goal} goal
//                     WHERE goals.course = :goalscourse
//                       AND goals.topic = topic.id
//                       AND topic.title != :goalstopicshortname
//                       AND topic.title != :topicshortname
//                       AND goals.goal = goal.id
//                  ORDER BY topic.title";
        $sqlstmt = "SELECT goals.id as goalid, goals.title as goaltitle, goals.topicid, topics.title as topictitle
                      FROM {learninggoalwidget_goals} goals
                 LEFT JOIN {learninggoalwidget_topics} topics ON goals.topicid = topics.id
                     WHERE goals.learninggoalwidgetid = :lgwid
                       AND topics.title != :goalstopicshortname
                       AND topics.title != :topicshortname
                  ORDER BY topics.title";
        $params = [
            'lgwid' => $lgwid,
            'goalstopicshortname' => self::$goalstopicshortname,
            'topicshortname' => self::$topicshortname,
        ];
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

        // Component to select goals during setup.
        $templatecontext['learning_goal_selection_fieldset'] = $renderedlgselection;
        $templatecontext['predefined_learning_goals'] = $renderedsellgs;

        $templatecontext['amplifier_welcome_headline'] = get_string('amplifier_welcome_headline', 'mod_amplifier');
        $templatecontext['amplifier_welcome_text_1'] = get_string('amplifier_welcome_text_1', 'mod_amplifier');
        $templatecontext['amplifier_welcome_text_2'] = get_string('amplifier_welcome_text_2', 'mod_amplifier');
        $templatecontext['amplifier_setup_submit_text_1'] = get_string('amplifier_setup_submit_text_1', 'mod_amplifier');

        $templatecontext['amplifier'] = get_string('amplifier', 'mod_amplifier');

        $templatecontext['amplifier_button_submit'] = get_string('amplifier_button_submit_setup', 'mod_amplifier');
        $templatecontext['amplifier_button_next'] = get_string('amplifier_button_next', 'mod_amplifier');

        $templatecontext['amplifier_setup_finished'] = $this->finished;

        // Component shown once the setup is done (list of goals with possibility
        // to choose reminder or reflection).
        // Component is amplifier-user-goal.
        $templatecontext['usergoals'] = $this->usergoals;

        return $OUTPUT->render_from_template(
            'mod_amplifier/widget/amplifier-widget',
            $templatecontext
        );

    }

    /**
     * Function to get the learninggoalwidget ID from the course
     *
     * @return number
     */
    private function get_lgwid_from_course() {
        global $DB;
        // Get LGW instance ID from courseID.
        // TODO: add possibility in settings to choose which lgw.
        $stmt = "SELECT lgw.id as lgwid
                   FROM {course_modules} cm
                   JOIN {modules} m ON cm.module = m.id
                   JOIN {learninggoalwidget} lgw ON cm.instance = lgw.id
                  WHERE m.name = 'learninggoalwidget'
                    AND cm.course = :courseid";
        $params = ["courseid" => $this->courseid];
        $data = $DB->get_records_sql($stmt, $params);
        return reset($data)->lgwid;
    }

    /**
     * Function to get the reflection question
     *
     * @param number $lgwid ID of the learninggoalwidget
     * @param string $title Title of the topic
     * @return array
     */
    private function get_reflection_question($lgwid, $title) {
        global $DB;
        // TODO: remove once lgwid is saved in amplifier table, and topic id not shortname, add lgwid otherwise only based on course
//                $sqlstmt = "SELECT goal.id as goalid, goal.title as reflectionquestion, topic.id as topicid
//                    FROM {learninggoalwidget_i_goals} goals, {learninggoalwidget_topic} topic, {learninggoalwidget_goal} goal
//                    WHERE goals.course = ? AND goals.topic = topic.id AND topic.title = ? AND goals.goal = goal.id";
        $sqlstmt = "SELECT goals.id as goalid, goals.title as reflectionquestion, goals.topicid
                      FROM {learninggoalwidget_goals} goals
                 LEFT JOIN {learninggoalwidget_topics} topics ON goals.topicid = topics.id
                     WHERE goals.learninggoalwidgetid = :lgwid
                       AND topics.shortname = :topicshortname";
        $params = [
            "lgwid" => $lgwid,
            "topicshortname" => $title,
        ];
        var_dump($params);
        return $DB->get_records_sql($sqlstmt, $params);
    }

}
