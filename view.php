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
 * Prints an instance of mod_amplifier.
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'amplifier');

require_login($course, true, $cm);

if ($id) {
    $PAGE->set_url('/mod/amplifier/index.php', array('id' => $id));
    if (!$cm = get_coursemodule_from_id('amplifier', $id)) {
        throw new moodle_exception('invalidcoursemodule');
    }

    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        throw new moodle_exception('coursemisconf');
    }

    if (!$label = $DB->get_record("amplifier", array("id" => $cm->instance))) {
        throw new moodle_exception('invalidcoursemodule');
    }
}

redirect("$CFG->wwwroot/course/view.php?id=$course->id");
