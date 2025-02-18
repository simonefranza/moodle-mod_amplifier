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
 * amplifier module upgrade
 *
 * @package   mod_amplifier
 * @copyright 2025 Know Cener GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file keeps track of upgrades to
// the amplifier module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

/**
 * deletes a foreign key from a table
 *
 * @param object $dbman
 * @param string $tablename
 * @param string $name
 * @param string[] $fields
 * @param string $reftable
 * @param string[] $reffields
 * @return void
 */
function delete_foreign_key($dbman, $tablename, $name, $fields, $reftable, $reffields) {
    $table = new xmldb_table($tablename);
    $key = new xmldb_key($name, XMLDB_KEY_FOREIGN, $fields, $reftable, $reffields);
    $dbman->drop_key($table, $key);
}

/**
 * Adds a foreign key from a table
 *
 * @param object $dbman
 * @param string $tablename
 * @param string $name
 * @param string[] $fields
 * @param string $reftable
 * @param string[] $reffields
 * @return void
 */
function add_foreign_key($dbman, $tablename, $name, $fields, $reftable, $reffields) {
    $table = new xmldb_table($tablename);
    $key = new xmldb_key($name, XMLDB_KEY_FOREIGN, $fields, $reftable, $reffields);
    $dbman->add_key($table, $key);
}

/**
 * upgrade amplifier
 *
 * @param int $oldversion
 * @return void
 */
function xmldb_amplifier_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2025021400) {
        upgrade1($dbman);

        // Amplifier savepoint reached.
        upgrade_mod_savepoint(true, 2025021400, 'amplifier');
    }

    return true;
}

/**
 * upgrade amplifier for oldversion < 2025021400
 *
 * @param xmldb $dbman
 * @return void
 */
function upgrade1($dbman) {
    // Remove foreign keys to learninggoalwidget.
    // Remove amplifier_setup_reflection->fk_topic.
    delete_foreign_key($dbman, 'amplifier_setup_reflection', 'fk_topic', ['topic'], 'learninggoalwidget_topic', ['id']);
    // Remove amplifier_setup_reflection->fk_goal.
    delete_foreign_key($dbman, 'amplifier_setup_reflection', 'fk_goal', ['goal'], 'learninggoalwidget_goal', ['id']);
    // Remove amplifier_setup_goals->fk_topic.
    delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_topic', ['topic'], 'learninggoalwidget_topic', ['id']);
    // Remove amplifier_setup_goals->fk_goal.
    delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_goal', ['goal'], 'learninggoalwidget_goal', ['id']);

    // Add foreign keys to new learninggoalwidget tables.
    // Add amplifier_setup_reflection->fk_topic.
    add_foreign_key($dbman, 'amplifier_setup_reflection', 'fk_topic', ['topic'], 'learninggoalwidget_topics', ['id']);
    // Add amplifier_setup_reflection->fk_goal.
    add_foreign_key($dbman, 'amplifier_setup_reflection', 'fk_goal', ['goal'], 'learninggoalwidget_goals', ['id']);
    // Add amplifier_setup_goals->fk_topic.
    add_foreign_key($dbman, 'amplifier_setup_goals', 'fk_topic', ['topic'], 'learninggoalwidget_topics', ['id']);
    // Add amplifier_setup_goals->fk_goal.
    add_foreign_key($dbman, 'amplifier_setup_goals', 'fk_goal', ['goal'], 'learninggoalwidget_goals', ['id']);
}
