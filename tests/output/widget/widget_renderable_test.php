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
 * Training Amplifier widget renderable Test
 *
 * @package   mod_amplifier
 * @copyright 2025 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_amplifier\output\widget;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/amplifier/tests/utils.php');

use mod_amplifier\output\widget\renderer;
use mod_amplifier\output\widget\widget_renderable;
use mod_amplifier\external\save_reminder;
use mod_amplifier\external\submit_setup;
use core_external\external_api;
use renderer_base;
use core_renderer;

/**
 * Training Amplifier Widget Renderable Test
 *
 * @package   mod_amplifier
 * @copyright 2025 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 */
final class widget_renderable_test extends \advanced_testcase {
    use \mod_amplifier\utils;
    /**
     * Test rendering a widget as a teacher.
     *
     * @covers \mod_amplifier\output\widget\renderer
     * @covers \mod_amplifier\output\widget\widget_renderable::__construct
     * @covers \mod_amplifier\output\widget\widget_renderable::export_for_template
     */
    public function test_render_widget_teacher(): void {
        $setup = $this->setup_widget(true);
        $renderer = $this->get_renderer();

        $mockrenderable = new widget_renderable($setup->instance->id);
        $output = $renderer->render_widget($mockrenderable);

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('data-instance-id="' . $setup->instance->id . '"', $output);
        $this->assertStringContainsString(
          get_string('template:setup:headline', 'mod_amplifier'),
          $output
        );
        $this->assertStringContainsString(
          get_string('template:setup:teacher', 'mod_amplifier'),
          $output
        );
    }

    /**
     * Test rendering a widget as a student without the setup.
     *
     * @covers \mod_amplifier\output\widget\renderer
     * @covers \mod_amplifier\output\widget\widget_renderable::__construct
     * @covers \mod_amplifier\output\widget\widget_renderable::export_for_template
     */
    public function test_render_widget_no_setup(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        $renderer = $this->get_renderer();
        $mockrenderable = new widget_renderable($setup->instance->id);
        $widget = $renderer->render_widget($mockrenderable);
        $this->assertStringNotContainsString(
          get_string('template:setup:teacher', 'mod_amplifier'),
          $widget
        );

        // Student strings.
        $this->assertStringContainsString(
          get_string('template:setup:headline', 'mod_amplifier'), $widget);
        $this->assertStringContainsString(
          get_string('template:setup:text_1', 'mod_amplifier'), $widget);
        $this->assertStringContainsString(
          get_string('template:setup:text_2', 'mod_amplifier'), $widget);
        foreach ($setup->taxonomy->children as $topic) {
            $this->assertStringContainsString($topic->name, $widget);
            foreach ($topic->children as $goal) {
                $this->assertStringContainsString($goal->name, $widget);
            }
        }
    }
    /**
     * Test rendering a widget as a student who has completed the setup.
     *
     * @covers \mod_amplifier\output\widget\renderer
     * @covers \mod_amplifier\output\widget\widget_renderable::__construct
     * @covers \mod_amplifier\output\widget\widget_renderable::export_for_template
     */
    public function test_render_widget_setup_done(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit setup.
        $submitdata = $this->submit_setup($setup);
        $taxonomy = $submitdata->taxonomy;
        $firsttopic = $taxonomy->children[0];

        // Set reminder to check that it is added to template.
        $reminderdata = $this->save_reminder($setup->instance->id);

        $renderer = $this->get_renderer();
        $mockrenderable = new widget_renderable($setup->instance->id);
        $widget = $renderer->render_widget($mockrenderable);
        // Teacher.
        $this->assertStringNotContainsString(
          get_string('template:setup:teacher', 'mod_amplifier'),
          $widget
        );
        // Setudent no setup.
        $this->assertStringNotContainsString(
          get_string('template:setup:headline', 'mod_amplifier'), $widget);
        $this->assertStringNotContainsString(
          get_string('template:setup:text_1', 'mod_amplifier'), $widget);
        $this->assertStringNotContainsString(
          get_string('template:setup:text_2', 'mod_amplifier'), $widget);
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
            $this->assertStringContainsString(get_string($langstring, 'mod_amplifier'), $widget);
        }

        foreach ($firsttopic->children as $goal) {
            $this->assertStringContainsString($goal->name, $widget);
            $this->assertStringContainsString($firsttopic->name . ' - ' . $goal->name, $widget);
        }
        $secondtopic = $taxonomy->children[1];
        foreach ($secondtopic->children as $goal) {
            $this->assertStringNotContainsString($goal->name, $widget);
            $this->assertStringNotContainsString($secondtopic->name . ' - ' . $goal->name, $widget);
        }
        $this->assertStringContainsString('data-startdate="' . $reminderdata->startdate . '"', $widget);
        $this->assertStringContainsString('data-enddate="' . $reminderdata->enddate . '"', $widget);
        $this->assertStringContainsString('data-reminder-hour="' . $reminderdata->reminderhour . '"', $widget);
        $this->assertStringContainsString('data-reminder-minute="' . $reminderdata->reminderminute . '"', $widget);
        $this->assertStringContainsString('data-reminderfrequency="' . $reminderdata->frequency . '"', $widget);
    }

    /**
     * Helper function to get the renderer.
     */
    private function get_renderer() {
        global $PAGE;
        return $PAGE->get_renderer('mod_amplifier', 'widget');
    }
}

