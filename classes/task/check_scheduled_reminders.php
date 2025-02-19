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

        mtrace('check_scheduled_reminders task started');

        $now = time() * 1000;
        $select = 'enddate > :enddate AND startdate < :startdate';
        $params = [
            "enddate" => $now,
            "startdate" => $now,
        ];
        $ampreminderrecords = $DB->get_records_select('amplifier_reminders', $select, $params);

        var_dump($now);
        mtrace("we have " . count($ampreminderrecords) . " records");
        var_dump($ampreminderrecords);

        foreach ($ampreminderrecords as $reminderrecord) {

            mtrace('check last notification timestamp for user ' . $reminderrecord->amp_user . ' and goal ' . $reminderrecord->goal);
            mtrace('notification timestamp = ' . $reminderrecord->lastnotificationdate);

            $now = new DateTime();
            if ($reminderrecord->lastnotificationdate > 0) {
                $lastnotificationdate = new DateTime();
                mtrace('Check the last notification timestamp ...');
                $lastnotificationdate->setTimestamp($reminderrecord->lastnotificationdate);
                $diff = $now->diff($lastnotificationdate);

                mtrace('difference in days is ' . $diff->d);
                mtrace('difference in months is ' . $diff->m);
                mtrace('the frequency of the reminder is set to ' . $reminderrecord->frequency);

                if ($reminderrecord->frequency == 0 && $diff->d > 0
                    || $reminderrecord->frequency == 1 && $diff->d > 6
                    || $reminderrecord->frequency == 2 && $diff->m > 0) {

                    mtrace('Last notification too far in the past, so send notification to user and update timestamp');
                    $amplifierreminder = new \stdClass;
                    $amplifierreminder->id = $reminderrecord->id;
                    $amplifierreminder->lastnotificationdate = $now->getTimestamp();
                    $DB->update_record('amplifier_reminders', $amplifierreminder);
                    $this->sendnotification($reminderrecord->userid, $reminderrecord->course, $reminderrecord->amplifiergoalid);
                }
            } else {
                mtrace('No notification timestamp set so far, so send notification to user and update timestamp');
                $currenthour = (int)$now->format("G");
                $currentminute = (int)$now->format("i");
                var_dump($currenthour);
                var_dump($currentminute);

                if ($reminderrecord->reminderhour == $currenthour
                    && $reminderrecord->reminderminute + 5 >= $currentminute
                    && $reminderrecord->reminderminute <= $currentminute) {
                        mtrace('send notification now');
                        // If message send failed, skip update.
                        if (!$this->sendnotification($reminderrecord->amp_user, $reminderrecord->course, $reminderrecord->goal)) {
                            mtrace('failed to send notification. no update');
                            continue;
                        }
                        $amplifierreminder = new \stdClass;
                        $amplifierreminder->id = $reminderrecord->id;
                        $amplifierreminder->lastnotificationdate = $now->getTimestamp();
                        $DB->update_record('amplifier_reminders', $amplifierreminder);
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
     * @return bool whether the notification was successful
     */
    private function sendnotification($userid, $courseid, $goalid) {
        global $DB, $CFG;
        // Check if can send notification.
        $provider = 'reflection_reminder';
        $component = 'mod_amplifier';

        $user = $DB->get_record('user', array('id' => $userid));

        $params = ["id" => $courseid];
        $courserecord = $DB->get_record('course', $params);

        $sqlstmt = "SELECT topics.title as topic, goals.title as goal
                      FROM {learninggoalwidget_goals} goals
                 LEFT JOIN {learninggoalwidget_topics} topics ON goals.topicid = topics.id
                     WHERE goals.id = :goalid";
        $params = ["goalid" => $goalid];
        $goalrecord = $DB->get_record_sql($sqlstmt, $params);

        $info = new \stdClass();
        $info->coursetitle = $courserecord->fullname;
        $info->topicname = $goalrecord->topic;
        $info->goalname = $goalrecord->goal;
        $info->url = $CFG->wwwroot . '/course/view.php?' . 'id=' . $courseid;

        $eventdata = new \core\message\message();
        $eventdata->component = 'mod_amplifier';
        $eventdata->name = 'reflection_reminder';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = get_string('amplifier_user_reminder_message_subject', 'amplifier');
        $eventdata->fullmessage = get_string('amplifier_user_reminder_message_text', 'amplifier', $info);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = get_string('amplifier_user_reminder_message_html', 'amplifier', $info);
        $eventdata->notification = 1;
        $eventdata->smallmessage = '';
        $eventdata->contexturl = $CFG->wwwroot . '/course/view.php?' . 'id=' . $courseid;
        $eventdata->contexturlname = 'Training Amplifier';
        $eventdata->courseid = $courseid;
        return message_send($eventdata) != false;
    }

}
