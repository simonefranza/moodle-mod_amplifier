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
     * instance id
     *
     * @var int
     */
    private $instanceid;

    /**
     * Related learninggoalwidgetid
     *
     * @var int
     */
    private $learninggoalwidgetid;

    /**
     * ctor of widget_renderable
     *
     * @param number $instanceid
     */
    public function __construct($instanceid) {
        global $DB, $OUTPUT;

        $this->instanceid = $instanceid;
        $res = $DB->get_record('amplifier', ['id' => $instanceid], 'learninggoalwidgetid');
        if ($res !== false) {
            $this->learninggoalwidgetid = $res->learninggoalwidgetid;
        }
    }


    /**
     * Renders the template to allow the users to set reminders and do the reflection
     *
     * @return string
     */
    private function render_training_goals() {
        global $OUTPUT, $USER, $DB;
        $out = '';
        $sqlstmt = "SELECT lgwgoals.id as goalid,
                           lgwgoals.title as goaltitle,
                           lgwtopics.id as topicid,
                           lgwtopics.title as topictitle,
                           ampgoals.id as amplifiergoalid
                      FROM {amplifier_goals} ampgoals
                 LEFT JOIN {learninggoalwidget_goals} lgwgoals ON ampgoals.lgwgoalid = lgwgoals.id
                 LEFT JOIN {learninggoalwidget_topics} lgwtopics ON lgwgoals.topicid = lgwtopics.id
                     WHERE ampgoals.userid = :userid
                       AND ampgoals.amplifierid = :amplifierid
                       AND lgwgoals.learninggoalwidgetid = :lgwid
                       AND lgwtopics.learninggoalwidgetid = lgwgoals.learninggoalwidgetid";
        $params = [
            "userid" => $USER->id,
            "amplifierid" => $this->instanceid,
            "lgwid" => $this->learninggoalwidgetid,
        ];
        $usergoalrecords = $DB->get_records_sql($sqlstmt, $params);
        foreach ($usergoalrecords as $usergoal) {
            $renderedrq = "";
            $templatecontext = [];
            $params = [
                "amplifiergoalid" => $usergoal->amplifiergoalid,
            ];
            $reminderrecord = $DB->get_record('amplifier_reminders', $params);
            if ($reminderrecord) {
                $templatecontext['reminder'] = true;
                $templatecontext['reminderstartdate'] = $reminderrecord->startdate;
                $templatecontext['reminderenddate'] = $reminderrecord->enddate;
                $templatecontext['reminderhour'] = $reminderrecord->reminderhour;
                $templatecontext['reminderminute'] = $reminderrecord->reminderminute;
                $templatecontext['reminderfrequency'] = $reminderrecord->frequency;
            }

            $templatecontext['topictitle'] = $usergoal->topictitle;
            $templatecontext['goaltitle'] = $usergoal->goaltitle;
            $templatecontext['topicid'] = $usergoal->topicid;
            $templatecontext['goalid'] = $usergoal->goalid;
            $templatecontext['amplifiergoalid'] = $usergoal->amplifiergoalid;
            $templatecontext['amplifier_calendar'] = $OUTPUT->image_url('amplifier_calendar', 'amplifier');

            $renderedrq .= $OUTPUT->render_from_template(
                'mod_amplifier/widget/amplifier-reflective-question',
                [
                    'amplifier_reflective_question_topicid' => $usergoal->topicid,
                    'amplifier_reflective_question_goalid' => $usergoal->goalid,
                    'amplifier_reflective_question_questiontext' => $usergoal->goaltitle,
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
            $currentyear = (int) date("Y");
            for ($i = 0; $i < 4; $i++) {
                $templatecontext['yearOptions'][] = ['value' => $currentyear + $i, 'display' => $currentyear + $i];
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

            $out .= $OUTPUT->render_from_template(
                'mod_amplifier/widget/amplifier-user-goal',
                $templatecontext);
        }
        return $out;
    }


    /**
     * Renders the setup view to select the goals
     *
     * @return string
     */
    private function render_goals_selection() {
        global $DB, $OUTPUT;
        $out = "";
        $lasttopicid = 0;
        $sqlstmt = "SELECT goals.id as goalid, goals.title as goaltitle, goals.topicid, topics.title as topictitle
                      FROM {learninggoalwidget_goals} goals
                 LEFT JOIN {learninggoalwidget_topics} topics ON goals.topicid = topics.id
                     WHERE goals.learninggoalwidgetid = :lgwid
                  ORDER BY topics.ranking ASC, goals.ranking ASC";
        $predefinedlgsrecords = $DB->get_records_sql($sqlstmt, ['lgwid' => $this->learninggoalwidgetid]);

        foreach ($predefinedlgsrecords as $predefinedlgrecord) {
            if ($lasttopicid != $predefinedlgrecord->topicid) {
                $out .= $OUTPUT->render_from_template(
                    'mod_amplifier/widget/amplifier-predefined-learning-topic',
                    [
                      'amplifier_predefined_learning_topic_label' => $predefinedlgrecord->topictitle,
                    ]
                );
                $lasttopicid = $predefinedlgrecord->topicid;
            }

            $out .= $OUTPUT->render_from_template(
              'mod_amplifier/widget/amplifier-predefined-learning-goal',
              [
                'amplifier_predefined_learning_goal_topicid' => $predefinedlgrecord->topicid,
                'amplifier_predefined_learning_goal_goalid' => $predefinedlgrecord->goalid,
                'amplifier_predefined_learning_goal_label' => $predefinedlgrecord->goaltitle,
              ]
            );
        }
        return $out;
    }

    /**
     * render the training amplifier widget
     * @param [type] $templatecontext
     */
    public function render($templatecontext) {
        global $OUTPUT, $USER, $DB;
        $numusergoals = $DB->count_records('amplifier_goals', ['userid' => $USER->id]);

        if (!$numusergoals) {
            // User has not setup the training amplifier yet.
            // Needed for template amplifier-setup.mustache.
            $templatecontext['amplifier_welcome_headline'] = get_string('amplifier_welcome_headline', 'mod_amplifier');
            $templatecontext['amplifier_welcome_text_1'] = get_string('amplifier_welcome_text_1', 'mod_amplifier');
            $templatecontext['amplifier_welcome_text_2'] = get_string('amplifier_welcome_text_2', 'mod_amplifier');
            $templatecontext['amplifier_setup_submit_text_1'] = get_string('amplifier_setup_submit_text_1', 'mod_amplifier');
            $templatecontext['amplifier_button_submit'] = get_string('amplifier_button_submit_setup', 'mod_amplifier');
            $templatecontext['amplifier_setup_finished'] = 0;
            // Component to select goals during setup.
            $templatecontext['predefined_learning_goals'] = $this->render_goals_selection();
        } else {
            // User completed setup already.
            $templatecontext['amplifier_setup_finished'] = 1;
            // Component shown once the setup is done (list of goals with possibility
            // to choose reminder or reflection).
            // Component is amplifier-user-goal.
            $templatecontext['usergoals'] = $this->render_training_goals();
        }

        return $OUTPUT->render_from_template(
            'mod_amplifier/widget/amplifier-widget',
            $templatecontext
        );

    }
}
