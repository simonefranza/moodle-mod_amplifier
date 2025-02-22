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
//
/**
 * Function to get the first learninggoalwidget ID from a course
 *
 * @param number $courseid
 * @return number
 */
function xmldb_amplifier_get_lgwid_from_course($courseid) {
    global $DB;
    // Get LGW instance ID from courseID.
    $stmt = "SELECT lgw.id as lgwid
               FROM {course_modules} cm
               JOIN {modules} m ON cm.module = m.id
               JOIN {learninggoalwidget} lgw ON cm.instance = lgw.id
              WHERE m.name = 'learninggoalwidget'
                AND cm.course = :courseid";
    $params = ["courseid" => $courseid];
    $data = $DB->get_records_sql($stmt, $params);
    return reset($data)->lgwid;
}

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
function xmldb_amplifier_delete_foreign_key($dbman, $tablename, $name, $fields, $reftable, $reffields) {
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
function xmldb_amplifier_add_foreign_key($dbman, $tablename, $name, $fields, $reftable, $reffields) {
    $table = new xmldb_table($tablename);
    $key = new xmldb_key($name, XMLDB_KEY_FOREIGN, $fields, $reftable, $reffields);
    $dbman->add_key($table, $key);
}

/**
 * deletes an index from a table
 *
 * @param object $dbman
 * @param string $tablename
 * @param string $indexname
 * @param int $unique
 * @param string[] $fields
 * @return void
 */
function xmldb_amplifier_delete_index($dbman, $tablename, $indexname, $unique, $fields) {
    $table = new xmldb_table($tablename);
    $index = new xmldb_index($indexname, $unique, $fields);

    if ($dbman->index_exists($table, $index)) {
        $dbman->drop_index($table, $index);
    }
}

/**
 * Adds an index to a table
 *
 * @param object $dbman
 * @param string $tablename
 * @param string $indexname
 * @param int $unique XMLDB_INDEX_NOTUNIQUE |
 * @param string[] $fields
 * @return void
 */
function xmldb_amplifier_add_index($dbman, $tablename, $indexname, $unique, $fields) {
    $table = new xmldb_table($tablename);
    $index = new xmldb_index($indexname, $unique, $fields);

    if (!$dbman->index_exists($table, $index)) {
        $dbman->add_index($table, $index);
    }
}

/**
 * Adds a key to a table
 *
 * @param object $dbman
 * @param string $tablename
 * @param string $name
 * @param number $type
 * @param number $length
 * @param number $isnull
 * @param number $sequence
 * @param * $default
 * @return void
 */
function xmldb_amplifier_add_field($dbman, $tablename, $name, $type, $length, $isnull, $sequence, $default) {
    $table = new xmldb_table($tablename);
    $field = new xmldb_field($name, $type, $length, null, $isnull, $sequence, $default, null);

    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}

/**
 * Renames a table
 *
 * @param object $dbman
 * @param string $tablename
 * @param string $newtablename
 * @return void
 */
function xmldb_amplifier_rename_table($dbman, $tablename, $newtablename) {
    $table = new xmldb_table($tablename);

    $dbman->rename_table($table, $newtablename);
}

/**
 * Drops a table
 *
 * @param object $dbman
 * @param string $tablename
 * @return void
 */
function xmldb_amplifier_drop_table($dbman, $tablename) {
    $table = new xmldb_table($tablename);

    // Conditionally launch drop table for amplifier_setup.
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }
}

/**
 * rename a field of a table
 *
 * @param object $dbman
 * @param string $tablename
 * @param object $field
 * @param string $newname
 * @return void
 */
function xmldb_amplifier_rename_field($dbman, $tablename, $field, $newname) {
    $table = new xmldb_table($tablename);
    if ($dbman->field_exists($table, $field)) {
        $dbman->rename_field($table, $field, $newname);
    }
}

/**
 * Deletes a field from a table
 *
 * @param object $dbman
 * @param string $tablename
 * @param string $fieldname
 * @return void
 */
function xmldb_amplifier_delete_field($dbman, $tablename, $fieldname) {
    $table = new xmldb_table($tablename);
    $field = new xmldb_field($fieldname);

    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
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
        xmldb_amplifier_upgrade1($dbman);

        // Amplifier savepoint reached.
        upgrade_mod_savepoint(true, 2025021400, 'amplifier');
    }
    if ($oldversion < 2025021900) {
        xmldb_amplifier_upgrade2($dbman);

        // Amplifier savepoint reached.
        upgrade_mod_savepoint(true, 2025021900, 'amplifier');
    }
    if ($oldversion < 2025022005) {
        xmldb_amplifier_upgrade3($dbman);

        // Amplifier savepoint reached.
        upgrade_mod_savepoint(true, 2025022005, 'amplifier');
    }
    if ($oldversion < 2025022200) {
        xmldb_amplifier_upgrade4($dbman);

        // Amplifier savepoint reached.
        upgrade_mod_savepoint(true, 2025022200, 'amplifier');
    }
    return true;
}

/**
 * upgrade amplifier for oldversion < 2025022200
 * Add fields for timezone otherwise reminder sent at wrong time
 *
 * @param xmldb $dbman
 * @return void
 */
function xmldb_amplifier_upgrade4($dbman) {
    // Add field on amplifier_reminders.timezone.
    xmldb_amplifier_add_field($dbman, 'amplifier_reminders', 'timezone', XMLDB_TYPE_CHAR, '100', XMLDB_NOTNULL, null, 'Europe/Vienna');
}

/**
 * upgrade amplifier for oldversion < 2025022005
 *
 * @param xmldb $dbman
 * @return void
 */
function xmldb_amplifier_upgrade3($dbman) {
    // Add index on amplifier.learninggoalwidgetid.
    xmldb_amplifier_add_index($dbman, 'amplifier', 'learninggoalwidgetid', XMLDB_INDEX_NOTUNIQUE, ['learninggoalwidgetid']);
    // Add index on amplifier_reminders.startdate.
    xmldb_amplifier_add_index($dbman, 'amplifier_reminders', 'startdate', XMLDB_INDEX_NOTUNIQUE, ['startdate']);
    // Add index on amplifier_reminders.enddate.
    xmldb_amplifier_add_index($dbman, 'amplifier_reminders', 'enddate', XMLDB_INDEX_NOTUNIQUE, ['enddate']);
}

/**
 * upgrade amplifier for oldversion < 2025021900
 *
 * @param xmldb $dbman
 * @return void
 */
function xmldb_amplifier_upgrade2($dbman) {
    global $DB;

    // Modify amplifier table.
    // Add learninggoalwidget id to amplifier table.
    xmldb_amplifier_add_field($dbman, 'amplifier', 'learninggoalwidgetid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, -1);
    // Update learninggoalwidgetid for existing rows.
    $stmt = "UPDATE {amplifier} amp
                SET amp.learninggoalwidgetid = (
             SELECT lgw.id
               FROM {course_modules} cm
               JOIN {modules} m ON cm.module = m.id
               JOIN {learninggoalwidget} lgw ON cm.instance = lgw.id
              WHERE m.name = 'learninggoalwidget'
                AND cm.course = amp.course
              LIMIT 1
                    )
              WHERE amp.learninggoalwidgetid = -1";
    $DB->execute($stmt);
    // Remove course index.
    xmldb_amplifier_delete_index($dbman, 'amplifier', 'course', false, ['course']);
    // Delete key fk_course and recreate it because renaming is not allowed in production.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier', 'fk_course', ['course'], 'course', ['id']);
    // Add course->course.id.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier', 'course', ['course'], 'course', ['id'], 'course');
    // Add learninggoalwidgetid->learninggoalwidget.id.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier', 'learninggoalwidgetid', ['learninggoalwidgetid'], 'learninggoalwidget', ['id']);

    // Remove amplifier_setup_reflection as it has been removed from the setup.
    xmldb_amplifier_drop_table($dbman, 'amplifier_setup_reflection');

    // Modify amplifier_reflection table.
    // Delete all keys and indexes.
    // Delete goal index.
    xmldb_amplifier_delete_index($dbman, 'amplifier_reflection', 'goal', false, ['goal']);
    // Delete key fk_goal.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_reflection', 'fk_goal', ['goal'], 'amplifier_setup_goals', ['id']);
    // Delete key fk_course.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_reflection', 'fk_course', ['course'], 'course', ['id']);
    // Delete key fk_user.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_reflection', 'fk_user', ['amp_user'], 'user', ['id']);

    // Modify amplifier_reminder table.
    // Delete all keys and indexes.
    // Delete goal index.
    xmldb_amplifier_delete_index($dbman, 'amplifier_reminder', 'goal', false, ['goal']);
    // Delete key fk_goal.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_reminder', 'fk_goal', ['goal'], 'amplifier_setup_goals', ['id']);
    // Delete key fk_course.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_reminder', 'fk_course', ['course'], 'course', ['id']);
    // Delete key fk_user.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_reminder', 'fk_user', ['amp_user'], 'user', ['id']);

    // Modify amplifier_setup_goals table.
    // Delete all keys and indexes.
    // Delete topic index.
    xmldb_amplifier_delete_index($dbman, 'amplifier_setup_goals', 'topic', false, ['topic']);
    // Delete goal index.
    xmldb_amplifier_delete_index($dbman, 'amplifier_setup_goals', 'goal', false, ['goal']);
    // Delete course index.
    xmldb_amplifier_delete_index($dbman, 'amplifier_setup_goals', 'course', false, ['course']);
    // Delete coursemodule index.
    xmldb_amplifier_delete_index($dbman, 'amplifier_setup_goals', 'coursemodule', false, ['coursemodule']);
    // Delete setup index.
    xmldb_amplifier_delete_index($dbman, 'amplifier_setup_goals', 'setup', false, ['setup']);
    // Delete amp_user index.
    xmldb_amplifier_delete_index($dbman, 'amplifier_setup_goals', 'amp_user', false, ['amp_user']);

    // Delete key fk_topic.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_topic', ['topic'], 'learninggoalwidget_topics', ['id']);
    // Delete key fk_goal.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_goal', ['goal'], 'learninggoalwidget_goals', ['id']);
    // Delete key fk_course.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_course', ['course'], 'course', ['id']);
    // Delete key fk_setup.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_setup', ['setup'], 'amplifier_setup', ['id']);
    // Delete key fk_user.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_user', ['amp_user'], 'user', ['id']);

    // Rename instance -> amplifierid.
    $field = new xmldb_field('instance', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', null);
    xmldb_amplifier_rename_field($dbman, 'amplifier_setup_goals', $field, 'amplifierid');
    // Rename goal -> lgwgoalid (learninggoalwidget goal id).
    $field = new xmldb_field('goal', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', null);
    xmldb_amplifier_rename_field($dbman, 'amplifier_setup_goals', $field, 'lgwgoalid');
    // Rename amp_user -> userid.
    $field = new xmldb_field('amp_user', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', null);
    xmldb_amplifier_rename_field($dbman, 'amplifier_setup_goals', $field, 'userid');

    // Delete field course.
    xmldb_amplifier_delete_field($dbman, 'amplifier_setup_goals', 'course');
    // Delete field coursemodule.
    xmldb_amplifier_delete_field($dbman, 'amplifier_setup_goals', 'coursemodule');
    // Delete field topic as goal is already exact.
    xmldb_amplifier_delete_field($dbman, 'amplifier_setup_goals', 'topic');
    // Delete field setup.
    xmldb_amplifier_delete_field($dbman, 'amplifier_setup_goals', 'setup');
    // Delete field participantcode.
    xmldb_amplifier_delete_field($dbman, 'amplifier_setup_goals', 'participantcode');

    // Add foreign keys.
    // Add amplifierid->amplifier.id.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_setup_goals', 'amplifierid', ['amplifierid'], 'amplifier', ['id']);
    // Add userid->user.id.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_setup_goals', 'userid', ['userid'], 'user', ['id']);
    // Add lgwgoalid->learninggoalwidget_goals.id.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_setup_goals', 'lgwgoalid', ['lgwgoalid'], 'learninggoalwidget_goals', ['id']);

    // Rename table amplifier_setup_goals->amplifier_goals.
    xmldb_amplifier_rename_table($dbman, 'amplifier_setup_goals', 'amplifier_goals');

    // Modify amplifier_reminder.
    // The column goal is said to contain a reference to amplifier_setup_goals.id
    // But actually has a reference to learninggoalwidget_goals.id.
    $stmt = "UPDATE {amplifier_reminder} rem
               JOIN {amplifier_goals} goals
                 ON rem.goal = goals.goal
                AND rem.amp_user = goals.amp_user
                SET rem.goal = goals.id";
    $DB->execute($stmt);

    // Modify amplifier_reminder.
    // Delete field course.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reminder', 'course');
    // Delete field coursemodule.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reminder', 'coursemodule');
    // Delete field instance.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reminder', 'instance');
    // Delete field amp_user.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reminder', 'amp_user');
    // Delete field participantcode.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reminder', 'participantcode');

    // Rename field goal->amplifiergoalid.
    $field = new xmldb_field('goal', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', null);
    xmldb_amplifier_rename_field($dbman, 'amplifier_reminder', $field, 'amplifiergoalid');

    // Add amplifiergoalid->amplifier_goals.id.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_reminder', 'amplifiergoalid', ['amplifiergoalid'], 'amplifier_goals', ['id']);

    // Rename table amplifier_reminder->amplifier_reminders.
    xmldb_amplifier_rename_table($dbman, 'amplifier_reminder', 'amplifier_reminders');

    // Modify amplfier_reflection.
    // The column goal is said to contain a reference to amplifier_setup_goals.id
    // But actually has a reference to learninggoalwidget_goals.id.
    $stmt = "UPDATE {amplifier_reflection} ref
               JOIN {amplifier_goals} goals
                 ON ref.goal = goals.goal
                AND ref.amp_user = goals.amp_user
                SET ref.goal = goals.id";
    $DB->execute($stmt);
    // Delete field course.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reflection', 'course');
    // Delete field coursemodule.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reflection', 'coursemodule');
    // Delete field instance.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reflection', 'instance');
    // Delete field amp_user.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reflection', 'amp_user');
    // Delete field participantcode.
    xmldb_amplifier_delete_field($dbman, 'amplifier_reflection', 'participantcode');

    // Rename field goal->amplifiergoalid.
    $field = new xmldb_field('goal', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', null);
    xmldb_amplifier_rename_field($dbman, 'amplifier_reflection', $field, 'amplifiergoalid');
    // Rename field reflectedat->timecreated.
    $field = new xmldb_field('reflectedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', null);
    xmldb_amplifier_rename_field($dbman, 'amplifier_reflection', $field, 'timecreated');

    // Add amplifiergoalid->amplifier_goals.id.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_reflection', 'amplifiergoalid', ['amplifiergoalid'], 'amplifier_goals', ['id']);

    // Rename amplifier_reflection->amplifier_reflections.
    xmldb_amplifier_rename_table($dbman, 'amplifier_reflection', 'amplifier_reflections');

    // Remove amplifier_setup because its data is not needed.
    xmldb_amplifier_drop_table($dbman, 'amplifier_setup');
}

/**
 * upgrade amplifier for oldversion < 2025021400
 *
 * @param xmldb $dbman
 * @return void
 */
function xmldb_amplifier_upgrade1($dbman) {
    // Remove foreign keys to learninggoalwidget.
    // Remove amplifier_setup_reflection->fk_topic.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_reflection', 'fk_topic', ['topic'], 'learninggoalwidget_topic', ['id']);
    // Remove amplifier_setup_reflection->fk_goal.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_reflection', 'fk_goal', ['goal'], 'learninggoalwidget_goal', ['id']);
    // Remove amplifier_setup_goals->fk_topic.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_topic', ['topic'], 'learninggoalwidget_topic', ['id']);
    // Remove amplifier_setup_goals->fk_goal.
    xmldb_amplifier_delete_foreign_key($dbman, 'amplifier_setup_goals', 'fk_goal', ['goal'], 'learninggoalwidget_goal', ['id']);

    // Add foreign keys to new learninggoalwidget tables.
    // Add amplifier_setup_reflection->fk_topic.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_setup_reflection', 'fk_topic', ['topic'], 'learninggoalwidget_topics', ['id']);
    // Add amplifier_setup_reflection->fk_goal.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_setup_reflection', 'fk_goal', ['goal'], 'learninggoalwidget_goals', ['id']);
    // Add amplifier_setup_goals->fk_topic.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_setup_goals', 'fk_topic', ['topic'], 'learninggoalwidget_topics', ['id']);
    // Add amplifier_setup_goals->fk_goal.
    xmldb_amplifier_add_foreign_key($dbman, 'amplifier_setup_goals', 'fk_goal', ['goal'], 'learninggoalwidget_goals', ['id']);
}
