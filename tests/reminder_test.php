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

use mod_amplifier\task\check_scheduled_reminders;
use stdClass;
use mod_amplifier_external;
use external_api;
use DateTime;

/**
 * Learning Goal Taxonomy Test
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reminder_test extends \advanced_testcase {


    /**
     * testing class controller
     *
     * @return void
     */
    public function test_get_name() {
        global $DB;

        // Reset all changes automatically after this test.
        $this->resetAfterTest(true);
        $task = new check_scheduled_reminders();
        $this->assertNotNull($task);
        $this->assertEquals($task->get_name(), 'Lookup user set reminders and send reflection notification message');
    }

    /**
     * testing class controller
     *
     * @return void
     */
    public function test_execute() {
        global $DB;

        // Reset all changes automatically after this test.
        $this->resetAfterTest(true);

        list($course, $coursemodule, $widgetinstance, $user) = $this->create_user_and_course();

        $task = new check_scheduled_reminders();
        $this->assertNotNull($task);

        $now = new DateTime();
        $reminderstarttime = (int)microtime(true) * 1000;
        $reminderendtime = (int)microtime(true) * 1000 + 3600000;

        $currenthour = (int)$now->format("G");
        $currentminute = (int)$now->format("i");

        $DB->delete_records('amplifier_reminder');
        $recordexists = $DB->count_records('amplifier_reminder');
        $this->assertEquals(0, $recordexists);

        $DB->delete_records('learninggoalwidget_topic');
        // Create topic.
        $topicrecord = new stdClass;
        $topicrecord->title = "Topictitle";
        $topicrecord->shortname = "short";
        $topicrecord->url = "invalidurl";
        $topicrecord->id = $DB->insert_record('learninggoalwidget_topic', $topicrecord);
        $recordexists = $DB->count_records('learninggoalwidget_topic');
        $this->assertEquals(1, $recordexists);

        $DB->delete_records('learninggoalwidget_goal');
        // Create topic.
        $goalrecord = new stdClass;
        $goalrecord->title = "Goal Title";
        $goalrecord->shortname = "goal shortname";
        $goalrecord->url = "Invlaid url";
        $goalrecord->topic = $topicrecord->id;
        $goalrecord->id = $DB->insert_record('learninggoalwidget_goal', $goalrecord);
        $recordexists = $DB->count_records('learninggoalwidget_goal');
        $this->assertEquals(1, $recordexists);

        // Insert reminder.
        $reminder = (object)[
            'startdate' => $reminderstarttime,
            'enddate' => $reminderendtime,
            'reminderhour' => $currenthour,
            'reminderminute' => $currentminute,
            'frequency' => 0,
            'lastnotificationdate' => 0,
            'course' => $course->id,
            'coursemodule' => $coursemodule->id,
            'instance' => $widgetinstance->id,
            'user' => $user->id,
            'participantcode' => 'whatever',
            'goal' => $goalrecord->id
        ];
        $reminderid = $DB->insert_record('amplifier_reminder', $reminder);

        $recordexists = $DB->count_records('amplifier_reminder');
        $this->assertEquals(1, $recordexists);

        $this->preventResetByRollback();
        $sink = $this->redirectMessages();
        // Send reminder
        $task->execute();
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));

        // Update reminder.
        $reminder = (object)[
            'id' => $reminderid,
            'startdate' => $reminderstarttime,
            'enddate' => $reminderendtime,
            'reminderhour' => $currenthour,
            'reminderminute' => $currentminute,
            'frequency' => 0,
            'lastnotificationdate' => $reminderstarttime - 90000000,
            'course' => $course->id,
            'coursemodule' => $coursemodule->id,
            'instance' => $widgetinstance->id,
            'user' => $user->id,
            'participantcode' => 'whatever',
            'goal' => $goalrecord->id
        ];
        $reminderid = $DB->update_record('amplifier_reminder', $reminder);

        $this->preventResetByRollback();
        $sink = $this->redirectMessages();
        // Send reminder
        $task->execute();
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
    }

    /**
     * helper function creating a course and a user
     */
    public function create_user_and_course() {

        $course1 = $this->getDataGenerator()->create_course();
        $widgetinstance = $this->getDataGenerator()->create_module('amplifier', array('course' => $course1->id));
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $coursemodule = get_coursemodule_from_instance('amplifier', $widgetinstance->id, $course1->id);
        return [$course1, $coursemodule, $widgetinstance, $user1];
    }
}
