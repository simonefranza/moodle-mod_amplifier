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
 * Library of functions and constants for module amplifier
 *
 * @package mod_amplifier
 * @copyright  2021 Know Center GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");

use mod_amplifier\event\course_module_instance_list_viewed;

$id = required_param('id', PARAM_INT);           // Course ID

// Ensure that the course specified is valid
if (!$course = $DB->get_record('course', array('id' => $id))) {
    throw new moodle_exception('Course ID is incorrect', 'amplifier');
}

$PAGE->set_url('/mod/amplifier/index.php', array('id' => $id));

$coursecontext = context_course::instance($id);

require_login($course);

$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => $coursecontext
);

$event = course_module_instance_list_viewed::create($params);
$event->trigger();

// Print the header.
$strwidgets = get_string("modulenameplural", "amplifier");
$PAGE->navbar->add($strwidgets);
$PAGE->set_title($strwidgets);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading($strwidgets, 2);


// Get all the appropriate data.
if (!$widgets = get_all_instances_in_course("amplifier", $course)) {
    notice(get_string('thereareno', 'moodle', $strwidgets), "../../course/view.php?id=$course->id");
    die;
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
foreach ($widgets as $widget) {
    $cm = get_coursemodule_from_instance('amplifier', $widget->id);
    $context = context_module::instance($cm->id);
    $data = array();

    // Section number if necessary.
    $strsection = '';
    if ($widget->section != $currentsection) {
        if ($widget->section) {
            $strsection = $widget->section;
            $strsection = get_section_name($course, $widget->section);
        }
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $widget->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$widget->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$widget->coursemodule\">" .
        format_string($widget->name, true) . '</a>';


    $table->data[] = $data;
} // End of loop over quiz instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
