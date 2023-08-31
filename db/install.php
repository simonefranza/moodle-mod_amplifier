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
 * Create Learning Goal Competency Framework upon installation
 *
 * @package   mod_amplifier
 * @copyright 2021 KnowCenter GmbH {@link http://www.know-center.at}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Code run after the mod_amplifier module database tables have been created.
 * create the Training Amplifier Widget competency framework record which all instances of
 * the activity refers to
 *
 * @return bool
 */
function xmldb_amplifier_install() {

    return true;
}
