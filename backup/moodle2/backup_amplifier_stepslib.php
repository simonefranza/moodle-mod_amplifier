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
 * Structure step of the backup
 *
 * @package   mod_amplifier
 * @category  backup
 * @copyright 2025 onwards Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that will be used by the
 * backup_amplifier_activity_task
 */
class backup_amplifier_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure of the backup
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $amplifier = new backup_nested_element('amplifier', ['id'],
            ['name', 'intro', 'introformat', 'timecreated', 'timemodified', 'learninggoalwidgetid']);

        $goals = new backup_nested_element('goals');

        $goal = new backup_nested_element('goal', ['id'], ['lgwgoalid', 'userid']);

        $reminders = new backup_nested_element('reminders');

        $reminder = new backup_nested_element('reminder', ['id'],
          [
            'startdate',
            'enddate',
            'timezone',
            'reminderhour',
            'reminderminute',
            'frequency',
            'lastnotificationdate',
          ]);

        $reflections = new backup_nested_element('reflections');

        $reflection = new backup_nested_element('reflection', ['id'],
            ['response', 'timecreated']);

        // Build the tree.
        $amplifier->add_child($goals);
        $goals->add_child($goal);

        $goal->add_child($reminders);
        $reminders->add_child($reminder);

        $goal->add_child($reflections);
        $reflections->add_child($reflection);

        // Define sources.
        $amplifier->set_source_table('amplifier', ['id' => backup::VAR_ACTIVITYID]);

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $goal->set_source_table('amplifier_goals', ['amplifierid' => backup::VAR_ACTIVITYID]);
            $reminder->set_source_table('amplifier_reminders', ['amplifiergoalid' => backup::VAR_PARENTID]);
            $reflection->set_source_table('amplifier_reflections', ['amplifiergoalid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $goal->annotate_ids('user', 'userid');

        // Return the root element (amplifier), wrapped into standard activity structure.
        return $this->prepare_activity_structure($amplifier);
    }
}
