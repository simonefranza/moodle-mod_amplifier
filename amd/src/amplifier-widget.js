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
     * The course module instance identifier
     */
    var instanceid;

    /**
     * Intialise the content widget
     *
     * @param {object} instanceId The course module instance identifier
     */
    var init = function(instanceId) {
      const amplifier = document.querySelector(`#amplifier-widget-${instanceId}`);
      instanceid = instanceId;

      amplifier.querySelectorAll(".topic-card").forEach((el) => {
        const reminderBtn = el.querySelector(".reminder-dropdown-toggle");
        const reflectionBtn = el.querySelector(".reflection-dropdown-toggle");
        reminderBtn.addEventListener("click", () => toggleView(reminderBtn, reflectionBtn));
        reflectionBtn.addEventListener("click", () => toggleView(reflectionBtn, reminderBtn));

        // Monitor input to enable/disable submit button
        const reflectionSubmitBtn = el.querySelector(".submit.action-button.reflection-submit");
        el.querySelector('textarea').addEventListener('input', (e) => checkInput(e, reflectionSubmitBtn));
        const reflectionBlock = el.querySelector('.user-goal-reflection');
        reflectionSubmitBtn.addEventListener('click', (e) => submitReflection(e, reflectionBlock));

        // Setup reminder view
        const reminderBlock = el.querySelector('.user-goal-reminder');
        setFrequency(reminderBlock);
        setReminderDates(reminderBlock);
        addDateChecker(reminderBlock);
        // Handle submit button
        el.querySelector(".user-goal-reminder-save")
          .addEventListener("click", (e) => submitReminder(e, reminderBlock));
      });
    };

    /**
     * Toggles one of the two views (reflection or reminder) and closes the other
     * @param {HTMLElement} toToggle Element to toggle
     * @param {HTMLElement} toClose Element to close
     */
    const toggleView = (toToggle, toClose) => {
      document.querySelector('#' + toToggle.dataset.target).classList.toggle('d-none');
      document.querySelector('#' + toClose.dataset.target).classList.add('d-none');
    };

    /**
     * Monitors the content of the reflection textarea and enables/disables the submit
     * button if there is/isn't content
     * @param {Event} e Input event
     * @param {HTMLElement} btn Button to enable/disable
     */
    const checkInput = (e, btn) => {
      // Submit is enabled if there is content
      btn.disabled = e.target.value.trim() === '';
    };

    /**
     * Reminder submit button handler
     * @param {Event} e Click event
     * @param {HTMLElement} el Root reminder element
     */
    const submitReminder = (e, el) => {
      const amplifierGoalId = e.target.dataset.goalid;

      el.parentElement.classList.add('d-none');

      let radioButton = el.querySelector(`input[type=radio]` +
        `[name=reminder-frequency-options-${amplifierGoalId}]:checked`);
      if (!radioButton) {
        return;
      }
      const checkedFrequency = radioButton.value;

      let frequency = 0;
      if (checkedFrequency === "weekly") {
        frequency = 1;
      } else if (checkedFrequency === "monthly") {
        frequency = 2;
      }

      const postfix = 'option:checked';
      let prefix = 'select.startdate-';
      const startDate = parseDate(el, prefix, postfix);

      prefix = 'select.enddate-';
      const endDate = parseDate(el, prefix, postfix);

      prefix = 'select.reminder-';
      let reminderHour = parseInt(el.querySelector(`${prefix}hour ${postfix}`).value);
      let reminderMinute = parseInt(el.querySelector(`${prefix}minute ${postfix}`).value);

      Controller.saveReminder({
        startdate: startDate.getTime(),
        enddate: endDate.getTime(),
        reminderhour: reminderHour,
        reminderminute: reminderMinute,
        frequency: frequency,
        amplifiergoalid: amplifierGoalId,
        instanceid: instanceid,
      });
    };

    /**
     * Reflection submit button handler
     * @param {Event} e Click event
     * @param {HTMLElement} el Root reflection element
     */
    const submitReflection = (e, el) => {
      const textarea = el.querySelector('textarea');
      const reflection = textarea.value.trim();
      if (reflection === '') {
        return;
      }

      el.parentElement.classList.add('d-none');

      Controller.submitReflections({
        reflection: reflection,
        amplifiergoalid: e.target.dataset.amplifiergoalid,
        instanceid: instanceid,
      });
    };

    /**
     * Sets the correct frequency
     * @param {HTMLElement} el Element
     */
    const setFrequency = (el) => {
      let frequency = 0;
      if (el.hasAttribute('data-reminderfrequency')) {
        frequency = parseInt(el.getAttribute("data-reminderfrequency"));
      }
      let targetStr = 'daily';
      if (frequency === 1) {
        targetStr = 'weekly';
      } else if (frequency === 2) {
        targetStr = 'monthly';
      }
      const saveReminderButton = el.querySelector('.user-goal-reminder-save');
      const amplifierGoalId = saveReminderButton.dataset.goalid;

      const inputEl = el.querySelector(`input[type=radio]` +
        `[name=reminder-frequency-options-${amplifierGoalId}].option_${targetStr}`);
      inputEl.checked = true;
      inputEl.parentElement.classList.add("active");
    };

    /**
     * Sets the desired start and end date in the time picker
     * @param {HTMLElement} el Root element
     */
    const setReminderDates = (el) => {
      const startDate = new Date();
      const endDate = new Date();
      const reminderDate = new Date();

      if ('startdate' in el.dataset && 'enddate' in el.dataset) {
        startDate.setTime(parseInt(el.dataset.startdate));
        endDate.setTime(parseInt(el.dataset.enddate));
        reminderDate.setHours(parseInt(el.dataset.reminderHour), parseInt(el.dataset.reminderMinute));
      } else {
        endDate.setDate(startDate.getDate() + 7);
        reminderDate.setTime(endDate.getTime());
      }
      startDate.setMinutes(0);
      endDate.setMinutes(0);
      reminderDate.setMinutes(0);
      setDate(el, 'select.startdate-', startDate);
      setDate(el, 'select.enddate-', endDate);

      // Set reminder time.
      const reminderHour = reminderDate.getHours();
      const reminderMinute = reminderDate.getMinutes();
      el.querySelector(`select.reminder-hour option[value="${reminderHour}"]`)
        .setAttribute('selected', 'selected');
      el.querySelector(`select.reminder-minute option[value="${reminderMinute}"]`)
        .setAttribute('selected', 'selected');
    };

    /**
     * Sets the date in the select element
     * @param {HTMLElement} el Root element
     * @param {string} prefix Prefix of the class (startdate|enddate)
     * @param {Date} date Date to set
     */
    const setDate = (el, prefix, date) => {
      const day = date.getDate();
      const month = date.getMonth() + 1;
      const year = date.getFullYear();
      const hour = date.getHours();
      const minute = date.getMinutes();
      el.querySelector(`${prefix}day option[value="${day}"]`)
        .setAttribute('selected', 'selected');
      el.querySelector(`${prefix}month option[value="${month}"]`)
        .setAttribute('selected', 'selected');
      el.querySelector(`${prefix}year option[value="${year}"]`)
        .setAttribute('selected', 'selected');
      el.querySelector(`${prefix}hour option[value="${hour}"]`)
        .setAttribute('selected', 'selected');
      el.querySelector(`${prefix}minute option[value="${minute}"]`)
        .setAttribute('selected', 'selected');
    };

    /**
     * Updates the number of days in the picker
     * @param {HTMLElement} selectElement Select element
     * @param {number} month Current month
     * @param {number} year Current year
     */
    function updateDays(selectElement, month, year) {
        const daysInMonth = new Date(year, month, 0).getDate();
        const currentValue = parseInt(selectElement.value);
        selectElement.innerHTML = "";

        for (let i = 1; i <= daysInMonth; i++) {
            const option = document.createElement("option");
            option.value = i;
            option.textContent = i;
            selectElement.appendChild(option);
        }

        if (currentValue <= daysInMonth) {
            selectElement.value = currentValue;
        } else {
            selectElement.value = daysInMonth;
        }
    }

    /**
     * Checks that the dates are valid (i.e. start before end)
     * @param {Object} start Object with day, month, year, hour and minute elements
     * @param {Object} end Object with day, month, year, hour and minute elements
     */
    function validateDates(start, end) {
      let {day: startDay, month: startMonth, year: startYear, hour: startHour, minute: startMinute} = start;
      let {day: endDay, month: endMonth, year: endYear, hour: endHour, minute: endMinute} = end;
      const startDate = new Date(
        parseInt(startYear.value),
        parseInt(startMonth.value) - 1,
        parseInt(startDay.value),
        parseInt(startHour.value),
        parseInt(startMinute.value),
      );

      const endDate = new Date(
        parseInt(endYear.value),
        parseInt(endMonth.value) - 1,
        parseInt(endDay.value),
        parseInt(endHour.value),
        parseInt(endMinute.value),
      );

      if (startDate > endDate) {
        endYear.value = startYear.value;
        endMonth.value = startMonth.value;
        endDay.value = startDay.value;
        endHour.value = startHour.value;
        endMinute.value = startMinute.value;
      }
    }

    /**
     * Makes sure that only valid dates for start and end date are selectable
     * @param {HTMLElement} el Root element
     */
    const addDateChecker = (el) => {
      const start = {
        day: el.querySelector(".startdate-day"),
        month: el.querySelector(".startdate-month"),
        year: el.querySelector(".startdate-year"),
        hour: el.querySelector(".startdate-hour"),
        minute : el.querySelector(".startdate-minute"),
      };
      const end = {
        day: el.querySelector(".enddate-day"),
        month: el.querySelector(".enddate-month"),
        year: el.querySelector(".enddate-year"),
        hour: el.querySelector(".enddate-hour"),
        minute: el.querySelector(".enddate-minute"),
      };

      [start.month, start.year].forEach(el => el.addEventListener("change", () => {
        updateDays(start.day, parseInt(start.month.value), parseInt(start.year.value));
        validateDates(start, end);
      }));

      [end.month, end.year].forEach(el => el.addEventListener("change", () => {
        updateDays(end.day, parseInt(end.month.value), parseInt(end.year.value));
        validateDates(start, end);
      }));

      [start.day, start.hour, start.minute, end.day, end.hour, end.minute]
        .forEach(el => el.addEventListener("change", () => {
          validateDates(start, end);
        }));

      updateDays(start.day, parseInt(start.month.value), parseInt(start.year.value));
      updateDays(end.day, parseInt(end.month.value), parseInt(end.year.value));
      validateDates(start, end);
    };

    /**
     * Parses a date from the select elements
     * @param {HTMLElement} el Parent element of select
     * @param {string} prefix Prefix of the class
     * @param {string} postfix Postfix of the class
     * @returns {Date} Parsed date
     */
    function parseDate(el, prefix, postfix) {
      const date = new Date();
      date.setFullYear(
        parseInt(el.querySelector(`${prefix}year ${postfix}`).value),
        parseInt(el.querySelector(`${prefix}month ${postfix}`).value) - 1,
        parseInt(el.querySelector(`${prefix}day ${postfix}`).value)
      );
      date.setHours(
        parseInt(el.querySelector(`${prefix}hour ${postfix}`).value),
        parseInt(el.querySelector(`${prefix}minute ${postfix}`).value),
        0
      );
      return date;
    }

    return {
        init: init
    };
});
