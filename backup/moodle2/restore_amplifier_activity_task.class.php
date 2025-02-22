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
 * Main restore class
 *
 * @package   mod_amplifier
 * @category  backup
 * @copyright 2024 onwards Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Because it exists (must).
require_once($CFG->dirroot . '/mod/amplifier/backup/moodle2/restore_amplifier_stepslib.php');

/**
 * amplifier restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_amplifier_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // The amplifier only has one structure step.
        $this->add_step(
            new restore_amplifier_activity_structure_step('amplifier_structure', 'amplifier.xml')
        );
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('amplifier', ['intro'], 'amplifier');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('AMPLIFIERVIEWBYID', '/mod/amplifier/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('AMPLIFIERINDEX', '/mod/amplifier/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule(
            'amplifier',
            'amplifier_updated',
            'view.php?id={course_module}',
            '{amplifier}'
        );

        return $rules;
    }

    /**
     * Define the restore log rules for course
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];
        $rules[] = new restore_log_rule('amplifier', 'view all', 'index.php?id={course}', null);
        return $rules;
    }
}

