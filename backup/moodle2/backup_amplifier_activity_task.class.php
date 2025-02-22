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
 * Main backup class
 *
 * @package   mod_amplifier
 * @category  backup
 * @copyright 2025 onwards Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
// Because it exists (must).
require_once($CFG->dirroot . '/mod/amplifier/backup/moodle2/backup_amplifier_stepslib.php');

/**
 * training amplifier backup task that provides all the settings and steps
 * to perform one complete backup of the activity
 */
class backup_amplifier_activity_task extends backup_activity_task {

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
            new backup_amplifier_activity_structure_step('amplifier_structure', 'amplifier.xml')
        );
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of choices.
        $search = "/(".$base."\/mod\/amplifier\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@AMPLIFIERINDEX*$2@$', $content);

        // Link to choice view by moduleid.
        $search = "/(".$base."\/mod\/amplifier\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@AMPLIFIERVIEWBYID*$2@$', $content);
        return $content;
    }
}

