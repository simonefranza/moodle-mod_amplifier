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

use stdClass;
use mod_amplifier_external;
use external_api;

/**
 * Helper Class for test cases
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class providerhelper {

    /**
     * helper function submitting amplifier setup
     *
     * @param [type] $courseid
     * @param [type] $coursemoduleid
     * @param [type] $instanceid
     * @param [type] $userid
     *
     * @return void
     */
    public static function submit_setup($courseid, $coursemoduleid, $instanceid, $userid) {
        global $DB;

        // Create topic.
        $topicreflection = new stdClass;
        $topicreflection->lgw_title = "QUESTIONS_INITIAL";
        $topicreflection->lgw_shortname = "short";
        $topicreflection->lgw_url = "invalidurl";
        $topicreflection->id = $DB->insert_record('learninggoalwidget_topic', $topicreflection);
        $reflectioninitial = new stdClass;
        $reflectioninitial->lgw_title = "Goal Title Reflection";
        $reflectioninitial->lgw_shortname = "goal shortname";
        $reflectioninitial->lgw_url = "invalidurl";
        $reflectioninitial->lgw_topic = $topicreflection->id;
        $reflectioninitial->id = $DB->insert_record('learninggoalwidget_goal', $reflectioninitial);
        $goalirecord = new stdClass;
        $goalirecord->lgw_course = $courseid;
        $goalirecord->lgw_coursemodule = $coursemoduleid;
        $goalirecord->lgw_instance = $instanceid;
        $goalirecord->lgw_topic = $topicreflection->id;
        $goalirecord->lgw_goal = $reflectioninitial->id;
        $goalirecord->lgw_rank = 2;
        $goalirecord->id = $DB->insert_record('learninggoalwidget_i_goals', $goalirecord);

        // Create topic.
        $topicreflectiongoals = new stdClass;
        $topicreflectiongoals->lgw_title = "QUESTIONS_GOALS";
        $topicreflectiongoals->lgw_shortname = "short";
        $topicreflectiongoals->lgw_url = "invalidurl";
        $topicreflectiongoals->id = $DB->insert_record('learninggoalwidget_topic', $topicreflectiongoals);
        $reflectiongoal = new stdClass;
        $reflectiongoal->lgw_title = "Goal Title Reflection";
        $reflectiongoal->lgw_shortname = "goal shortname";
        $reflectiongoal->lgw_url = "invalidurl";
        $reflectiongoal->lgw_topic = $topicreflectiongoals->id;
        $reflectiongoal->id = $DB->insert_record('learninggoalwidget_goal', $reflectiongoal);
        $goalirecord = new stdClass;
        $goalirecord->lgw_course = $courseid;
        $goalirecord->lgw_coursemodule = $coursemoduleid;
        $goalirecord->lgw_instance = $instanceid;
        $goalirecord->lgw_topic = $topicreflectiongoals->id;
        $goalirecord->lgw_goal = $reflectiongoal->id;
        $goalirecord->lgw_rank = 3;
        $goalirecord->id = $DB->insert_record('learninggoalwidget_i_goals', $goalirecord);

        // Create topic.
        $topicrecord = new stdClass;
        $topicrecord->lgw_title = "Test Title";
        $topicrecord->lgw_shortname = "short";
        $topicrecord->lgw_url = "invalidurl";
        $topicrecord->id = $DB->insert_record('learninggoalwidget_topic', $topicrecord);

        // Create goal.
        $goalrecord = new stdClass;
        $goalrecord->lgw_title = "Goal Title";
        $goalrecord->lgw_shortname = "goal shortname";
        $goalrecord->lgw_url = "invalidurl";
        $goalrecord->lgw_topic = $topicrecord->id;
        $goalrecord->id = $DB->insert_record('learninggoalwidget_goal', $goalrecord);

        // Create goal.
        $goalirecord = new stdClass;
        $goalirecord->lgw_course = $courseid;
        $goalirecord->lgw_coursemodule = $coursemoduleid;
        $goalirecord->lgw_instance = $instanceid;
        $goalirecord->lgw_topic = $topicrecord->id;
        $goalirecord->lgw_goal = $goalrecord->id;
        $goalirecord->lgw_rank = 1;
        $goalirecord->id = $DB->insert_record('learninggoalwidget_i_goals', $goalirecord);

        $reflection = new stdClass();
        $reflection->userResponse = "This is a user response";
        $reflection->topicid = $topicrecord->id;
        $reflection->goalid = $goalrecord->id;

        $selectedgoal = new stdClass();
        $selectedgoal->topicid = $topicrecord->id;
        $selectedgoal->goalid = $goalrecord->id;

        mod_amplifier_external::submit_setup(
            $courseid,
            $userid,
            $coursemoduleid,
            $instanceid,
            "PARTICIPANTCODE",
            json_encode([$reflection]),
            json_encode([$selectedgoal])
        );

        $amplifierusersetup = $DB->get_record('amplifier_setup',
        array('course' => $courseid,
        'coursemodule' => $coursemoduleid,
        'instance' => $instanceid,
        'user' => $userid),
        '*',
        MUST_EXIST);

        $setupgoals = $DB->get_record('amplifier_setup_goals',
        array('topic' => $selectedgoal->topicid,
        'goal' => $selectedgoal->goalid,
        'course' => $courseid,
        'coursemodule' => $coursemoduleid,
        'instance' => $instanceid,
        'user' => $userid),
        '*',
        MUST_EXIST);

        return [$amplifierusersetup, $selectedgoal];
    }
}
