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
     * Course, Course module, course module instance and user identifiers
     */
  let courseId, courseModuleId, instanceId, userId;

  /**
     * The root element
     */
  let rootElement;

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
     * @param {object} paramCourseId The course identifier
     * @param {object} paramCourseModuleId The course module identifier
     * @param {object} paramInstanceId The course module instance identifier
     * @param {object} paramUserId The user identifier
     */
  const init = (paramCourseId, paramCourseModuleId, paramInstanceId, paramUserId) => {
    courseId = paramCourseId;
    courseModuleId = paramCourseModuleId;
    instanceId = paramInstanceId;
    userId = paramUserId;

    rootElement = document.querySelector(`#amplifier-widget-${courseId}-${courseModuleId}-${instanceId}`);

    submitButton = rootElement.querySelector(".amplifier-setup .amplifier-submit-setup");
    submitButton.addEventListener('click', handleSubmitButtonClick);

    checkboxes = rootElement.querySelectorAll(".amplifier-setup .predefined-learning-goal-check");
    checkboxes.forEach((el) => el.addEventListener('change', handleLearningGoalClick));
  };

  /**
     * Amplifier setup submit button handler
     */
  var handleSubmitButtonClick = function() {
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
    Controller.submitSetup({
      courseid: courseId,
      userid: userId,
      coursemoduleid: courseModuleId,
      instanceid: instanceId,
      participantcode: "PARTICIPANT CODE",
      reflections: JSON.stringify([]),
      learninggoals: JSON.stringify(learningGoals)
    })
      .then(
        function() {
          // Reload document to show amplifier widget
          location.reload();
          return;
        }
      )
      .catch(function(error) {
        throw new Error(error);
      });
  };

  /**
     * Learning goal check box selection handler
     * @param {*} e Changed event
     */
  var handleLearningGoalClick = function(e) {
    //eslint-disable-next-line
    console.log(checkedCount + (e.target.checked ? 1 : -1));
    handleNewCount(checkedCount + (e.target.checked ? 1 : -1));
  };

  /**
     * Handle a new count of checked checkboxes
     * @param {Int} newCount New count
     */
  const handleNewCount = (newCount) => {
    //eslint-disable-next-line
    console.log(newCount);
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


