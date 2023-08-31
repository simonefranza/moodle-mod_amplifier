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
 * Web Service Definition for the learninggoals service.
 *
 * @package   mod_amplifier
 * @copyright University of Technology Graz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = array(
    'mod_amplifier_submit_setup' => array(
        'classname'   => 'mod_amplifier_external',
        'methodname'  => 'submit_setup',
        'classpath'   => 'mod/amplifier/externallib.php',
        'description' => 'Stores the amplifier user setup and triggers the visualisation of the amplifier widget landing page',
        'type'        => 'write',
        'ajax'        => true
    ),
    'mod_amplifier_submit_reflections' => array(
        'classname'   => 'mod_amplifier_external',
        'methodname'  => 'submit_reflections',
        'classpath'   => 'mod/amplifier/externallib.php',
        'description' => 'Stores the amplifier user setup and triggers the visualisation of the amplifier widget landing page',
        'type'        => 'write',
        'ajax'        => true
    ),
    'mod_amplifier_save_reminder' => array(
        'classname'   => 'mod_amplifier_external',
        'methodname'  => 'save_reminder',
        'classpath'   => 'mod/amplifier/externallib.php',
        'description' => '',
        'type'        => 'write',
        'ajax'        => true
    ),
);
