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

namespace mod_amplifier\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/amplifier/tests/utils.php');

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_external\external_api;
use stdClass;
use DateTime;
use DateTimeZone;
use mod_amplifier\privacy\provider;
use mod_amplifier\core\amplifier_controller;
use mod_amplifier\external\submit_setup;
use mod_amplifier\task\check_scheduled_reminders;

/**
 * Amplifier Scheduled Reminders Test Case
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class check_scheduled_reminders_test extends \advanced_testcase {
    use \mod_amplifier\utils;
    /**
     * Test for getting name of task
     * @return void
     *
     * @covers \mod_amplifier\task\check_scheduled_reminders::get_name
     */
    public function test_get_name(): void {
        $task = new check_scheduled_reminders();
        $this->assertEquals($task->get_name(), get_string('task:reminder', 'mod_amplifier'));
    }
    /**
     * Test if notifications are sent
     * @return void
     *
     * @covers \mod_amplifier\task\check_scheduled_reminders::execute
     * @covers \mod_amplifier\task\check_scheduled_reminders::get_timezoned_date
     * @covers \mod_amplifier\task\check_scheduled_reminders::is_correct_time
     * @covers \mod_amplifier\task\check_scheduled_reminders::is_lastnotificationdate_recent
     * @covers \mod_amplifier\task\check_scheduled_reminders::send_notification
     */
    public function test_execute(): void {
        global $DB, $CFG;
        $setup = $this->setup_widget(true);

        $task = new check_scheduled_reminders();
        $this->check_messages($task, 0);

        $now = new DateTime();
        $now->setTimezone(new DateTimeZone('Europe/Oslo'));
        $reminderstarttime = $now->getTimestamp() * 1000;
        $reminderendtime = ($now->getTimestamp() + 3600) * 1000;

        $currenthour = (int)$now->format('G');
        $currentminute = (int)$now->format('i');

        // Create data for student.
        $student = $this->create_user('student', $setup->course->id, true);
        $submitdata = $this->submit_setup($setup);
        $reminderdata = (object)[
            'startdate' => $reminderstarttime,
            'enddate' => $reminderendtime,
            'timezone' => 'Europe/Vienna',
            'reminderhour' => $currenthour,
            'reminderminute' => $currentminute,
            'frequency' => 0,
        ];
        $reminderdata = $this->save_reminder($setup->instance->id, null, $reminderdata);
        $this->submit_reflections($setup->instance->id);

        $message = $this->check_messages($task, 1)[0];

        $info = new \stdClass();
        $info->coursetitle = $setup->course->fullname;
        $info->topicname = $submitdata->taxonomy->children[0]->name;
        $info->goalname = $submitdata->taxonomy->children[0]->children[0]->name;
        $info->url = $CFG->wwwroot . '/course/view.php?' . 'id=' . $setup->course->id;

        $this->assertSame(\core_user::get_noreply_user()->id, (int)$message->useridfrom);
        $this->assertSame((int)$student->id, (int)$message->useridto);
        $this->assertSame(get_string('message:reminder:subject', 'amplifier'), $message->subject);
        $this->assertStringContainsString(get_string('message:reminder:text', 'amplifier', $info), $message->fullmessage);
        $this->assertSame((int)FORMAT_PLAIN, (int)$message->fullmessageformat);
        $this->assertStringContainsString(get_string('message:reminder:html', 'amplifier', $info), $message->fullmessagehtml);
        $this->assertSame('', $message->smallmessage);
        $this->assertSame('mod_amplifier', $message->component);
        $this->assertSame('reflection_reminder', $message->eventtype);
        $this->assertSame($info->url, $message->contexturl);
        $this->assertSame('Training Amplifier', $message->contexturlname);
        $this->assertSame(1, $message->notification);

        // Message has been already be sent.
        $this->check_messages($task, 0);

        $reminderid = $DB->get_fieldset_select(
          'amplifier_reminders',
          'id',
          'amplifiergoalid = :amplifiergoalid',
          ['amplifiergoalid' => $reminderdata->amplifiergoalid]
        );
        $DB->update_record('amplifier_reminders', [
          'id' => $reminderid[0],
          'lastnotificationdate' => 0,
          'reminderhour' => ($currenthour + 5) % 24,
        ]);

        // Hour doesn't match.
        $this->check_messages($task, 0);

        $DB->update_record('amplifier_reminders', [
          'id' => $reminderid[0],
          'timezone' => 'WrongTimezone',
          'lastnotificationdate' => 0,
          'reminderhour' => $currenthour,
        ]);
        // Invalid timezone.
        $this->check_messages($task, 0);
    }

    /**
     * helper function to send messages and check how many have been sent
     *
     * @param check_scheduled_reminders $task Task that sends messages
     * @param number $nummessages Number of expected messages
     * @return stdClass
     */
    private function check_messages($task, $nummessages) {
        $sink = $this->redirectMessages();
        // Send reminder.
        $task->execute();
        $messages = $sink->get_messages();
        $this->assertEquals($nummessages, count($messages));
        return $messages;
    }
}
