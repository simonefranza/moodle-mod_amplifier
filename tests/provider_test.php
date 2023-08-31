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

namespace mod_amplifier;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/amplifier/externallib.php');

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use stdClass;
use mod_amplifier\privacy\provider;
use mod_amplifier\core\amplifier_controller;
use mod_amplifier_external;
use external_api;

/**
 * Amplifier Provider Test Case
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_test extends provider_testcase {


    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new \core_privacy\local\metadata\collection('amplifier');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(5, $itemcollection);

    }

    /**
     * Test that getting the contexts for a user works.
     */
    public function test_get_contexts_for_userid() {
        global $DB;
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $widgetinstance = $this->getDataGenerator()->create_module('amplifier', array('course' => $course1->id));
        $cm = get_coursemodule_from_instance('amplifier', $widgetinstance->id);
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $cmcontext1 = \context_module::instance($cm->id);

        // The user will be in these contexts.
        $usercontextids = [
            $cmcontext1,
        ];

        $this->submit_setup_without_relations($course1->id, $user1->id, $cm->id, $widgetinstance->id);

        $contextlist = provider::get_contexts_for_userid($user1->id);
        $this->assertEquals(count($usercontextids), count($contextlist->get_contextids()));
    }

    /**
     * Test returning a list of user IDs related to a context (assign).
     */
    public function test_get_users_in_context() {
        global $DB;

        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $widgetinstance = $this->getDataGenerator()->create_module('amplifier', array('course' => $course1->id));
        $cm = get_coursemodule_from_instance('amplifier', $widgetinstance->id);
        $cmcontext1 = \context_module::instance($cm->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user4->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user5->id, $course1->id, 'editingteacher');

        $this->submit_setup_without_relations($course1->id, $user1->id, $cm->id, $widgetinstance->id);

        // Create reflection.
        $reflectionrecord = new stdClass;
        $reflectionrecord->course = $course1->id;
        $reflectionrecord->coursemodule = $cm->id;
        $reflectionrecord->instance = $widgetinstance->id;
        $reflectionrecord->amp_user = $user1->id;
        $reflectionrecord->reflectedat = 123;
        $reflectionrecord->response = "reflection text";
        $reflectionrecord->id = $DB->insert_record('amplifier_reflection', $reflectionrecord);

        $userlist = new \core_privacy\local\request\userlist($cmcontext1, 'assign');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertTrue(in_array($user1->id, $userids));
        $this->assertFalse(in_array($user2->id, $userids));
        $this->assertFalse(in_array($user3->id, $userids));
        $this->assertFalse(in_array($user4->id, $userids));
        $this->assertFalse(in_array($user5->id, $userids));
    }

    /**
     * Test exporting data
     */
    public function test_export_user_data_student() {

        [$course1, $cm, $user1, $widgetinstance, $coursecontext, $cmcontext1, $amplifierusersetup, $selectedgoal]
        = $this->submit_setup_for_one_user();

        $this->assertNotFalse($amplifierusersetup);
        $this->assertEquals($amplifierusersetup->participantcode, "PARTICIPANTCODE");
        $this->assertEquals($amplifierusersetup->reflectiontopicshortname, "QUESTIONS_INITIAL");
        $this->assertEquals($amplifierusersetup->goalstopicshortname, "QUESTIONS_GOALS");
        $this->assertEquals($amplifierusersetup->finished, 1);

        // Create reminder.
        $reminderstarttime = (int)microtime(true);
        $lastnotificationdate = (int)microtime(true);
        $reminderendtime = (int)microtime(true);

        $reminder = $this->save_reminder($reminderstarttime,
        $reminderendtime,
        20,
        15,
        2,
        $lastnotificationdate,
        $selectedgoal->goalid,
        $user1->id,
        $course1->id,
        $cm->id,
        $widgetinstance->id,
        "PARTICIPANTCODE");

        // Create reflection.
        $reflectiontimestamp = (int)microtime(true);
        $response = mod_amplifier_external::submit_reflections(
            $reflectiontimestamp,
            json_encode(["some user reflection", "some other user reflection"]),
            $selectedgoal->goalid,
            $user1->id,
            $course1->id,
            $cm->id,
            $widgetinstance->id,
            "PARTICIPANTCODE"
        );

        $writer = writer::with_context($cmcontext1);
        $this->assertFalse($writer->has_any_data());

        // The student should have some text submitted.
        // Add the course context as well to make sure there is no error.
        $approvedlist = new approved_contextlist($user1, 'mod_amplifier', [$cmcontext1->id, $coursecontext->id]);
        provider::export_user_data($approvedlist);

        // Check export details.
        $selectedgoalsexport = $writer->get_data(['selectedgoals'])->selectedgoals;
        $this->assertNotNull($selectedgoalsexport);
        $this->assertEquals(count($selectedgoalsexport), 1);
        $this->assertEquals($selectedgoalsexport[0]->topictitle, "Test Title");
        $this->assertEquals($selectedgoalsexport[0]->goaltitle, "Goal Title");

        $remindersexport = $writer->get_data(['reminders'])->reminders;
        $this->assertNotNull($remindersexport);
        $this->assertEquals(count($remindersexport), 1);
        $this->assertEquals($remindersexport[0]->reminderhour, 20);
        $this->assertEquals($remindersexport[0]->reminderminute, 15);
        $this->assertEquals($remindersexport[0]->frequency, 2);

        $reflectionsexport = $writer->get_data(['reflections'])->reflections;
        $this->assertNotNull($reflectionsexport);
        $this->assertEquals(count($reflectionsexport), 2);
        $this->assertEquals($reflectionsexport[0]->response, "some user reflection");
        $this->assertEquals($reflectionsexport[1]->response, "some other user reflection");

    }

    /**
     * Test delete all users data wrt training amplifier widget
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        [$course1, $cm, $user1, $widgetinstance, $coursecontext, $cmcontext1, $amplifierusersetup, $selectedgoal]
        = $this->submit_setup_for_one_user();

        // Delete all user data for this assignment.
        provider::delete_data_for_all_users_in_context($cmcontext1);

        // Check all relevant tables.
        $records = $DB->get_records('amplifier_setup');
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup_reflection');
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup_goals');
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reminder');
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reflection');
        $this->assertEmpty($records);
    }

    /**
     * A test for deleting all user data for one user.
     */
    public function test_delete_data_for_user() {
        global $DB;

        [$course1, $cm, $user1, $widgetinstance, $coursecontext, $cmcontext1, $amplifierusersetup, $selectedgoal]
        = $this->submit_setup_for_one_user();

        // Create reminder.
        $reminderstarttime = (int)microtime(true);
        $lastnotificationdate = (int)microtime(true);
        $reminderendtime = (int)microtime(true);

        $reminder = $this->save_reminder($reminderstarttime,
        $reminderendtime,
        20,
        15,
        2,
        $lastnotificationdate,
        $selectedgoal->goalid,
        $user1->id,
        $course1->id,
        $cm->id,
        $widgetinstance->id,
        "PARTICIPANTCODE");

        // Create reflection.
        $reflectiontimestamp = (int)microtime(true);
        $response = mod_amplifier_external::submit_reflections(
            $reflectiontimestamp,
            json_encode(["some user reflection", "some other user reflection"]),
            $selectedgoal->goalid,
            $user1->id,
            $course1->id,
            $cm->id,
            $widgetinstance->id,
            "PARTICIPANTCODE"
        );

        // Delete user 1's data.
        $approvedlist = new approved_contextlist($user1, 'mod_amplifier', [$cmcontext1->id, $coursecontext->id]);
        provider::delete_data_for_user($approvedlist);

        // Check all relevant tables.
        $records = $DB->get_records('amplifier_setup', ['user' => $user1->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup_reflection', ['user' => $user1->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup_goals', ['user' => $user1->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reminder', ['user' => $user1->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reflection', ['user' => $user1->id]);
        $this->assertEmpty($records);
    }

    /**
     * A test for deleting all user data for a bunch of users.
     */
    public function test_delete_data_for_users() {
        global $DB;

        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');
        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, 'student');

        $course1 = $this->getDataGenerator()->create_course();
        $widgetinstance = $this->getDataGenerator()->create_module('amplifier', array('course' => $course1->id));
        $cm = get_coursemodule_from_instance('amplifier', $widgetinstance->id);
        $cmcontext1 = \context_module::instance($cm->id);

        $controller = new amplifier_controller($course1->id, $user1->id, $cm->id, $widgetinstance->id);
        [$amplifierusersetup, $selectedgoal] = providerhelper::submit_setup($course1->id, $cm->id, $widgetinstance->id, $user1->id);
        $controller2 = new amplifier_controller($course1->id, $user2->id, $cm->id, $widgetinstance->id);
        [$amplifierusersetup2, $selectedgoal2] = providerhelper::submit_setup($course1->id, $cm->id, $widgetinstance->id, $user2->id);
        $controller3 = new amplifier_controller($course1->id, $user3->id, $cm->id, $widgetinstance->id);
        [$amplifierusersetup3, $selectedgoal3] = providerhelper::submit_setup($course1->id, $cm->id, $widgetinstance->id, $user3->id);

        // Create reminder.
        $reminderstarttime = (int)microtime(true);
        $lastnotificationdate = (int)microtime(true);
        $reminderendtime = (int)microtime(true);

        $reminder = $this->save_reminder($reminderstarttime,
        $reminderendtime,
        20,
        15,
        2,
        $lastnotificationdate,
        $selectedgoal2->goalid,
        $user2->id,
        $course1->id,
        $cm->id,
        $widgetinstance->id,
        "PARTICIPANTCODE");

        // Create reflection.
        $reflectiontimestamp = (int)microtime(true);
        $response = mod_amplifier_external::submit_reflections(
            $reflectiontimestamp,
            json_encode(["some user reflection", "some other user reflection"]),
            $selectedgoal3->goalid,
            $user3->id,
            $course1->id,
            $cm->id,
            $widgetinstance->id,
            "PARTICIPANTCODE"
        );

        $userlist = new \core_privacy\local\request\approved_userlist($cmcontext1, 'amplifier', [$user2->id, $user3->id]);
        provider::delete_data_for_users($userlist);

        // Check all relevant tables.
        $records = $DB->get_records('amplifier_setup', ['user' => $user2->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup_reflection', ['user' => $user2->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup_goals', ['user' => $user2->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reminder', ['user' => $user2->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reflection', ['user' => $user2->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup', ['user' => $user3->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup_reflection', ['user' => $user3->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_setup_goals', ['user' => $user3->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reminder', ['user' => $user3->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reflection', ['user' => $user3->id]);
        $this->assertEmpty($records);
    }

    /**
     * testing class controller
     *
     * @param [type] $startdate
     * @param [type] $enddate
     * @param [type] $reminderhour
     * @param [type] $reminderminute
     * @param [type] $frequency
     * @param [type] $lastnotificationdate
     * @param [type] $goal
     * @param [type] $user
     * @param [type] $course
     * @param [type] $coursemodule
     * @param [type] $instance
     * @param [type] $participantcode
     *
     * @return void
     */
    protected function save_reminder($startdate,
    $enddate,
    $reminderhour,
    $reminderminute,
    $frequency,
    $lastnotificationdate,
    $goal,
    $user,
    $course,
    $coursemodule,
    $instance,
    $participantcode) {

        global $DB;

        $response = mod_amplifier_external::save_reminder(
            $startdate,
            $enddate,
            $reminderhour,
            $reminderminute,
            $frequency,
            $lastnotificationdate,
            $goal,
            $user,
            $course,
            $coursemodule,
            $instance,
            $participantcode
        );

        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(mod_amplifier_external::save_reminder_returns(), $response);

        return $DB->get_record('amplifier_reminder',
        array('course' => $course,
        'coursemodule' => $coursemodule,
        'instance' => $instance,
        'user' => $user,
        'goal' => '1',
        'startdate' => $startdate),
        '*',
        IGNORE_MULTIPLE);

    }

    /**
     * Test submitting setup parameters
     *
     * @param [type] $courseid
     * @param [type] $userid
     * @param [type] $cmid
     * @param [type] $instanceid
     */
    protected function submit_setup_without_relations($courseid, $userid, $cmid, $instanceid) {
        global $DB;

        $controller = new amplifier_controller($courseid, $userid, $cmid, $instanceid);

        mod_amplifier_external::submit_setup(
            $courseid,
            $userid,
            $cmid,
            $instanceid,
            "PARTICIPANTCODE",
            json_encode([]),
            json_encode([])
        );

        $amplifiersetup = $DB->get_record('amplifier_setup',
        array('course' => $courseid,
        'coursemodule' => $cmid,
        'instance' => $instanceid,
        'user' => $userid,
        'finished' => '1'),
        '*',
        IGNORE_MULTIPLE);

        $this->assertNotNull($amplifiersetup);
        $this->assertEquals($amplifiersetup->finished, 1);

    }

    /**
     * Test exporting data
     */
    protected function submit_setup_for_one_user() {
        global $DB;
        $this->resetAfterTest();
        $course1 = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course1->id);
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student');
        $widgetinstance = $this->getDataGenerator()->create_module('amplifier', array('course' => $course1->id));
        $cm = get_coursemodule_from_instance('amplifier', $widgetinstance->id);
        $cmcontext1 = \context_module::instance($cm->id);
        $controller = new amplifier_controller($course1->id, $user1->id, $cm->id, $widgetinstance->id);
        [$amplifierusersetup, $selectedgoal] = providerhelper::submit_setup($course1->id, $cm->id, $widgetinstance->id, $user1->id);
        return [$course1, $cm, $user1, $widgetinstance, $coursecontext, $cmcontext1, $amplifierusersetup, $selectedgoal];
    }
}
