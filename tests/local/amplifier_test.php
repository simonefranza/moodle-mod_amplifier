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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/amplifier/tests/providerhelper.php');
require_once($CFG->dirroot . '/mod/amplifier/tests/utils.php');
require_once($CFG->dirroot . '/mod/learninggoalwidget/classes/local/taxonomy.php');

use mod_amplifier\local\amplifier_controller;
use mod_learninggoalwidget\local\taxonomy;
use mod_amplifier\external\submit_setup;
use mod_amplifier\external\save_reminder;
use core_external\external_api;
use stdClass;

/**
 * Amplifier Test
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class amplifier_test extends \advanced_testcase {
    use \mod_amplifier\utils;
    /**
     * Testing creation of amplfier widget
     * @return void
     *
     * @covers \mod_amplifier\local\amplifier::__construct
     */
    public function test_creation(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $taxonomy = json_decode(taxonomy::get_taxonomy_as_json($setup->lgwinstance->id));
        for ($i = 0; $i < 2; $i++) {
            $this->check_topic($taxonomy->children[$i], $i, $i + 1, 2, true);
        }

        $record = $DB->get_record('amplifier', ['id' => $setup->instance->id]);
        $this->assertSame($record->name, 'Training Amplifier');
        $this->assertSame($record->learninggoalwidgetid, $setup->lgwinstance->id);
        $amp = new amplifier($setup->instance->id);
        $this->assertNotNull($amp);
    }

    /**
     * Render the widget for a teacher
     * @return void
     *
     * @covers \mod_amplifier\local\amplifier::render
     * @covers \mod_amplifier\local\amplifier::add_strings
     */
    public function test_render_teacher(): void {
        $setup = $this->setup_widget(true);
        $amp = new amplifier($setup->instance->id);
        $context['instanceId'] = $setup->instance->id;
        $widget = $amp->render($context);
        $this->assertStringContainsString(
          get_string('template:setup:headline', 'mod_amplifier'),
          $widget['teacher_headline']
        );
        $this->assertStringContainsString(
          get_string('template:setup:teacher', 'mod_amplifier'),
          $widget['teacher_text']
        );
    }

    /**
     * Render the widget for a student who has not done the setup yet
     * @return void
     *
     * @covers \mod_amplifier\local\amplifier::render
     * @covers \mod_amplifier\local\amplifier::render_no_setup
     * @covers \mod_amplifier\local\amplifier::add_strings
     */
    public function test_render_student_no_setup(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        $amp = new amplifier($setup->instance->id);
        $context['instanceId'] = $setup->instance->id;
        $widget = $amp->render($context);
        // Does not contain teacher string.
        $this->assertTrue(!isset($widget['teacher_text']));

        // Student strings.
        $this->assertStringContainsString(
          get_string('template:setup:headline', 'mod_amplifier'), $widget['amplifier_welcome_headline']);
        $this->assertStringContainsString(
          get_string('template:setup:text_1', 'mod_amplifier'), $widget['amplifier_welcome_text_1']);
        $this->assertStringContainsString(
          get_string('template:setup:text_2', 'mod_amplifier'), $widget['amplifier_welcome_text_2']);
        foreach ($setup->taxonomy->children as $topic) {
            $this->assertStringContainsString($topic->name, $widget['predefined_learning_goals']);
            foreach ($topic->children as $goal) {
                $this->assertStringContainsString($goal->name, $widget['predefined_learning_goals']);
            }
        }
    }

    /**
     * Render the widget for a student who has completed the setup
     * @return void
     *
     * @covers \mod_amplifier\local\amplifier::render
     * @covers \mod_amplifier\local\amplifier::render_setup_done
     * @covers \mod_amplifier\local\amplifier::add_learninggoal_context
     * @covers \mod_amplifier\local\amplifier::add_reflection_context
     * @covers \mod_amplifier\local\amplifier::add_reminder_context
     * @covers \mod_amplifier\local\amplifier::add_strings
     * @covers \mod_amplifier\external\submit_setup::execute
     * @covers \mod_amplifier\external\submit_setup::execute_parameters
     * @covers \mod_amplifier\external\submit_setup::execute_returns
     */
    public function test_render_student_setup_done(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit setup.
        $taxonomy = $this->get_taxonomy($setup->lgwinstance->id);
        $firsttopic = $taxonomy->children[0];
        $goals = [
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $firsttopic->children[0]->goalid],
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $firsttopic->children[1]->goalid],
        ];
        $submission = submit_setup::execute($setup->instance->id, json_encode($goals));
        $submission = external_api::clean_returnvalue(submit_setup::execute_returns(), $submission);
        $this->assertSame("OK", $submission);

        // Set reminder to check that it is added to template.
        $amplifiergoalids = $DB->get_fieldset_select(
          'amplifier_goals',
          'id',
          'userid = :userid',
          ['userid' => $student->id]
        );
        $this->assertSame(2, count($amplifiergoalids));
        $reminderdata = (object)[
            'startdate' => 0,
            'enddate' => 200000,
            'reminderhour' => 10,
            'reminderminute' => 10,
            'frequency' => 0,
            'amplifiergoalid' => $amplifiergoalids[0],
        ];
        $res = save_reminder::execute(
            $reminderdata->startdate,
            $reminderdata->enddate,
            $reminderdata->reminderhour,
            $reminderdata->reminderminute,
            $reminderdata->frequency,
            $reminderdata->amplifiergoalid,
            $setup->instance->id
        );
        $res = external_api::clean_returnvalue(save_reminder::execute_returns(), $res);
        $this->assertSame("OK", $res);

        $amp = new amplifier($setup->instance->id);
        $context['instanceId'] = $setup->instance->id;
        $widget = $amp->render($context);
        // Does not contain teacher string.
        $this->assertTrue(!isset($widget['teacher_headline']));
        $this->assertTrue(!isset($widget['teacher_text']));
        // Does not contain student setup strings.
        $this->assertTrue(!isset($widget['amplifier_welcome_headline']));
        $this->assertTrue(!isset($widget['amplifier_welcome_text_1']));
        $this->assertTrue(!isset($widget['amplifier_welcome_text_2']));

        // Contains student strings.
        $contained = [
            'template:reflection:headline',
            'template:reflection:text_1',
            'template:general:save',
            'template:reminder:headline',
            'template:reminder:frequency:daily',
            'template:reminder:frequency:weekly',
            'template:reminder:frequency:monthly',
            'template:reminder:date:start',
            'template:reminder:date:end',
            'template:reminder:label:time',
            'template:general:save',
            'template:reflection:placeholder',
        ];
        foreach ($contained as $langstring) {
            $this->assertStringContainsString(get_string($langstring, 'mod_amplifier'), $widget['usergoals']);
        }

        foreach ($firsttopic->children as $goal) {
            $this->assertStringContainsString($goal->name, $widget['usergoals']);
            $this->assertStringContainsString($firsttopic->name . ' - ' . $goal->name, $widget['usergoals']);
        }
        $secondtopic = $taxonomy->children[1];
        foreach ($secondtopic->children as $goal) {
            $this->assertStringNotContainsString($goal->name, $widget['usergoals']);
            $this->assertStringNotContainsString($secondtopic->name . ' - ' . $goal->name, $widget['usergoals']);
        }
        $this->assertStringContainsString('data-startdate="' . $reminderdata->startdate . '"', $widget['usergoals']);
        $this->assertStringContainsString('data-enddate="' . $reminderdata->enddate . '"', $widget['usergoals']);
        $this->assertStringContainsString('data-reminder-hour="' . $reminderdata->reminderhour . '"', $widget['usergoals']);
        $this->assertStringContainsString('data-reminder-minute="' . $reminderdata->reminderminute . '"', $widget['usergoals']);
        $this->assertStringContainsString('data-reminderfrequency="' . $reminderdata->frequency . '"', $widget['usergoals']);
    }
}
