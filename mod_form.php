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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

use mod_amplifier\local\taxonomy;

/**
 * Training Amplifier Widget form
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_amplifier_mod_form extends moodleform_mod {

    /**
     * setup the form
     *
     * @return void
     */
    public function definition(): void {
        global $PAGE, $DB;

        $PAGE->force_settings_menu();

        $mform = $this->_form;
        $courseid = $this->get_course()->id;
        // Check that there is at least one learninggoalwidget in this course.
        if ($requiredmodule = $DB->get_record('modules', ['name' => 'learninggoalwidget'])) {
            $exists = $DB->record_exists('course_modules', [
                'course' => $courseid,
                'module' => $requiredmodule->id,
            ]);

            if (!$exists) {
                // Stop the form from displaying by throwing an error.
                throw new moodle_exception('exception:requiredactivitymissing', 'mod_amplifier',
                    new moodle_url('/course/view.php', ['id' => $courseid]));
            }
        } else {
            throw new moodle_exception('exception:requiredactivitypluginmissing', 'mod_amplifier',
                new moodle_url('/course/view.php', ['id' => $courseid]));
        }
        $mform->addElement('header', 'general', get_string('general'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $amplifierrecord = $DB->get_record('amplifier', ['id' => $this->_instance]);
        if ($amplifierrecord) {
            $lgwinstance = $DB->get_record('learninggoalwidget', ['id' => (int)$amplifierrecord->learninggoalwidgetid], 'id, name');
            $widgetname = isset($lgwinstance->name) ? $lgwinstance->name : 'wtf';
            // If widget has already been setup don't allow to change LGW (to avoid further complexity).
            $mform->addElement('static', 'lgwname', get_string('modform:selectlearninggoalwidget', 'mod_amplifier'), $widgetname);
            $mform->addElement('hidden', 'learninggoalwidgetid', $lgwinstance->id);
            $mform->setType('learninggoalwidgetid', PARAM_INT);
        } else {
            $lgwinstances = $DB->get_records('learninggoalwidget', ['course' => $courseid], '', 'id, name');

            // Prepare options for the dropdown.
            $options = [];
            foreach ($lgwinstances as $instance) {
                $options[$instance->id] = $instance->name;
            }

            // Add dropdown to form.
            $mform->addElement('select', 'learninggoalwidgetid', get_string('modform:selectlearninggoalwidget', 'mod_amplifier'), $options);
            $mform->setType('learninggoalwidgetid', PARAM_INT);
            $mform->addRule('learninggoalwidgetid', get_string('modform:required'), 'required');
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);
    }

    /**
     * Dummy stub method - override if you needed to perform some extra validation.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     *
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     *
     * @param  array $data  array of ("fieldname"=>value) of submitted data
     * @param  array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    /**
     * Allows modules to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data passed by reference
     */
    public function data_postprocessing($data) {
    }
}
