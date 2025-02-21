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
 * Unit tests for the save_reminder function.
 *
 * @package    mod_amplifier
 * @copyright  2025 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_amplifier\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/amplifier/tests/utils.php');

use externallib_advanced_testcase;
use core_external\external_api;

/**
 * Unit tests for the save_reminder function.
 *
 * @package    mod_amplifier
 * @category   external
 * @copyright  2025 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 */
final class save_reminder_test extends externallib_advanced_testcase {
    use \mod_amplifier\utils;
    /**
     * Test save_reminder to trigger exception by calling the function as teacher
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_teacher_exc(): void {
        $setup = $this->setup_widget(true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\required_capability_exception::class);
        save_reminder::execute($now, $now, 0, 0, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign invalid start and end date
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_date_exc(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        save_reminder::execute($now + 2000, $now, 0, 0, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign reminderhour < 0
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_hour_exc_1(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        save_reminder::execute($now, $now + 2000, -1, 0, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign reminderhour > 23
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_hour_exc_2(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        save_reminder::execute($now, $now + 2000, 24, 0, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign reminderminute < 0
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_minute_exc_1(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        save_reminder::execute($now, $now + 2000, 19, -10, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign reminderminute > 59
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_minute_exc_2(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        save_reminder::execute($now, $now + 2000, 19, 65, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign a frequency < 0
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_frequency_exc_1(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        save_reminder::execute($now, $now + 2000, 19, 22, 0, -1, $setup->instance->id);
    }

    /**
     * Test save_reminder usign a frequency > 2
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_frequency_exc_2(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        save_reminder::execute($now, $now + 2000, 19, 22, 0, 3, $setup->instance->id);
    }

    /**
     * Test save_reminder to trigger the exception of not having done the setup
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_no_setup_exc(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        save_reminder::execute($now, $now + 2000, 19, 25, 0, 34234, $setup->instance->id);
    }

    /**
     * Test save_reminder
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_returns
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder(): void {
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

        $amplifiergoalids = $DB->get_fieldset_select(
          'amplifier_goals',
          'id',
          'userid = :userid',
          ['userid' => $student->id]
        );

        $now = time();
        $testdata = (object)[
            'startdate' => [0, 430, 3242],
            'enddate' => [200000, 4300000, 342423],
            'reminderhour' => [10, 14, 22],
            'reminderminute' => [10, 4, 32],
            'frequency' => [0, 1, 2],
            'amplifiergoalid' => [$amplifiergoalids[0], $amplifiergoalids[1], $amplifiergoalids[0]],
        ];
        for ($i = 0; $i < 2; $i++) {
            $res = save_reminder::execute(
                $testdata->startdate[$i],
                $testdata->enddate[$i],
                $testdata->reminderhour[$i],
                $testdata->reminderminute[$i],
                $testdata->frequency[$i],
                $testdata->amplifiergoalid[$i],
                $setup->instance->id
            );
            $res = external_api::clean_returnvalue(save_reminder::execute_returns(), $res);
            $this->assertSame("OK", $res);
        }
        // Done separated on purpose to make sure that reminders are not mixed.
        for ($i = 0; $i < 2; $i++) {
            $data = array_values($DB->get_records(
                'amplifier_reminders',
                ['amplifiergoalid' => $testdata->amplifiergoalid[$i]]
            ));
            $this->assertSame(1, count($data));
            $this->assertSame($testdata->startdate[$i], $data[0]->startdate);
            $this->assertSame($testdata->enddate[$i], $data[0]->enddate);
            $this->assertSame($testdata->reminderhour[$i], $data[0]->reminderhour);
            $this->assertSame($testdata->reminderminute[$i], $data[0]->reminderminute);
            $this->assertSame($testdata->frequency[$i], $data[0]->frequency);
            $this->assertSame($testdata->amplifiergoalid[$i], $data[0]->amplifiergoalid);
        }

        // Update reminider.
        $res = save_reminder::execute(
            $testdata->startdate[2],
            $testdata->enddate[2],
            $testdata->reminderhour[2],
            $testdata->reminderminute[2], $testdata->frequency[2],
            $testdata->amplifiergoalid[2],
            $setup->instance->id
        );
        $res = external_api::clean_returnvalue(save_reminder::execute_returns(), $res);
        $this->assertSame("OK", $res);

        $data = array_values($DB->get_records(
            'amplifier_reminders',
            ['amplifiergoalid' => $testdata->amplifiergoalid[2]]
        ));
        $this->assertSame(1, count($data));
        $this->assertSame($testdata->startdate[2], $data[0]->startdate);
        $this->assertSame($testdata->enddate[2], $data[0]->enddate);
        $this->assertSame($testdata->reminderhour[2], $data[0]->reminderhour);
        $this->assertSame($testdata->reminderminute[2], $data[0]->reminderminute);
        $this->assertSame($testdata->frequency[2], $data[0]->frequency);
        $this->assertSame($testdata->amplifiergoalid[2], $data[0]->amplifiergoalid);
    }
}
