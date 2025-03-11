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
 * Training Amplifier Test Utils
 *
 * @package   mod_amplifier
 * @copyright 2025 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_amplifier;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/learninggoalwidget/classes/local/taxonomy.php');

use core_external\external_api;
use mod_amplifier\local\amplifier;
use mod_learninggoalwidget\local\taxonomy;
use mod_amplifier\external\save_reminder;
use mod_amplifier\external\submit_setup;
use mod_amplifier\external\submit_reflections;

/**
 * Training Amplifier Test Utils
 *
 * @package   mod_amplifier
 * @copyright 2025 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait utils {
    /**
     * helper function, sets up test environment
     *
     * @return void
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * helper function creating an instance
     *
     * @param bool $setuplgw Whether to setup the learninggoalwidget
     * @return \stdClass
     */
    protected function setup_widget($setuplgw) {
        $this->setUp();

        $return = new \stdClass;
        $return->course = $this->getDataGenerator()->create_course();
        $return->user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($return->user->id, $return->course->id, 'editingteacher');
        $this->setUser($return->user);
        if ($setuplgw) {
            $return->lgwinstance = $this->getDataGenerator()->create_module(
              'learninggoalwidget',
              ['course' => $return->course->id]
            );
            $taxonomy = new \stdClass;
            $taxonomy->name = 'name';
            $taxonomy->children = $this->create_taxonomy(2, 2);
            taxonomy::update_taxonomy($return->lgwinstance->id, $taxonomy);
            $return->taxonomy = $taxonomy;
        } else {
            $return->lgwinstance = -1;
            $return->taxonomy = (object)[];
        }

        $options = [
            'course' => $return->course->id,
            'name' => 'Training Amplifier',
            'learninggoalwidgetid' => $return->lgwinstance->id,
        ];
        $return->instance = $this->getDataGenerator()->create_module('amplifier', $options);

        return $return;
    }

    /**
     * helper function to retrieve the taxonomy of a learninggoalwidget
     *
     * @param number $lgwid ID of the learninggoalwidget instance
     * @return \stdClass
     */
    protected function get_taxonomy($lgwid) {
        return json_decode(taxonomy::get_taxonomy_as_json($lgwid));
    }

    /**
     * helper function to create a user with the desired role
     *
     * @param string $role of the user to be created
     * @param number $courseid id of the course where the user should be enrolled
     * @param bool $activate whether the user should be set as current user
     * @return \stdClass
     */
    protected function create_user($role, $courseid, $activate) {
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $role);
        if ($activate) {
            $this->setUser($user);
        }

        return $user;
    }

    /**
     * Helper function to create the learning goal widget taxonomy
     *
     * @param int $numtopics Number of topics to create
     * @param int $numgoals Number of goals per topic to create
     * @return array of topics with goals in the children prop
     */
    private function create_taxonomy($numtopics, $numgoals): array {
        // Create $numtopics topics with $numgoals goals each.
        $topics = [];
        for ($i = 0; $i < $numtopics; $i++) {
            $goals = [];
            for ($ii = 0; $ii < $numgoals; $ii++) {
                $newgoal = (object) [
                    'name' => 'T' . $i . 'G' . $ii,
                    'shortname' => 'T' . $i . 'G' . $ii,
                    'url' => 'http://topic' . $i . 'goal' . $ii . '.com',
                    'ranking' => $ii + 1,
                    'goalid' => $i * $numtopics + $ii,
                    'new' => true,
                ];
                $goals[] = $newgoal;
            }
            $newtopic = (object) [
                'name' => 'T' . $i,
                'shortname' => 'T' . $i,
                'url' => 'http://topic' . $i . '.com',
                'ranking' => $i + 1,
                'topicid' => $i,
                'children' => $goals,
                'new' => true,
            ];
            $topics[] = $newtopic;
        }
        return $topics;
    }

    /**
     * Helper function to check that a topic contains the expected data
     * The data must be generated with create_taxonomy
     *
     * @param stdClass $topic Topic to check
     * @param number $i Value to use for the check
     * @param number $newranking New ranking of the topic
     * @param number $numgoals Number of goals that the topic should contain
     * @param bool $checkgoals Whether to check the goals of the topic or not
     */
    private function check_topic($topic, $i, $newranking, $numgoals, $checkgoals) {
        $this->assertTrue(isset($topic->name) && is_string($topic->name));
        $this->assertSame($topic->name, 'T' . $i);
        $this->assertTrue(isset($topic->shortname) && is_string($topic->shortname));
        $this->assertSame($topic->shortname, 'T' . $i);
        $this->assertTrue(isset($topic->url) && is_string($topic->url));
        $this->assertSame($topic->url, 'http://topic' . $i . '.com');
        $this->assertTrue(isset($topic->ranking) && is_int($topic->ranking));
        $this->assertSame($topic->ranking, $newranking);
        $this->assertTrue(isset($topic->topicid) && is_int($topic->topicid));
        $this->assertTrue(isset($topic->children) && is_array($topic->children));
        $this->assertTrue(count($topic->children) == $numgoals);
        if (!$checkgoals) {
            return;
        }
        for ($ii = 0; $ii < $numgoals; $ii++) {
            $this->check_goal($topic->children[$ii], $i, $ii);
        }
    }

    /**
     * Helper function to check that a goal contains the expected data
     * The data must be generated with create_taxonomy
     *
     * @param stdClass $goal Goal to check
     * @param number $i Topic-value to use for the check
     * @param number $ii Goal-value to use for the check
     * @param number $newranking New ranking
     */
    private function check_goal($goal, $i, $ii, $newranking = -2) {
        if ($newranking == -2) {
            $newranking = $ii + 1;
        }
        $this->assertTrue(isset($goal->name) && is_string($goal->name));
        $this->assertSame($goal->name, 'T' . $i . 'G' . $ii);
        $this->assertTrue(isset($goal->shortname) && is_string($goal->shortname));
        $this->assertSame($goal->shortname, 'T' . $i . 'G' . $ii);
        $this->assertTrue(isset($goal->url) && is_string($goal->url));
        $this->assertSame($goal->url, 'http://topic' . $i . 'goal' . $ii . '.com');
        $this->assertTrue(isset($goal->ranking) && is_int($goal->ranking));
        $this->assertSame($goal->ranking, $newranking);
        $this->assertTrue(isset($goal->goalid) && is_int($goal->goalid));
    }

    /**
     * Function to submit setup for the current user and return the data.
     * @param stdClass $setup
     * @return stdClass
     */
    protected function submit_setup($setup) {
        $taxonomy = $this->get_taxonomy($setup->lgwinstance->id);
        $firsttopic = $taxonomy->children[0];
        $goals = [
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $firsttopic->children[0]->goalid],
            (object)['topicid' => $firsttopic->topicid, 'goalid' => $firsttopic->children[1]->goalid],
        ];
        $submission = submit_setup::execute($setup->instance->id, json_encode($goals));
        $submission = external_api::clean_returnvalue(submit_setup::execute_returns(), $submission);
        $this->assertSame("OK", $submission);
        return (object) [
          "taxonomy" => $taxonomy,
          "goals" => $goals,
        ];
    }

    /**
     * Saves a reminder for the active user and returns the set data
     * @param int $instanceid ID of instance
     * @param int|null $amplifiergoalid ID of amplifier_goals
     * @param object|null $data Data of the reminder
     * @return stdClass
     */
    protected function save_reminder($instanceid, $amplifiergoalid = null, ?object $data = null) {
        global $USER, $DB;
        if ($amplifiergoalid == null) {
            $amplifiergoalids = $DB->get_fieldset_select(
              'amplifier_goals',
              'id',
              'userid = :userid AND amplifierid = :amplifierid',
              ['userid' => $USER->id, 'amplifierid' => $instanceid]
            );
            $amplifiergoalid = $amplifiergoalids[0];
            if ($data) {
                $data->amplifiergoalid = $amplifiergoalid;
            }
        }
        if ($data == null) {
            $data = (object)[
                'startdate' => 0,
                'enddate' => 200000,
                'timezone' => 'Europe/Vienna',
                'reminderhour' => 10,
                'reminderminute' => 10,
                'frequency' => 0,
                'amplifiergoalid' => $amplifiergoalid,
            ];
        }
        $res = save_reminder::execute(
            $data->startdate,
            $data->enddate,
            $data->timezone,
            $data->reminderhour,
            $data->reminderminute,
            $data->frequency,
            $data->amplifiergoalid,
            $instanceid
        );
        $res = external_api::clean_returnvalue(save_reminder::execute_returns(), $res);
        return $data;
    }
    /**
     * Submits a reflection for the active user and returns the set data
     * @param int $instanceid ID of instance
     * @param object|null $data Data of the reminder
     * @return stdClass
     */
    protected function submit_reflections($instanceid, $data = null) {
        global $USER, $DB;
        if ($data == null) {
            $amplifiergoalids = $DB->get_fieldset_select(
              'amplifier_goals',
              'id',
              'userid = :userid AND amplifierid = :amplifierid',
              ['userid' => $USER->id, 'amplifierid' => $instanceid]
            );
            $data = (object) [
                'reflection' => "My reflection.",
                'amplifiergoalid' => $amplifiergoalids[0],
                'instanceid' => $instanceid,
            ];
        }

        $res = submit_reflections::execute(
            $data->reflection,
            $data->amplifiergoalid,
            $data->instanceid,
        );
        $res = external_api::clean_returnvalue(submit_reflections::execute_returns(), $res);
        return $data;
    }

    /**
     * Submits a reflection for the active user and returns the set data
     * @param int $instanceid ID of the lgw instance
     * @return stdClass
     */
    protected function mark_lgw_deleted($instanceid) {
        global $DB;
        $this->assertTrue($DB->record_exists('learninggoalwidget', ['id' => $instanceid]));
        $stmt = "SELECT cm.id
                   FROM {course_modules} cm
                   JOIN {modules} m ON cm.module = m.id
                  WHERE m.name = 'learninggoalwidget'
                    AND cm.instance = :instanceid";
        $cmid = $DB->get_field_sql($stmt, ['instanceid' => $instanceid]);
        $update = (object)[
          'id' => $cmid,
          'deletioninprogress' => 1
        ];
        $DB->update_record('course_modules', $update);
    }
}
