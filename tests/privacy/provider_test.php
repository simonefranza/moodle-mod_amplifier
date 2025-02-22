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

namespace mod_amplifier\privacy;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/amplifier/tests/utils.php');

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use stdClass;
use mod_amplifier\privacy\provider;
use mod_amplifier\core\amplifier_controller;
use mod_amplifier\external\submit_setup;
use core_external\external_api;

/**
 * Amplifier Provider Test Case
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider_test extends provider_testcase {
    use \mod_amplifier\utils;
    /**
     * Test for provider::get_metadata().
     * @covers \mod_amplifier\privacy\provider::get_metadata
     */
    public function test_get_metadata() {
        $collection = new \core_privacy\local\metadata\collection('amplifier');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(3, $itemcollection);
        $this->assertSame('amplifier_reflections', $itemcollection[0]->get_name());
        $this->assertSame('amplifier_reminders', $itemcollection[1]->get_name());
        $this->assertSame('amplifier_goals', $itemcollection[2]->get_name());
    }

    /**
     * Test that getting the contexts for a user works.
     * @covers \mod_amplifier\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid() {
        $setup = $this->setup_widget(true);

        $contextlist = provider::get_contexts_for_userid($setup->user->id);
        $this->assertEquals(0, count($contextlist->get_contextids()));

        // Submit setup.
        $student = $this->create_user('student', $setup->course->id, true);
        $this->submit_setup($setup);

        $coursemodule = get_coursemodule_from_instance('amplifier', $setup->instance->id);
        $cmcontext1 = \context_module::instance($coursemodule->id);

        // The user will be in these contexts.
        $usercontextids = [
          $cmcontext1,
        ];

        $contextlist = provider::get_contexts_for_userid($setup->user->id);
        $this->assertEquals(0, count($contextlist->get_contextids()));
        $contextlist = provider::get_contexts_for_userid($student->id);
        $this->assertEquals(count($usercontextids), count($contextlist->get_contextids()));
    }

    /**
     * Test returning a list of user IDs related to a context (assign).
     * @covers \mod_amplifier\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context() {
        $setup = $this->setup_widget(true);

        $coursemodule = get_coursemodule_from_instance('amplifier', $setup->instance->id);
        $coursecontext = \context_course::instance($coursemodule->course);
        $cmcontext = \context_module::instance($coursemodule->id);

        $user1 = $this->create_user('student', $setup->course->id, true);
        $user2 = $this->create_user('student', $setup->course->id, false);
        $user3 = $this->create_user('student', $setup->course->id, false);
        $user4 = $this->create_user('student', $setup->course->id, false);
        $user5 = $this->create_user('editingteacher', $setup->course->id, false);

        // Submit setup.
        $this->submit_setup($setup);
        $this->setUser($user2);
        $this->submit_setup($setup);

        $userlist = new \core_privacy\local\request\userlist($coursecontext, 'assign');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertSame(0, count($userids));

        $userlist = new \core_privacy\local\request\userlist($cmcontext, 'assign');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        $this->assertTrue(in_array($user1->id, $userids));
        $this->assertTrue(in_array($user2->id, $userids));
        $this->assertFalse(in_array($user3->id, $userids));
        $this->assertFalse(in_array($user4->id, $userids));
        $this->assertFalse(in_array($user5->id, $userids));
        $this->assertFalse(in_array($setup->user->id, $userids));
    }

    /**
     * Test exporting data
     * @covers \mod_amplifier\privacy\provider::export_user_data
     */
    public function test_export_user_data_student() {
        $setup = $this->setup_widget(true);
        $student = $this->create_user('student', $setup->course->id, true);

        $coursemodule = get_coursemodule_from_instance('amplifier', $setup->instance->id);
        $coursecontext = \context_course::instance($coursemodule->course);
        $cmcontext = \context_module::instance($coursemodule->id);

        $submitdata = $this->submit_setup($setup);

        // Create reminder.
        $reminderdata = $this->save_reminder($setup->instance->id);

        // Create reflection.
        $reflectiondata = $this->submit_reflections($setup->instance->id);

        $writer = writer::with_context($cmcontext);
        $this->assertFalse($writer->has_any_data());

        // Test empty list.
        $approvedlist = new approved_contextlist($student, 'mod_amplifier', []);
        provider::export_user_data($approvedlist);
        $this->assertEmpty($writer->get_data(['selectedgoals']));
        $this->assertEmpty($writer->get_data(['reminders']));
        $this->assertEmpty($writer->get_data(['reflections']));

        // The student should have some text submitted.
        // Add the course context as well to make sure there is no error.
        $approvedlist = new approved_contextlist($student, 'mod_amplifier', [$cmcontext->id, $coursecontext->id]);
        provider::export_user_data($approvedlist);

        // Check export details.
        $selectedgoalsexport = $writer->get_data(['selectedgoals'])->selectedgoals;
        $this->assertNotNull($selectedgoalsexport);
        $this->assertEquals(count($selectedgoalsexport), 2);
        $topic1 = $submitdata->taxonomy->children[0];
        $this->assertEquals($selectedgoalsexport[0]->topictitle, $topic1->name);
        $this->assertEquals($selectedgoalsexport[0]->goaltitle, $topic1->children[0]->name);
        $this->assertEquals($selectedgoalsexport[1]->topictitle, $topic1->name);
        $this->assertEquals($selectedgoalsexport[1]->goaltitle, $topic1->children[1]->name);

        $remindersexport = $writer->get_data(['reminders'])->reminders;
        $this->assertNotNull($remindersexport);
        $this->assertEquals(count($remindersexport), 1);
        $this->assertEquals($remindersexport[0]->startdate, $reminderdata->startdate);
        $this->assertEquals($remindersexport[0]->enddate, $reminderdata->enddate);
        $this->assertEquals($remindersexport[0]->reminderhour, $reminderdata->reminderhour);
        $this->assertEquals($remindersexport[0]->reminderminute, $reminderdata->reminderminute);
        $this->assertEquals($remindersexport[0]->frequency, $reminderdata->frequency);
        $this->assertEquals($remindersexport[0]->topictitle, $topic1->name);
        $this->assertEquals($remindersexport[0]->goaltitle, $topic1->children[0]->name);

        $reflectionsexport = $writer->get_data(['reflections'])->reflections;
        $this->assertNotNull($reflectionsexport);
        $this->assertEquals(count($reflectionsexport), 1);
        $this->assertEquals($reflectionsexport[0]->userid, $student->id);
        $this->assertEquals($reflectionsexport[0]->topictitle, $topic1->name);
        $this->assertEquals($reflectionsexport[0]->goaltitle, $topic1->children[0]->name);
        $this->assertEquals($reflectionsexport[0]->response, $reflectiondata->reflection);
    }

    /**
     * Test delete all users data wrt training amplifier widget
     * @covers \mod_amplifier\privacy\provider::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $setup = $this->setup_widget(true);
        $this->create_user('student', $setup->course->id, true);

        $coursemodule = get_coursemodule_from_instance('amplifier', $setup->instance->id);
        $cmcontext = \context_module::instance($coursemodule->id);

        $lgwcoursemodule = get_coursemodule_from_instance('learninggoalwidget', $setup->lgwinstance->id);
        $lgwcmcontext = \context_module::instance($lgwcoursemodule->id);

        $syscontext = \context_system::instance();

        // Test with not a module context.
        provider::delete_data_for_all_users_in_context($syscontext);

        // Test with LGW module context.
        provider::delete_data_for_all_users_in_context($lgwcmcontext);

        // Try to delete with no data.
        provider::delete_data_for_all_users_in_context($cmcontext);

        $this->submit_setup($setup);

        // Create data.
        $this->save_reminder($setup->instance->id);
        $this->submit_reflections($setup->instance->id);
        provider::delete_data_for_all_users_in_context($cmcontext);

        // Check all relevant tables.
        $records = $DB->get_records('amplifier_goals');
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reflections');
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_reminders');
        $this->assertEmpty($records);
    }

    /**
     * A test for deleting all user data for one user.
     * @covers \mod_amplifier\privacy\provider::delete_data_for_user
     */
    public function test_delete_data_for_user() {
        global $DB;
        $setup = $this->setup_widget(true);

        $coursemodule = get_coursemodule_from_instance('amplifier', $setup->instance->id);
        $coursecontext = \context_course::instance($coursemodule->course);
        $cmcontext = \context_module::instance($coursemodule->id);

        $lgwcoursemodule = get_coursemodule_from_instance('learninggoalwidget', $setup->lgwinstance->id);
        $lgwcmcontext = \context_module::instance($lgwcoursemodule->id);

        // Create data for student1.
        $student = $this->create_user('student', $setup->course->id, true);
        $this->submit_setup($setup);
        $this->save_reminder($setup->instance->id);
        $this->submit_reflections($setup->instance->id);

        // Create data for student2.
        $student2 = $this->create_user('student', $setup->course->id, true);
        $submitdata = $this->submit_setup($setup);
        $reminderdata = $this->save_reminder($setup->instance->id);
        $reflectiondata = $this->submit_reflections($setup->instance->id);

        // Delete students's data.
        $approvedlist = new approved_contextlist(
          $student,
          'mod_amplifier',
          [$cmcontext->id, $coursecontext->id, $lgwcmcontext->id]
        );
        provider::delete_data_for_user($approvedlist);

        // Check all relevant tables.
        $records = $DB->get_records('amplifier_goals', ['userid' => $student->id]);
        $this->assertEmpty($records);

        $records = array_values($DB->get_records('amplifier_goals', ['userid' => $student2->id]));
        $this->assertNotEmpty($records);

        $data = $DB->get_records('amplifier_reflections', ['amplifiergoalid' => $records[0]->id]);
        $this->assertNotEmpty($data);
        $data = $DB->get_records('amplifier_reminders', ['amplifiergoalid' => $records[0]->id]);
        $this->assertNotEmpty($data);

        $records = $DB->get_records('amplifier_reflections');
        foreach ($records as $record) {
            $data = $DB->get_record('amplifier_goals', ['id' => $record->amplifiergoalid]);
            $this->assertTrue($data->userid !== $student->id);
        }
        $records = $DB->get_records('amplifier_reminders');
        foreach ($records as $record) {
            $data = $DB->get_record('amplifier_goals', ['id' => $record->amplifiergoalid]);
            $this->assertTrue($data->userid !== $student->id);
        }
    }

    /**
     * A test for deleting all user data for a bunch of users.
     * @covers \mod_amplifier\privacy\provider::delete_data_for_users
     * @covers \mod_amplifier\privacy\provider::delete_data_for_user_int
     */
    public function test_delete_data_for_users() {
        global $DB;
        $setup = $this->setup_widget(true);

        $coursemodule = get_coursemodule_from_instance('amplifier', $setup->instance->id);
        $coursecontext = \context_course::instance($coursemodule->course);
        $cmcontext = \context_module::instance($coursemodule->id);
        $coursecontext = \context_course::instance($coursemodule->course);
        $cmcontext = \context_module::instance($coursemodule->id);

        $lgwcoursemodule = get_coursemodule_from_instance('learninggoalwidget', $setup->lgwinstance->id);
        $lgwcmcontext = \context_module::instance($lgwcoursemodule->id);

        // Create data for student1.
        $student = $this->create_user('student', $setup->course->id, true);
        $this->submit_setup($setup);
        $this->save_reminder($setup->instance->id);
        $this->submit_reflections($setup->instance->id);

        // Create data for student2.
        $student2 = $this->create_user('student', $setup->course->id, true);
        $submitdata = $this->submit_setup($setup);
        $reminderdata = $this->save_reminder($setup->instance->id);
        $reflectiondata = $this->submit_reflections($setup->instance->id);

        // Create data for student3.
        $student3 = $this->create_user('student', $setup->course->id, true);
        $submitdata = $this->submit_setup($setup);
        $reminderdata = $this->save_reminder($setup->instance->id);
        $reflectiondata = $this->submit_reflections($setup->instance->id);

        // Create student4 but without data.
        $student4 = $this->create_user('student', $setup->course->id, true);

        // Delete with wrong context.
        $userlist = new approved_userlist($coursecontext,
          'amplifier',
          [$student->id, $student3->id, $student4->id]
        );
        provider::delete_data_for_users($userlist);

        // Delete with lgw context.
        $userlist = new approved_userlist($lgwcmcontext,
          'learninggoalwidget',
          [$student->id, $student3->id, $student4->id]
        );
        provider::delete_data_for_users($userlist);

        // Check all relevant tables.
        $records = $DB->get_records('amplifier_goals', ['userid' => $student->id]);
        $this->assertSame(2, count($records));
        $records = $DB->get_records('amplifier_goals', ['userid' => $student3->id]);
        $this->assertSame(2, count($records));
        $records = $DB->get_records('amplifier_goals', ['userid' => $student4->id]);
        $this->assertSame(0, count($records));

        $userlist = new approved_userlist($cmcontext,
          'amplifier',
          [$student->id, $student3->id, $student4->id]
        );
        provider::delete_data_for_users($userlist);

        // Check all relevant tables.
        $records = $DB->get_records('amplifier_goals', ['userid' => $student->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_goals', ['userid' => $student3->id]);
        $this->assertEmpty($records);
        $records = $DB->get_records('amplifier_goals', ['userid' => $student4->id]);
        $this->assertEmpty($records);

        $records = array_values($DB->get_records('amplifier_goals', ['userid' => $student2->id]));
        $this->assertNotEmpty($records);

        $data = $DB->get_records('amplifier_reflections', ['amplifiergoalid' => $records[0]->id]);
        $this->assertNotEmpty($data);
        $data = $DB->get_records('amplifier_reminders', ['amplifiergoalid' => $records[0]->id]);
        $this->assertNotEmpty($data);

        $records = $DB->get_records('amplifier_reflections');
        foreach ($records as $record) {
            $data = $DB->get_record('amplifier_goals', ['id' => $record->amplifiergoalid]);
            $this->assertTrue($data->userid !== $student->id);
            $this->assertTrue($data->userid !== $student3->id);
            $this->assertTrue($data->userid !== $student4->id);
        }
        $records = $DB->get_records('amplifier_reminders');
        foreach ($records as $record) {
            $data = $DB->get_record('amplifier_goals', ['id' => $record->amplifiergoalid]);
            $this->assertTrue($data->userid !== $student->id);
            $this->assertTrue($data->userid !== $student3->id);
            $this->assertTrue($data->userid !== $student4->id);
        }
    }
}
