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
 * Unit tests for the submit_reflections function.
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
 * Unit tests for the submit_reflections function.
 *
 * @package    mod_amplifier
 * @category   external
 * @copyright  2025 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 */
final class submit_reflections_test extends externallib_advanced_testcase {
    use \mod_amplifier\utils;
    /**
     * Test submit_reflections to trigger exception by calling the function as teacher
     * @return void
     *
     * @covers \mod_amplifier\external\submit_reflections::execute
     * @covers \mod_amplifier\external\submit_reflections::execute_parameters
     */
    public function test_submit_reflections_teacher_exc(): void {
        global $DB;
        $setup = $this->setup_widget(true);

        // Submit reflection.
        $this->expectException(\required_capability_exception::class);
        $res = submit_reflections::execute('Reflection', 1, $setup->instance->id);
    }

    /**
     * Test submit_reflections to trigger the exception of not having done the setup
     * @return void
     *
     * @covers \mod_amplifier\external\submit_reflections::execute
     * @covers \mod_amplifier\external\submit_reflections::execute_returns
     * @covers \mod_amplifier\external\submit_reflections::execute_parameters
     */
    public function test_submit_reflections_no_setup_exc(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit reflection.
        $this->expectException(\invalid_parameter_exception::class);
        $res = submit_reflections::execute('Reflection', 1, $setup->instance->id);
    }

    /**
     * Test submit_reflections
     * @return void
     *
     * @covers \mod_amplifier\external\submit_reflections::execute
     * @covers \mod_amplifier\external\submit_reflections::execute_returns
     * @covers \mod_amplifier\external\submit_reflections::execute_parameters
     */
    public function test_submit_reflections(): void {
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

        $this->assertSame(2, count($amplifiergoalids));

        $res = submit_reflections::execute('', $amplifiergoalids[0], $setup->instance->id);
        $res = external_api::clean_returnvalue(submit_setup::execute_returns(), $res);
        $this->assertSame("Reflection is empty, ignored.", $res);

        $reflections = ["Test 1", "Test 2"];
        foreach ($reflections as $reflection) {
            $res = submit_reflections::execute($reflection, $amplifiergoalids[0], $setup->instance->id);
            $res = external_api::clean_returnvalue(submit_setup::execute_returns(), $res);
            $this->assertSame("OK", $res);
        }
        $res = submit_reflections::execute("Test 3", $amplifiergoalids[1], $setup->instance->id);
        $res = external_api::clean_returnvalue(submit_setup::execute_returns(), $res);
        $this->assertSame("OK", $res);

        $data2 = array_values($DB->get_records('amplifier_reflections', ['amplifiergoalid' => $amplifiergoalids[0]]));
        $this->assertSame(2, count($data2));

        $thi->assertSame($reflections[0], $data2[0]->response);
        $thi->assertSame($reflections[1], $data2[1]->response);

        $data = array_values($DB->get_records('amplifier_reflections', ['amplifiergoalid' => $amplifiergoalids[1]]));
        $this->assertSame(1, count($data));
        $thi->assertSame("Test 3", $data[0]->response);
    }
}
