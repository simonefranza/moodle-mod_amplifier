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

use core_external\external_api;
use mod_amplifier\local\amplifier;

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
     * @return \stdClass
     */
    protected function setup_widget() {
        $this->setUp();

        $return = new \stdClass;
        $return->course = $this->getDataGenerator()->create_course();
        $return->user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($return->user->id, $return->course->id, 'editingteacher');
        $this->setUser($return->user);
        $return->instance = $this->getDataGenerator()->create_module('amplifier', ['course' => $return->course->id]);

        return $return;
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
}
