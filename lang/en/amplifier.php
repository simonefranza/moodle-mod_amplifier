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
 * Strings for component 'amplifier', language 'en'
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['amplifier'] = 'User Registration';

$string['amplifier:addinstance'] = 'Add a new Training Amplifier Widget';
$string['amplifier:setupgoals'] = 'Setup and use the Training Amplifier Widget';
$string['amplifier:view'] = 'View Training Amplifier Widget';
$string['amplifiertext'] = 'Training Amplifier Widget Text';

$string['exception:change_lgw'] = 'The target Learning Goal Widget cannot be changed. Please delete the Training Amplifier instance and create a new one.';
$string['exception:instance_not_found'] = 'The instance to update was not found.';
$string['exception:lgw_missing'] = 'The Learning Goal Widget cannot be found.';
$string['exception:requiredactivitymissing'] = 'A Learning Goal Widget must be present in the course before adding this activity.';
$string['exception:requiredactivitypluginmissing'] = 'The Learning Goal Widget is not installed.';
$string['exception:setup_done'] = 'You already setup the Training Amplifier.';

$string['guestaccess'] = 'You need to login first';

$string['message:reminder:html'] = '
<h3>Training Amplifier - Course {$a->coursetitle}</h3>
<p>
This is a reminder to reflect about the learning goal <b>{$a->goalname}</b>.
</p>
<p>
Please visit the <a href="{$a->url}">course page</a> and go to the section with the Training Amplifier Widget.
</p>
<p>
Search for the line starting with topicname <b>{$a->topicname}</b> and click on the reflection button.
</p>
<p>
Good luck!
</p>';
$string['message:reminder:subject'] = 'Training Amplifier - Learning Goal Reflection';
$string['message:reminder:text'] = '
Training Amplifier - Course {$a->coursetitle}

This is a reminder to reflect about the learning goal {$a->goalname}.

Please visit the course page and go to section with the Training Amplifier Widget.
Search for the line starting with topicname {$a->topicname} and click on the reflection button.
This is the link to the course page:
{$a->url}

Good luck!';

$string['messageprovider:reflection_reminder'] = 'Reminder to reflection about a learning goal';

$string['modform:selectlearninggoalwidget'] = 'Learning Goal Widget';

$string['modulename'] = 'Training Amplifier Widget';
$string['modulename_help'] = '';
$string['modulename_link'] = 'mod/amplifier/view';
$string['modulenameplural'] = 'Training Amplifier Widget';
$string['noaccess'] = 'You need to login first';
$string['pluginadministration'] = 'Training Amplifier Widget Administration';
$string['pluginname'] = 'Training Amplifier Widget';

$string['privacy:metadata'] = '';

$string['privacy:metadata:amplifier_goals'] = 'Information about the selected learning goals from a user.';
$string['privacy:metadata:amplifier_goals:amplifierid'] = 'The ID of an instance of the Training Amplifier.';
$string['privacy:metadata:amplifier_goals:lgwgoalid'] = 'The ID of the goal the user selected for reminders and reflections.';
$string['privacy:metadata:amplifier_goals:userid'] = 'The ID of the user.';

$string['privacy:metadata:amplifier_reflections'] = 'Information about an individual reflection by a user on a specific learning goal.';
$string['privacy:metadata:amplifier_reflections:amplifiergoalid'] = 'The ID of an amplifier_goals entry.';
$string['privacy:metadata:amplifier_reflections:response'] = 'The textual representation of the user\'s reflection.';
$string['privacy:metadata:amplifier_reflections:timecreated'] = 'The timestamp of when the reflection was created.';

$string['privacy:metadata:amplifier_reminders'] = 'Information about a user\'s reminder settings for a specific learning goal.';
$string['privacy:metadata:amplifier_reminders:amplifiergoalid'] = 'The ID of an amplifier_goals entry.';
$string['privacy:metadata:amplifier_reminders:enddate'] = 'The date when the reminders should stop.';
$string['privacy:metadata:amplifier_reminders:lastnotificationdate'] = 'The date when the last reminder was sent.';
$string['privacy:metadata:amplifier_reminders:reminderhour'] = 'The hour at which the reminder should be sent.';
$string['privacy:metadata:amplifier_reminders:reminderminute'] = 'The minute at which the reminder should be sent.';
$string['privacy:metadata:amplifier_reminders:startdate'] = 'The date when the reminders should start.';
$string['privacy:metadata:amplifier_reminders:timezone'] = 'The timezone where the reminder was created.';

$string['search:activity'] = 'amplifier';

$string['task:reminder'] = 'Lookup user set reminders and send reflection notification message';

$string['template:general:save'] = 'Save';
$string['template:general:submit'] = 'Submit';

$string['template:reflection:headline'] = "Reflection";
$string['template:reflection:placeholder'] = 'My thoughts...';
$string["template:reflection:text_1"] = "Please reflect on the following learning goal.";

$string["template:reminder:date:end"] = "End Date";
$string["template:reminder:date:start"] = "Start Date";
$string["template:reminder:frequency:daily"] = "Daily";
$string["template:reminder:frequency:monthly"] = "Monthly";
$string["template:reminder:frequency:weekly"] = "Weekly";
$string["template:reminder:headline"] = "Reminder Settings";
$string["template:reminder:label:time"] = "Daytime";
$string["template:reminder:month:01"] = "January";
$string["template:reminder:month:02"] = "February";
$string["template:reminder:month:03"] = "March";
$string["template:reminder:month:04"] = "April";
$string["template:reminder:month:05"] = "May";
$string["template:reminder:month:06"] = "June";
$string["template:reminder:month:07"] = "July";
$string["template:reminder:month:08"] = "August";
$string["template:reminder:month:09"] = "September";
$string["template:reminder:month:10"] = "October";
$string["template:reminder:month:11"] = "November";
$string["template:reminder:month:12"] = "December";

$string['template:setup:headline'] = "Welcome to the Training Amplifier";
$string['template:setup:lgw_missing'] = "The chosen Learninggoal Widget instance could not be found or has been deleted. Please delete this instance of the Training Amplifier.";
$string['template:setup:teacher'] = 'Please change your role to student to try out the Training Amplifier.';
$string['template:setup:text_1'] = "The goal of the Training Amplifier is to support YOU to transfer the theory learned in a recently attended course into practice.";
$string['template:setup:text_2'] = "Please select up to 5 goals that you would like to pursue in the following days or weeks. For each of your selected goals you can set a reminder for reflection, asking you if you already have applied the goal in practice.";
