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
        return get_string('task:reminder', 'mod_amplifier');
    }

    /**
     * Execute User Scheduled Reminders Task
     */
    public function execute() {
        global $DB;

        // Select all reminders which are still active.
        $now = time() * 1000;
        $stmt = "SELECT amprem.id as reminderid,
                        amp.learninggoalwidgetid as lgwid,
                        amp.course,
                        ampgoals.userid,
                        amprem.amplifiergoalid,
                        amprem.startdate,
                        amprem.enddate,
                        amprem.reminderhour,
                        amprem.reminderminute,
                        amprem.frequency,
                        amprem.lastnotificationdate,
                        lgwtopics.title as topictitle,
                        lgwgoals.title as goaltitle
                   FROM {amplifier} amp
             INNER JOIN {amplifier_goals} ampgoals ON amp.id = ampgoals.amplifierid
             INNER JOIN {amplifier_reminders} amprem ON ampgoals.id = amprem.amplifiergoalid
             INNER JOIN {learninggoalwidget_goals} lgwgoals ON ampgoals.lgwgoalid = lgwgoals.id
             INNER JOIN {learninggoalwidget_topics} lgwtopics ON lgwgoals.topicid = lgwtopics.id
                  WHERE amprem.enddate > :enddate AND amprem.startdate < :startdate";
        $params = [
            "enddate" => $now,
            "startdate" => $now,
        ];
        $ampreminderrecords = $DB->get_records_sql($stmt, $params);

        foreach ($ampreminderrecords as $record) {
            $now = new DateTime();
            // Check if we already sent a reminder.
            if ($record->lastnotificationdate > 0) {
                $lastnotificationdate = new DateTime();
                $lastnotificationdate->setTimestamp($record->lastnotificationdate / 1000);
                $diff = $now->diff($lastnotificationdate);

                // If last reminder was sent too little ago, skip.
                if (!($record->frequency == 0 && $diff->d > 0
                    || $record->frequency == 1 && $diff->d > 6
                    || $record->frequency == 2 && $diff->m > 0)) {
                    continue;
                }
            }

            $currenthour = (int)$now->format("G");
            $currentminute = (int)$now->format("i");

            // Check that we match user preference.
            if (!((int)$record->reminderhour == $currenthour
                && (int)$record->reminderminute - 2 <= $currentminute
                && (int)$record->reminderminute + 2 >= $currentminute)) {
                continue;
            }
            $amplifierreminder = new \stdClass;
            $amplifierreminder->id = $record->reminderid;
            $amplifierreminder->lastnotificationdate = $now->getTimestamp() * 1000;
            // If message send failed, skip update.
            if (!$this->sendnotification($record)) {
                mtrace('mod_amplifier: Failed to send notification to user ' . $record->userid . '. No update.');
                continue;
            }
            $DB->update_record('amplifier_reminders', $amplifierreminder);
        }
    }

    /**
     * Send reflection reminder message
     * @param stdClass $record Record
     * @return bool whether the notification was successful
     */
    private function sendnotification($record) {
        global $DB, $CFG;
        // Check if can send notification.
        $provider = 'reflection_reminder';
        $component = 'mod_amplifier';

        $user = $DB->get_record('user', ['id' => $record->userid]);
        $course = $DB->get_record('course', ["id" => $record->course]);

        $info = new \stdClass();
        $info->coursetitle = $course->fullname;
        $info->topicname = $record->topictitle;
        $info->goalname = $record->goaltitle;
        $info->url = $CFG->wwwroot . '/course/view.php?' . 'id=' . $course->id;

        $eventdata = new \core\message\message();
        $eventdata->component = 'mod_amplifier';
        $eventdata->name = 'reflection_reminder';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = get_string('message:reminder:subject', 'amplifier');
        $eventdata->fullmessage = get_string('message:reminder:text', 'amplifier', $info);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = get_string('message:reminder:html', 'amplifier', $info);
        $eventdata->notification = 1;
        $eventdata->smallmessage = '';
        $eventdata->contexturl = $CFG->wwwroot . '/course/view.php?' . 'id=' . $course->id;
        $eventdata->contexturlname = 'Training Amplifier';
        $eventdata->courseid = $course->id;
        return message_send($eventdata) != false;
    }

}
