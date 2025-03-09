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

namespace mod_amplifier\local;

/**
 * Training Amplifier Controller
 *
 * controls everything ;)
 *
 * @package   mod_amplifier
 * @copyright University of Technology Graz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class amplifier {
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
        global $DB;

        $this->instanceid = $instanceid;
        $res = $DB->get_record('amplifier', ['id' => $instanceid], 'learninggoalwidgetid');
        if ($res !== false) {
            if ($DB->record_exists('learninggoalwidget', ['id' => $res->learninggoalwidgetid])) {
                $this->learninggoalwidgetid = $res->learninggoalwidgetid;
                return;
            }
        }
        $this->learninggoalwidgetid = null;
    }

    /**
     * Fetches the localised strings and adds them to the context
     *
     * @param stdClass $strings key values of strings to fetch
     * @param array $context Template context
     * @return void
     */
    private function add_strings($strings, &$context) {
        foreach ($strings as $key => $value) {
            $context[$key] = get_string($value, 'mod_amplifier');
        }
    }

    /**
     * Adds the learning goal information to the context
     *
     * @param stdClass $usergoal User goal object
     * @param array $context Template context
     * @return void
     */
    private function add_learninggoal_context($usergoal, &$context) {
        $context['topictitle'] = $usergoal->topictitle;
        $context['goaltitle'] = $usergoal->goaltitle;
        $context['topicid'] = $usergoal->topicid;
        $context['goalid'] = $usergoal->goalid;
        $context['amplifiergoalid'] = $usergoal->amplifiergoalid;
    }

    /**
     * Renders the template to allow the users do the reflection
     *
     * @param stdClass $usergoal User goal object
     * @param array $context Template context
     * @return string
     */
    private function add_reflection_context($usergoal, &$context) {
        global $OUTPUT;
        $strings = [
            'amplifier_submit_reflections_headline' => 'template:reflection:headline',
            'amplifier_reflection_text_1' => 'template:reflection:text_1',
            'amplifier_button_submit' => 'template:general:save',
        ];
        $this->add_strings($strings, $context);

        $context['reflectivequestions'] = $OUTPUT->render_from_template(
            'mod_amplifier/widget/amplifier-reflective-question',
            [
                'amplifier_reflective_question_topicid' => $usergoal->topicid,
                'amplifier_reflective_question_goalid' => $usergoal->goalid,
                'amplifier_reflective_question_questiontext' => $usergoal->goaltitle,
                'amplifier_placeholder_thoughts' =>
                    get_string('template:reflection:placeholder', 'mod_amplifier'),
            ]
        );
    }

    /**
     * Adds the reminder information to the context
     *
     * @param number $amplifiergoalid amplifier_goals id
     * @param array $context Template context
     * @return void
     */
    private function add_reminder_context($amplifiergoalid, &$context) {
        global $DB, $OUTPUT;
        $params = ["amplifiergoalid" => $amplifiergoalid];
        $reminderrecord = $DB->get_record('amplifier_reminders', $params);

        if ($reminderrecord) {
            $context['reminder'] = true;
            $context['reminderstartdate'] = $reminderrecord->startdate;
            $context['reminderenddate'] = $reminderrecord->enddate;
            $context['reminderhour'] = $reminderrecord->reminderhour;
            $context['reminderminute'] = $reminderrecord->reminderminute;
            $context['reminderfrequency'] = $reminderrecord->frequency;
        }
        $context['amplifier_calendar'] = $OUTPUT->image_url('amplifier_calendar', 'amplifier');
        $strings = (object)[
            'amplifier_reminder_settings_headline' => 'template:reminder:headline',
            'amplifier_reminder_frequency_daily' => 'template:reminder:frequency:daily',
            'amplifier_reminder_frequency_weekly' => 'template:reminder:frequency:weekly',
            'amplifier_reminder_frequency_monthly' => 'template:reminder:frequency:monthly',
            'amplifier_reminder_settings_startdate_label' => 'template:reminder:date:start',
            'amplifier_reminder_settings_enddate_label' => 'template:reminder:date:end',
            'amplifier_reminder_settings_time_label' => 'template:reminder:label:time',
            'amplifier_button_submit_reflection' => 'template:general:save',
        ];
        $this->add_strings($strings, $context);

        $context['dayOptions'] = [];
        for ($i = 1; $i <= 31; $i++) {
            $context['dayOptions'][] = ['value' => $i, 'display' => $i];
        }
        $context['monthOptions'] = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthstrname = "template:reminder:month:" . sprintf('%02d', $i);
            $context['monthOptions'][] = [
                "value" => $i,
                "label" => get_string($monthstrname, 'mod_amplifier'),
            ];
        }
        $context['yearOptions'] = [];
        $currentyear = (int) date("Y");
        for ($i = 0; $i < 4; $i++) {
            $context['yearOptions'][] = ['value' => $currentyear + $i, 'display' => $currentyear + $i];
        }
        $context['hourOptions'] = [];
        for ($i = 0; $i < 24; $i++) {
            $context['hourOptions'][] = ['value' => $i, 'display' => $i];
        }
        $context['minuteOptions'] = [];
        for ($i = 0; $i < 4; $i++) {
            $context['minuteOptions'][] = [
                'value' => $i * 15,
                'display' => sprintf('%02d', $i * 15),
            ];
        }
    }

    /**
     * Renders the template to allow the users to set reminders and do the reflection
     *
     * @return string
     */
    private function render_setup_done() {
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
            $context = [];
            $this->add_learninggoal_context($usergoal, $context);
            $this->add_reflection_context($usergoal, $context);
            $this->add_reminder_context($usergoal->amplifiergoalid, $context);

            $out .= $OUTPUT->render_from_template(
                'mod_amplifier/widget/amplifier-user-goal',
                $context);
        }
        return $out;
    }


    /**
     * Renders the setup view to select the goals
     *
     * @return string
     */
    private function render_no_setup() {
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
     * @param [type] $context
     */
    public function render($context) {
        global $USER, $DB;

        // Capability check.
        $cm = get_coursemodule_from_instance('amplifier', $this->instanceid, 0, false, MUST_EXIST);
        $contextmodule = \context_module::instance($cm->id);
        require_capability('mod/amplifier:view', $contextmodule);
        $instancename = $DB->get_field('amplifier', 'name', ['id' => $this->instanceid]);
        $context['instancename'] = $instancename;

        if ($this->learninggoalwidgetid == null) {
            // LGW Instance does not exist.
            $context['data_missing'] = 1;
            $strings = [
                'amplifier_welcome_headline' => 'template:setup:headline',
                'amplifier_lgw_missing' => 'template:setup:lgw_missing',
            ];
            $this->add_strings($strings, $context);
            return $context;
        }
        $context['data_missing'] = 0;

        $isteacher = !has_capability('mod/amplifier:setupgoals', $contextmodule);
        $context['is_teacher'] = $isteacher;

        $numusergoals = $DB->count_records('amplifier_goals', [
          'userid' => $USER->id,
          'amplifierid' => $this->instanceid,
        ]);
        $strings = [];

        if ($isteacher) {
            // User hasn't setupgoals capability, aka is a teacher.
            $strings = [
                'teacher_headline' => 'template:setup:headline',
                'teacher_text' => 'template:setup:teacher',
            ];
        } else if (!$numusergoals) {
            // User has not setup the training amplifier yet.
            // Needed for template amplifier-setup.mustache.
            $context['amplifier_setup_finished'] = 0;
            $strings = [
                'amplifier_welcome_headline' => 'template:setup:headline',
                'amplifier_welcome_text_1' => 'template:setup:text_1',
                'amplifier_welcome_text_2' => 'template:setup:text_2',
                'amplifier_button_submit' => 'template:general:submit',
            ];
            // Component to select goals during setup.
            $context['predefined_learning_goals'] = $this->render_no_setup();
        } else {
            // User completed setup already.
            $context['amplifier_setup_finished'] = 1;
            // Component shown once the setup is done (list of goals with possibility
            // to choose reminder or reflection).
            // Component is amplifier-user-goal.
            $context['usergoals'] = $this->render_setup_done();
        }
        $this->add_strings($strings, $context);
        return $context;
    }
}
