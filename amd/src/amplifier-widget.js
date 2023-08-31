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
 * Amplifier Widget
 *
 * @module    mod_amplifier/amplifier-widget
 * @copyright University of Technology Graz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* eslint no-eval: 0 */
/* eslint no-bitwise: 0 */

define(
    [
        "jquery",
        "mod_amplifier/controller"
    ], function(
        $,
        Controller
    ) {

    /**
     * Amplifier Root Html Element
     */
    var amplifier;

    /**
     * The user identifier
     */
    var userid;

    /**
     * The course identifier
     */
    var courseid;

    /**
     * The course module identifier
     */
    var coursemoduleid;

    /**
     * The course module instance identifier
     */
    var instanceid;

    /**
     * The users participant code
     */
    var participantcode;

    /**
     * Reference to current fieldset
     */
    var currentFieldset;

    /**
     * Reference to next fieldset
     */
    var nextFieldset;

    /**
     * Intialise the content widget
     *
     * @param {object} userId The user identifier
     * @param {object} courseId The course identifier
     * @param {object} courseModuleId The course module identifier
     * @param {object} instanceId The course module instance identifier
     * @param {object} participantCode The participant code
     */
    var init = function(userId, courseId, courseModuleId, instanceId, participantCode) {

        let amplifierRoot = $(`#amplifier-widget-${courseId}-${courseModuleId}-${instanceId}`);

        amplifier = amplifierRoot;
        userid = userId;
        courseid = courseId;
        coursemoduleid = courseModuleId;
        instanceid = instanceId;
        participantcode = participantCode;

        $(amplifier).find(".topic-card .user-goal-reminder").each(function() {

            if ($(this)[0].hasAttribute("data-reminderfrequency")) {
                let frequency = $(this).attr("data-reminderfrequency");
                if (frequency == 0) {
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_daily]`)
                        .attr('checked', 'checked');
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_daily]`)
                        .parent().addClass("active");
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_weekly]`)
                        .parent().removeClass("active");
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_monthly]`)
                        .parent().removeClass("active");
                } else if (frequency == 1) {
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_weekly]`)
                        .attr('checked', 'checked');
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_daily]`)
                        .parent().removeClass("active");
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_weekly]`)
                        .parent().addClass("active");
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_monthly]`)
                        .parent().removeClass("active");
                } else if (frequency == 2) {
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_monthly]`)
                        .attr('checked', 'checked');
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_daily]`)
                        .parent().removeClass("active");
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_weekly]`)
                        .parent().removeClass("active");
                    $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_monthly]`)
                        .parent().addClass("active");
                }
            } else {
                $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_daily]`)
                    .attr('checked', 'checked');
                $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_daily]`)
                    .parent().addClass("active");
                $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_weekly]`)
                    .parent().removeClass("active");
                $(this).find(`input[type=radio][name=reminder-frequency-options][id=option_monthly]`)
                    .parent().removeClass("active");
            }
            if ($(this)[0].hasAttribute("data-startdate-day")) {
                let startDateDay = $(this).attr("data-startdate-day");
                let startDateMonth = $(this).attr("data-startdate-month");
                let startDateYear = $(this).attr("data-startdate-year");
                let startDateHour = $(this).attr("data-startdate-hour");
                let startDateMinute = $(this).attr("data-startdate-minute");
                let endDateDay = $(this).attr("data-enddate-day");
                let endDateMonth = $(this).attr("data-enddate-month");
                let endDateYear = $(this).attr("data-enddate-year");
                let endDateHour = $(this).attr("data-enddate-hour");
                let endDateMinute = $(this).attr("data-enddate-minute");
                let reminderHour = $(this).attr("data-reminder-hour").toString().padStart(2, '0');
                let reminderMinute = $(this).attr("data-reminder-minute").toString().padStart(2, '0');
                $(this).find(`select.startdate-day option[value=${startDateDay}]`).attr('selected', 'selected');
                $(this).find(`select.startdate-month option[value=${startDateMonth}]`).attr('selected', 'selected');
                $(this).find(`select.startdate-year option[value=${startDateYear}]`).attr('selected', 'selected');
                $(this).find(`select.startdate-hour option[value=${startDateHour}]`).attr('selected', 'selected');
                $(this).find(`select.startdate-minute option[value=${startDateMinute}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-day option[value=${endDateDay}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-month option[value=${endDateMonth}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-year option[value=${endDateYear}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-hour option[value=${endDateHour}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-minute option[value=${endDateMinute}]`).attr('selected', 'selected');
                $(this).find(`select.reminder-hour option[value=${reminderHour}]`).attr('selected', 'selected');
                $(this).find(`select.reminder-minute option[value=${reminderMinute}]`).attr('selected', 'selected');
            } else {
                var currentdate = new Date();
                $(this).find(`select.startdate-day option[value=${currentdate.getDate()}]`).attr('selected', 'selected');
                $(this).find(`select.startdate-month option[value=${(currentdate.getMonth() + 1)}]`).attr('selected', 'selected');
                $(this).find(`select.startdate-year option[value=${currentdate.getFullYear()}]`).attr('selected', 'selected');
                $(this).find(`select.startdate-hour option[value=${currentdate.getHours()}]`).attr('selected', 'selected');
                $(this).find(`select.startdate-minute option[value=${0}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-day option[value=${currentdate.getDate() + 7}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-month option[value=${(currentdate.getMonth() + 1)}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-year option[value=${currentdate.getFullYear()}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-hour option[value=${currentdate.getHours()}]`).attr('selected', 'selected');
                $(this).find(`select.enddate-minute option[value=${0}]`).attr('selected', 'selected');
                $(this).find(`select.reminder-hour option[value=${currentdate.getHours()}]`).attr('selected', 'selected');
                $(this).find(`select.reminder-minute option[value=${0}]`).attr('selected', 'selected');
            }
        });

        $(amplifier).find(".next.action-button.reflective-question").click(handleNextButtonClick);
        $(amplifier).find(".submit.action-button.reflection-submit").click(handleReflectionSubmitButtonClick);
        $(amplifier).find(".reminder-dropdown-toggle").on("click", function() {
            $(`#${$(this).attr("data-target")}`).toggleClass("d-none");
        });
        $(amplifier).find(".reflection-dropdown-toggle").on("click", function() {
            $(`#${$(this).attr("data-target")}`).toggleClass("d-none");
            $(`#${$(this).attr("data-target")} .user-goal-reflection fieldset`).first().toggleClass("d-none");
        });
        $(amplifier).find(".user-goal-reminder-save").on("click", handleReminderSubmitButtonClick);

    };

    /**
     * Next button handler
     */
    var handleNextButtonClick = function() {

        currentFieldset = $(this).parent().parent();
        nextFieldset = $(this).parent().parent().next();

        // Show the next fieldset
        currentFieldset.addClass("d-none");
        nextFieldset.removeClass("d-none");
    };

    /**
     * Reminder submit button handler
     */
    var handleReminderSubmitButtonClick = function() {

        currentFieldset = $(this).parent().parent();
        currentFieldset.parent().addClass("d-none");

        let amplifiergoalid = $(this).attr("data-goalid");
        let radioButton = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find("input[type=radio][name=reminder-frequency-options]:checked");

        let reminderFrequency = 0;
        if (radioButton.val() !== undefined) {
            if (radioButton.val() === "daily") {
                reminderFrequency = 0;
            } else if (radioButton.val() === "weekly") {
                reminderFrequency = 1;
            } else if (radioButton.val() === "monthly") {
                reminderFrequency = 2;
            }
        }

        let startDateDay = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.startdate-day option:selected").val();
        let startDateMonth = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.startdate-month option:selected").val();
        let startDateYear = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.startdate-year option:selected").val();
        let startDateHour = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.startdate-hour option:selected").val();
        let startDateMinute = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.startdate-minute option:selected").val();
        let endDateDay = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.enddate-day option:selected").val();
        let endDateMonth = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.enddate-month option:selected").val();
        let endDateYear = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.enddate-year option:selected").val();
        let endDateHour = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.enddate-hour option:selected").val();
        let endDateMinute = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.enddate-minute option:selected").val();
        let reminderHour = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.reminder-hour option:selected").val();
        let reminderMinute = $(`#amplifier-user-goal-${amplifiergoalid}`)
            .find(".user-goal-reminder select.reminder-minute option:selected").val();

        let startdate = Date.parse(`${startDateYear}-${startDateMonth}-${startDateDay}T${startDateHour}:${startDateMinute}:00`);
        let enddate = Date.parse(`${endDateYear}-${endDateMonth}-${endDateDay}T${endDateHour}:${endDateMinute}:00`);

        Controller.saveReminder({
            startdate: startdate,
            enddate: enddate,
            reminderhour: parseInt(reminderHour),
            reminderminute: parseInt(reminderMinute),
            frequency: reminderFrequency,
            lastnotificationdate: 0,
            goal: amplifiergoalid,
            user: userid,
            course: courseid,
            coursemodule: coursemoduleid,
            instance: instanceid,
            participantcode: participantcode
        });

    };

    /**
     * Reflection submit button handler
     */
    var handleReflectionSubmitButtonClick = function() {

        currentFieldset = $(this).parent().parent();
        currentFieldset.addClass("d-none");
        currentFieldset.parent().parent().addClass("d-none");

        let amplifiergoalid = $(this).attr("data-goalid");

        let reflections = [];
        $(`#amplifier-user-goal-${amplifiergoalid}`).find(".amplifier-user-response-input").each((idx, element) => {
            reflections.push($(element).val());
        });

        Controller.submitReflections({
            reflectiondate: Date.now(),
            reflections: JSON.stringify(reflections),
            goal: amplifiergoalid,
            user: userid,
            course: courseid,
            coursemodule: coursemoduleid,
            instance: instanceid,
            participantcode: participantcode
        });

    };

    return {
        init: init
    };
});
