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
require_once($CFG->dirroot . '/mod/amplifier/tests/providerhelper.php');

use mod_amplifier\core\amplifier_controller;
use stdClass;
use mod_amplifier_external;
use external_api;

/**
 * Learning Goal Taxonomy Test
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller_test extends \advanced_testcase {


    /**
     * testing class controller
     *
     * @return void
     */
    public function test_render() {
        global $DB;

        // Reset all changes automatically after this test.
        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $widgetinstance = $this->getDataGenerator()->create_module('amplifier', array('course' => $course1->id));
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $coursemodule = get_coursemodule_from_instance('amplifier', $widgetinstance->id, $course1->id);

        $controller = new amplifier_controller($course1->id, $user1->id, $coursemodule->id, $widgetinstance->id);

        $this->assertNotNull($controller);
        $this->assertNotFalse($DB->get_record('amplifier_setup',
            array('course' => $course1->id,
            'coursemodule' => $coursemodule->id,
            'instance' => $widgetinstance->id,
            'user' => $user1->id),
            '*',
            MUST_EXIST));

        [$amplifierusersetup, $selectedgoal] = providerhelper::submit_setup($course1->id, $coursemodule->id, $widgetinstance->id, $user1->id);

        $this->assertNotFalse($amplifierusersetup);
        $this->assertEquals($amplifierusersetup->participantcode, "PARTICIPANTCODE");
        $this->assertEquals($amplifierusersetup->reflectiontopicshortname, "QUESTIONS_INITIAL");
        $this->assertEquals($amplifierusersetup->goalstopicshortname, "QUESTIONS_GOALS");
        $this->assertEquals($amplifierusersetup->finished, 1);

        $controller2 = new amplifier_controller($course1->id, $user1->id, $coursemodule->id, $widgetinstance->id);

        $templatecontext['courseId'] = $course1->id;
        $templatecontext['courseModuleId'] = $coursemodule->id;
        $templatecontext['instanceId'] = $widgetinstance->id;
        $templatecontext['userId'] = $user1->id;

        $renderedwidget = $controller2->render($templatecontext);

        $this->assertNotNull($renderedwidget);
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

    /**
     * testing class controller
     *
     * @return void
     */
    public function test_submitsetup() {
        // Reset all changes automatically after this test.
        $this->resetAfterTest(true);

        list($course1, $coursemodule, $widgetinstance, $user1) = $this->create_user_and_course();
        $controller = new amplifier_controller($course1->id, $user1->id, $coursemodule->id, $widgetinstance->id);

        [$amplifierusersetup, $selectedgoal] = providerhelper::submit_setup($course1->id, $coursemodule->id, $widgetinstance->id, $user1->id);

        $this->assertNotNull($controller);
        $this->assertNotFalse($amplifierusersetup);
        $this->assertEquals($amplifierusersetup->participantcode, "PARTICIPANTCODE");
        $this->assertEquals($amplifierusersetup->reflectiontopicshortname, "QUESTIONS_INITIAL");
        $this->assertEquals($amplifierusersetup->goalstopicshortname, "QUESTIONS_GOALS");
        $this->assertEquals($amplifierusersetup->finished, 1);

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
    public function save_reminder($startdate,
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
     * testing class controller
     *
     * @return void
     */
    public function test_submitreminder() {

        // Reset all changes automatically after this test.
        $this->resetAfterTest(true);

        list($course1, $coursemodule, $widgetinstance, $user1) = $this->create_user_and_course();

        $controller = new amplifier_controller($course1->id, $user1->id, $coursemodule->id, $widgetinstance->id);
        [$amplifierusersetup, $selectedgoal] = providerhelper::submit_setup($course1->id, $coursemodule->id, $widgetinstance->id, $user1->id);

        $reminderstarttime = (int)microtime(true);
        $lastnotificationdate = (int)microtime(true);
        $reminderendtime = (int)microtime(true);

        $reminder = $this->save_reminder($reminderstarttime,
        $reminderendtime,
        20,
        15,
        2,
        $lastnotificationdate,
        1,
        $user1->id,
        $course1->id,
        $coursemodule->id,
        $widgetinstance->id,
        "PARTICIPANTCODE");

        $this->assertNotNull($controller);
        $this->assertNotFalse($reminder);
        $this->assertEquals($reminder->startdate, $reminderstarttime);
        $this->assertEquals($reminder->enddate, $reminderendtime);
        $this->assertEquals($reminder->enddate, $reminderendtime);
        $this->assertEquals($reminder->reminderhour, 20);
        $this->assertEquals($reminder->reminderminute, 15);
        $this->assertEquals($reminder->frequency, 2);
        $this->assertEquals($reminder->lastnotificationdate, $lastnotificationdate);

        $reminderupdate = $this->save_reminder(
            $reminderstarttime,
            $reminderendtime,
            21,
            16,
            1,
            $lastnotificationdate,
            1,
            $user1->id,
            $course1->id,
            $coursemodule->id,
            $widgetinstance->id,
            "PARTICIPANTCODE"
        );

        $this->assertNotNull($controller);
        $this->assertNotFalse($reminderupdate);
        $this->assertEquals($reminderupdate->startdate, $reminderstarttime);
        $this->assertEquals($reminderupdate->enddate, $reminderendtime);
        $this->assertEquals($reminderupdate->enddate, $reminderendtime);
        $this->assertEquals($reminderupdate->reminderhour, 21);
        $this->assertEquals($reminderupdate->reminderminute, 16);
        $this->assertEquals($reminderupdate->frequency, 1);
        $this->assertEquals($reminderupdate->lastnotificationdate, $lastnotificationdate);
    }

    /**
     * testing class controller
     *
     * @return void
     */
    public function test_submitreflection() {
        global $DB;

        // Reset all changes automatically after this test.
        $this->resetAfterTest(true);

        list($course1, $coursemodule, $widgetinstance, $user1) = $this->create_user_and_course();

        $controller = new amplifier_controller($course1->id, $user1->id, $coursemodule->id, $widgetinstance->id);
        [$amplifierusersetup, $selectedgoal] = providerhelper::submit_setup($course1->id, $coursemodule->id, $widgetinstance->id, $user1->id);

        $reflectiontimestamp = (int)microtime(true);
        $response = mod_amplifier_external::submit_reflections(
            $reflectiontimestamp,
            json_encode(["some user reflection", "some user reflection"]),
            "1",
            $user1->id,
            $course1->id,
            $coursemodule->id,
            $widgetinstance->id,
            "PARTICIPANTCODE"
        );

        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(mod_amplifier_external::submit_reflections_returns(), $response);

        $amplifieruserreflection = $DB->get_record('amplifier_reflection',
        array('course' => $course1->id,
        'coursemodule' => $coursemodule->id,
        'instance' => $widgetinstance->id,
        'user' => $user1->id,
        'goal' => '1',
        'reflectedat' => $reflectiontimestamp),
        '*',
        IGNORE_MULTIPLE);

        $this->assertNotNull($controller);
        $this->assertNotFalse($amplifieruserreflection);
        $this->assertEquals($amplifieruserreflection->response, "some user reflection");
        $this->assertEquals($amplifieruserreflection->reflectedat, $reflectiontimestamp);

    }


}
