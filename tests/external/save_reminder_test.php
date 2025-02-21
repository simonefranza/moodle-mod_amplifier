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
        global $DB;
        $setup = $this->setup_widget(true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\required_capability_exception::class);
        $res = save_reminder::execute($now, $now, 0, 0, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign invalid start and end date
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_date_exc(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        $res = save_reminder::execute($now + 2000, $now, 0, 0, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign reminderhour < 0
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_hour_exc_1(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        $res = save_reminder::execute($now, $now + 2000, -1, 0, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign reminderhour > 23
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_hour_exc_2(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        $res = save_reminder::execute($now, $now + 2000, 24, 0, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign reminderminute < 0
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_minute_exc_1(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        $res = save_reminder::execute($now, $now + 2000, 19, -10, 0, 0, $setup->instance->id);
    }

    /**
     * Test save_reminder usign reminderminute > 59
     * @return void
     *
     * @covers \mod_amplifier\external\save_reminder::execute
     * @covers \mod_amplifier\external\save_reminder::execute_parameters
     */
    public function test_save_reminder_minute_exc_2(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $now = time() * 1000;
        $this->expectException(\invalid_parameter_exception::class);
        $res = save_reminder::execute($now, $now + 2000, 19, 65, 0, 0, $setup->instance->id);
    }
}
