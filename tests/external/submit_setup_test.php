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
 * Unit tests for the submit_setup function.
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
 * Unit tests for the submit_setup function.
 *
 * @package    mod_amplifier
 * @category   external
 * @copyright  2025 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 */
final class submit_setup_test extends externallib_advanced_testcase {
    use \mod_amplifier\utils;
    /**
     * Test submit_setup to trigger exception by calling the function as teacher
     * @return void
     *
     * @covers \mod_amplifier\external\submit_setup::execute
     * @covers \mod_amplifier\external\submit_setup::execute_parameters
     */
    public function test_submit_setup_teacher_exc(): void {
        $setup = $this->setup_widget(true);

        // Submit setup.
        $taxonomy = $this->get_taxonomy($setup->lgwinstance->id);
        $firsttopic = $taxonomy->children[0];
        $goals = [
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $firsttopic->children[0]->goalid],
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $firsttopic->children[1]->goalid],
        ];
        $this->expectException(\required_capability_exception::class);
        submit_setup::execute($setup->instance->id, json_encode($goals));
    }

    /**
     * Test submit_setup
     * @return void
     *
     * @covers \mod_amplifier\external\submit_setup::execute
     * @covers \mod_amplifier\external\submit_setup::execute_returns
     * @covers \mod_amplifier\external\submit_setup::execute_parameters
     */
    public function test_submit_setup(): void {
        global $DB;
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        // Submit setup.
        $taxonomy = $this->get_taxonomy($setup->lgwinstance->id);
        $firsttopic = $taxonomy->children[0];
        $wronggoalid = $firsttopic->children[0]->goalid + $firsttopic->children[1]->goalid;
        $goals = [
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $firsttopic->children[0]->goalid],
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $firsttopic->children[1]->goalid],
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $wronggoalid],
        ];
        $submission = submit_setup::execute($setup->instance->id, json_encode($goals));
        $submission = external_api::clean_returnvalue(submit_setup::execute_returns(), $submission);
        $this->assertSame("OK", $submission);

        $data = array_values($DB->get_records('amplifier_goals', ['amplifierid' => $setup->instance->id]));
        $this->assertSame(count($data), 2);
        for ($i = 0; $i < 2; $i++) {
            $this->assertSame((int)($goals[$i]->goalid), (int)($data[$i]->lgwgoalid));
            $this->assertSame((int)($setup->instance->id), (int)($data[$i]->amplifierid));
            $this->assertSame((int)($student->id), (int)($data[$i]->userid));
        }

        // Trigger exception by doing setup again with the same user.
        $this->expectException(\moodle_exception::class);
        $submission = submit_setup::execute($setup->instance->id, json_encode($goals));
    }

    /**
     * Test submit_setup by creating two training amplifiers
     * @return void
     *
     * @covers \mod_amplifier\external\submit_setup::execute
     * @covers \mod_amplifier\external\submit_setup::execute_returns
     * @covers \mod_amplifier\external\submit_setup::execute_parameters
     */
    public function test_submit_setup_different_modules(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Submit setup.
        $this->submit_setup($setup);

        // Create new training amplifier.
        $options = [
            'course' => $setup->course->id,
            'name' => 'Training Amplifier',
            'learninggoalwidgetid' => $setup->lgwinstance->id,
        ];
        $newamp = $this->getDataGenerator()->create_module('amplifier', $options);
        $data = new \stdClass;
        $data->lgwinstance = new \stdClass;
        $data->lgwinstance->id = $setup->lgwinstance->id;
        $data->instance = $newamp;
        $this->submit_setup($data);
    }
    /**
     * Test submit_setup by triggering the exception once LGW is removed.
     * @return void
     *
     * @covers \mod_amplifier\external\submit_setup::execute
     * @covers \mod_amplifier\external\submit_setup::execute_parameters
     * @covers \mod_amplifier\local\amplifier::__construct
     * @covers \mod_amplifier\local\amplifier::is_lgw_valid
     */
    public function test_submit_setup_no_lgw_exc(): void {
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        // Delete LGW.
        $this->mark_lgw_deleted($setup->lgwinstance->id);

        // Submit setup.
        $this->expectException(\moodle_exception::class);
        $this->submit_setup($setup);
    }
}
