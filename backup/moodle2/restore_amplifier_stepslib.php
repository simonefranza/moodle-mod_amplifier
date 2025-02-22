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
 * Structure step of the restore process
 *
 * @package   mod_amplifier
 * @category  backup
 * @copyright 2025 onwards Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restores steps that will be used by the
 * restore_amplifier_activity_task
 */

/**
 * Structure step to restore one amplifier activity
 */
class restore_amplifier_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore process
     */
    protected function define_structure() {

        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('amplifier', '/activity/amplifier');
        if ($userinfo) {
            $paths[] = new restore_path_element('amplifier_goal', '/activity/amplifier/goals/goal');
            $paths[] = new restore_path_element('amplifier_reminder', '/activity/amplifier/goals/goal/reminders/reminder');
            $paths[] = new restore_path_element('amplifier_reflection', '/activity/amplifier/goals/goal/reflections/reflection');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a amplifier element
     *
     * @param object $data
     */
    protected function process_amplifier($data): void {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        // Invert ID so that we later know which need to be changed.
        $data->learninggoalwidgetid *= -1;

        // Insert the amplifier record.
        $newitemid = $DB->insert_record('amplifier', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process a goal element
     *
     * @param object $data
     */
    protected function process_amplifier_goal($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->amplifierid = $this->get_new_parentid('amplifier');
        // Invert ID so that we later know which need to be changed.
        $data->lgwgoalid *= -1;
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('amplifier_goals', $data);
        $this->set_mapping('amplifier_goal', $oldid, $newitemid);
    }

    /**
     * Process a reminder element
     *
     * @param object $data
     */
    protected function process_amplifier_reminder($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->amplifiergoalid = $this->get_new_parentid('amplifier_goal');

        $newitemid = $DB->insert_record('amplifier_reminders', $data);
        $this->set_mapping('amplifier_reminder', $oldid, $newitemid);
    }

    /**
     * Process a reflection element
     *
     * @param object $data
     */
    protected function process_amplifier_reflection($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->amplifiergoalid = $this->get_new_parentid('amplifier_goal');

        $newitemid = $DB->insert_record('amplifier_reflections', $data);
        $this->set_mapping('amplifier_reflection', $oldid, $newitemid);
    }

    /**
     * Function to execute after restore is complete
     * Restore the LGW IDs if possible
     */
    protected function after_execute(): void {
        global $DB;
        $instanceid = $this->get_new_parentid('amplifier');
        $record = $DB->get_record('amplifier', ['id' => $instanceid]);
        $oldlgwid = $record->learninggoalwidgetid * -1;
        $newlgwid = $this->get_mappingid('learninggoalwidget', $oldlgwid);
        if ($newlgwid == false) {
            $this->get_logger()->process("mod_amplifier depends on the data of mod_learninggoalwidget." .
              "Failed to find the restored instance of the learninggoalwidget." .
              "Backup and restore will not work correctly unless you include the learninggoalwidget.",
              backup::LOG_ERROR);
            return;
        }
        $record->learninggoalwidgetid = $newlgwid;
        $DB->update_record('amplifier', $record);

        // Update ID of amplifier_goals.lgwgoalid.
        $goals = $DB->get_records('amplifier_goals', ['amplifierid' => $instanceid]);
        foreach ($goals as $goal) {
            $oldlgwgoalid = $goal->lgwgoalid * -1;
            $newlgwgoalid = $this->get_mappingid('learninggoalwidget_goal', $oldlgwgoalid);
            if ($newlgwgoalid == false) {
                $this->get_logger()->process("mod_amplifier depends on the data of mod_learninggoalwidget." .
                  "Failed to find the new ID of the learninggoalwidget_goal that had the ID " . $oldlgwgoalid . "." .
                  "Backup and restore will not work correctly unless you include the whole original learninggoalwidget. Aborting.",
                  backup::LOG_ERROR);
                return;
            }
            $goal->lgwgoalid = $newlgwgoalid;
            $DB->update_record('amplifier_goals', $goal);
        }
    }
}
