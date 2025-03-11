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
 * Amplifier Widget Setup
 *
 * @module    mod_amplifier/amplifier-setup
 * @copyright 2020 Know-Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Controller from 'mod_amplifier/controller';
import Notification from "core/notification";

/**
 * Initialising the setup of the amplifier widget
 *
 * @param {object} instanceId The course module instance identifier
 */
const init = (instanceId) => {
  const rootElement = document.querySelector(`#amplifier-widget-${instanceId}`);

  const submitButton = rootElement.querySelector(".amplifier-setup .amplifier-submit-setup");
  submitButton.addEventListener('click', handleSubmitButtonClick);

  const checkboxes = rootElement.querySelectorAll(".amplifier-setup .predefined-learning-goal-check");
  checkboxes.forEach((el) => el.addEventListener('change', parseCheckboxes));
};

/**
 * Amplifier setup submit button handler
 * @param {Event} e Click event
 */
const handleSubmitButtonClick = async(e) => {
  const {learningGoals, submitEnabled} = parseCheckboxes(e);

  if (!submitEnabled) {
    // Submit is disabled
    return;
  }

  const rootElement = e.target.closest('.mod_amplifier');

  // Submit the settings and trigger loading landing page of amplifier widget
  try {
    await Controller.submitSetup({
      instanceid: rootElement.dataset.instanceId,
      learninggoals: JSON.stringify(learningGoals)
    });
    location.reload();
  } catch (e) {
    Notification.exception(e)
    .then(() => location.reload());
  }
};

/**
 * Parses the selected checkboxes and returns the learningGoals
 * @param {Event} e Click event
 * @returns {object} Object with selected learningGoals and whether submission is possible
 */
const parseCheckboxes = (e) => {
  const rootElement = e.target.closest('.mod_amplifier');
  const submitButton = rootElement.querySelector(".amplifier-setup .amplifier-submit-setup");
  const checkboxes = rootElement.querySelectorAll(".amplifier-setup .predefined-learning-goal-check");

  let learningGoals = [];
  checkboxes.forEach((checkbox) => {
    if (!checkbox.checked) {
      return;
    }
    learningGoals.push({
      topicid: parseInt(checkbox.dataset.topicid),
      goalid: parseInt(checkbox.dataset.goalid),
    });
  });
  const submitEnabled = handleNewCount(submitButton, learningGoals.length);
  return {learningGoals, submitEnabled};
};

/**
 * Handle a new count of checked checkboxes
 * @param {HTMLElement} submitButton Button to enable/disable
 * @param {Int} newCount New count
 * @returns {Bool} submitEnabled Whether the submit button is enabled or not
 */
const handleNewCount = (submitButton, newCount) => {
  const submitEnabled = newCount > 0 && newCount <= 5;

  if (submitEnabled) {
    submitButton.removeAttribute('disabled');
  } else {
    submitButton.setAttribute('disabled', true);
  }

  return submitEnabled;
};

export default {
  init: init
};
