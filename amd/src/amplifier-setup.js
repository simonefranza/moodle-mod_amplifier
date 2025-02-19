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

define(['jquery', 'mod_amplifier/controller'], function($, Controller) {

  /**
   * course module instance
   */
  let instanceId;

  /**
   * The submit button
   */
  let submitButton;

  /**
   * Wheter the submission is valid
   */
  let submitEnabled = false;

  /**
   * All chekboxes in the widget
   */
  let checkboxes;

  /**
   * Number of checked checkboxes
   */
  let checkedCount = 0;

  /**
   * Initialising the setup of the amplifier widget
   *
   * @param {object} paramInstanceId The course module instance identifier
   */
  const init = (paramInstanceId) => {
    instanceId = paramInstanceId;

    const rootElement = document.querySelector(`#amplifier-widget-${instanceId}`);

    submitButton = rootElement.querySelector(".amplifier-setup .amplifier-submit-setup");
    submitButton.addEventListener('click', handleSubmitButtonClick);

    checkboxes = rootElement.querySelectorAll(".amplifier-setup .predefined-learning-goal-check");
    checkboxes.forEach((el) => el.addEventListener('change', handleLearningGoalClick));
  };

  /**
   * Amplifier setup submit button handler
   */
  const handleSubmitButtonClick = async() => {
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
    handleNewCount(learningGoals.length);
    if (!submitEnabled) {
      return;
    }

    // Submit the settings and trigger loading landing page of amplifier widget
    try {
      await Controller.submitSetup({
        instanceid: instanceId,
        learninggoals: JSON.stringify(learningGoals)
      });
      location.reload();
    } catch (e) {
      throw new Error(e);
    }
  };

  /**
   * Learning goal check box selection handler
   * @param {*} e Changed event
   */
  const handleLearningGoalClick = (e) => {
    handleNewCount(checkedCount + (e.target.checked ? 1 : -1));
  };

  /**
   * Handle a new count of checked checkboxes
   * @param {Int} newCount New count
   */
  const handleNewCount = (newCount) => {
    checkedCount = newCount;
    submitEnabled = checkedCount > 0 && checkedCount <= 5;
    if (submitEnabled) {
      submitButton.removeAttribute('disabled');
    } else {
      submitButton.setAttribute('disabled', true);
    }
  };

  return {
    init: init
  };
});


