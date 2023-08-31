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
 * Lookup user set reminders and send reflection notification message
 *
 * @package    mod_amplifier
 * @copyright University of Technology Graz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_amplifier\task;

defined('MOODLE_INTERNAL') || die();

use core_user;
use DateTime;
use stdClass;

/**
 * Amplifier User Scheduled Reminders Task
 *
 * @package    mod_amplifier
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class check_scheduled_reminders extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('amplifier_check_scheduled_reminders', 'mod_amplifier');
    }

    /**
     * Execute User Scheduled Reminders Task
     */
    public function execute() {
        global $DB;

        // mtrace('check_scheduled_reminders task started');

        $sqlstmt = "SELECT id, startdate, enddate, reminderhour, reminderminute, lastnotificationdate, goal,
        frequency, course, amp_user, coursemodule, instance
        FROM {amplifier_reminder}
        WHERE enddate > (UNIX_TIMESTAMP() * 1000)";

        $ampreminderrecords = $DB->get_records_sql($sqlstmt);

        // mtrace("we have " . count($ampreminderrecords) . " records");

        foreach ($ampreminderrecords as $reminderrecord) {

            // mtrace('check last notification timestamp for user ' . $reminderrecord->user . ' and goal ' . $reminderrecord->goal);
            // mtrace('notification timestamp = ' . $reminderrecord->lastnotificationdate);

            $now = new DateTime();
            if ($reminderrecord->lastnotificationdate > 0) {
                $lastnotificationdate = new DateTime();
                // mtrace('Check the last notification timestamp ...');
                $lastnotificationdate->setTimestamp($reminderrecord->lastnotificationdate);
                $diff = $now->diff($lastnotificationdate);

                // mtrace('difference in days is ' . $diff->d);
                // mtrace('difference in months is ' . $diff->m);
                // mtrace('the frequency of the reminder is set to ' . $reminderrecord->frequency);

                if ($reminderrecord->frequency == 0 && $diff->d > 0
                    || $reminderrecord->frequency == 1 && $diff->d > 6
                    || $reminderrecord->frequency == 2 && $diff->m > 0) {

                    // mtrace('Last notification too far in the past, so send notification to user and update timestamp');
                    $amplifierreminder = new stdClass;
                    $amplifierreminder->id = $reminderrecord->id;
                    $amplifierreminder->lastnotificationdate = $now->getTimestamp();
                    $DB->update_record('amplifier_reminder', $amplifierreminder);
                    $this->sendnotification($reminderrecord->user, $reminderrecord->course, $reminderrecord->goal);
                }
            } else {

                // mtrace('No notification timestamp set so far, so send notification to user and update timestamp');
                $currenthour = (int)$now->format("G");
                $currentminute = (int)$now->format("i");
                if ($reminderrecord->reminderhour == $currenthour
                    && $reminderrecord->reminderminute >= $currentminute
                    && $reminderrecord->reminderminute <= ($currentminute + 1)) {
                        $amplifierreminder = new stdClass;
                        $amplifierreminder->id = $reminderrecord->id;
                        $amplifierreminder->lastnotificationdate = $now->getTimestamp();
                        $DB->update_record('amplifier_reminder', $amplifierreminder);
                        // mtrace('send notification now');
                        $this->sendnotification($reminderrecord->user, $reminderrecord->course, $reminderrecord->goal);
                }
            }
        }

        // mtrace('check_scheduled_reminders task finished');

    }

    /**
     * Send reflection reminder message
     * @param [type] $userid
     * @param [type] $courseid
     * @param [type] $goalid
     */
    private function sendnotification($userid, $courseid, $goalid) {
        global $DB, $CFG;

        $user = $DB->get_record('user', array('id' => $userid));

        $sqlstmt = "SELECT fullname
        FROM {course}
        WHERE id = ?";
        $params = [$courseid];
        $courserecord = $DB->get_record_sql($sqlstmt, $params);

        $sqlstmt = "SELECT a.lgw_title as topic, b.lgw_title as goal
        FROM {learninggoalwidget_goal} b, {learninggoalwidget_topic} a
        WHERE b.id = ? and b.lgw_topic = a.id";
        $params = [$goalid];
        $goalrecord = $DB->get_record_sql($sqlstmt, $params);

        $info = new stdClass();
        $info->coursetitle = $courserecord->fullname;
        $info->topicname = $goalrecord->topic;
        $info->goalname = $goalrecord->goal;
        $info->url = $CFG->wwwroot . '/course/view.php?' . 'id=' . $courseid;

        $eventdata = new \core\message\message();
        $eventdata->component = 'mod_amplifier';
        $eventdata->name = 'reflection_reminder';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->courseid = $courseid;
        $eventdata->subject = get_string('amplifier_user_reminder_message_subject', 'amplifier');
        $eventdata->fullmessage = get_string('amplifier_user_reminder_message_text', 'amplifier', $info);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = get_string('amplifier_user_reminder_message_html', 'amplifier', $info);
        $eventdata->smallmessage = '';
        $eventdata->contexturl = $CFG->wwwroot . '/course/view.php?' . 'id=' . $courseid;
        $eventdata->contexturlname = '';
        message_send($eventdata);
    }

}
