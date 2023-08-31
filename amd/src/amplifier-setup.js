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
    var courseId, courseModuleId, instanceId, userId;

    /**
     * References to the currcent, previous and next fieldsets within the amplifier setup
     */
    var currentFieldset, nextFieldset, previousFieldset;

    /**
     * The users participant code
     */
    var participantCode = "TODO insert participant code here";

    /**
     * The user reflections
     */
    var reflections = [];

    /**
     * The user selected learning goals
     */
    var learningGoals = [];

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

        let $amplifierSetupRoot = $(`#amplifier-widget-${courseId}-${courseModuleId}-${instanceId}`);

        $amplifierSetupRoot.find(".amplifier-setup .next").click(handleNextButtonClick);

        $amplifierSetupRoot.find(".amplifier-setup .previous").click(handlePreviousButtonClick);

        $amplifierSetupRoot.find(".amplifier-setup .amplifier-submit-setup").click(handleSubmitButtonClick);

        $amplifierSetupRoot.find(".amplifier-setup .predefined-learning-goal-check").click(handleLearningGoalClick);

        $amplifierSetupRoot.find(".amplifier-setup .participantcode-input").change(handleParticipantCodeChange);

    };

    /**
     * Next button click handler
     */
    var handleNextButtonClick = function() {

        currentFieldset = $(this).parent().parent();
        nextFieldset = $(this).parent().parent().next();

        if ($(this).hasClass('participantcode')) {
            participantCode = $(this).parent().parent().find(".participantcode-input").val();
        }
        if ($(this).hasClass('reflective-question')) {
            let userResponse = $(this).parent().parent().find(".amplifier-user-response-input").val();
            let topicId = $(this).parent().parent().find(".amplifier-user-response-input").attr("data-topicid");
            let goalId = $(this).parent().parent().find(".amplifier-user-response-input").attr("data-goalid");
            reflections.push({userResponse: userResponse, topicid: topicId, goalid: goalId});
        }
        if ($(this).hasClass('learning-goals')) {
            $(".predefined-learning-goal-check").each((idx, element) => {
                if ($(element).is(':checked')) {
                    let topicId = $(element).attr("data-topicid");
                    let goalId = $(element).attr("data-goalid");
                    learningGoals.push({topicid: topicId, goalid: goalId});
                }
            });

        }

        // Show the next fieldset
        currentFieldset.addClass("d-none");
        nextFieldset.removeClass("d-none");
        nextFieldset.show();
        currentFieldset.hide();
    };

    /**
     * Previous button click handler
     */
    var handlePreviousButtonClick = function() {
        currentFieldset = $(this).parent().parent();
        previousFieldset = $(this).parent().parent().prev();

        // Show the previous fieldset
        currentFieldset.addClass("d-none");
        previousFieldset.removeClass("d-none");
        previousFieldset.show();
        currentFieldset.hide();
    };

    /**
     * Amplifier setup submit button handler
     */
    var handleSubmitButtonClick = function() {

        // Submit the settings and trigger loading landing page of amplifier widget
        Controller.submitSetup({
            courseid: courseId,
            userid: userId,
            coursemoduleid: courseModuleId,
            instanceid: instanceId,
            participantcode: participantCode,
            reflections: JSON.stringify(reflections),
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
     */
    var handleLearningGoalClick = function() {
        let checkedCount = 0;
        $(".predefined-learning-goal-check").each((idx, element) => {
            if ($(element).is(':checked')) {
                checkedCount++;
            }
        });
        if (checkedCount > 0 && checkedCount <= 5) {
            $(this).closest('fieldset').find('button.next.action-button').prop('disabled', false);
            $(this).closest('fieldset').find('button.next.action-button').css('opacity', '1');
        } else {
            $(this).closest('fieldset').find('button.next.action-button').prop('disabled', true);
            $(this).closest('fieldset').find('button.next.action-button').css('opacity', '.5');
        }
    };


    /**
     * Participant code next button handler
     */
    var handleParticipantCodeChange = function() {

        let participantCode = $(this).val();
        if (participantCode.length == 5) {
            $(this).closest('fieldset').find('button.next.action-button').prop('disabled', false);
            $(this).closest('fieldset').find('button.next.action-button').css('opacity', '1');
        } else {
            $(this).closest('fieldset').find('button.next.action-button').prop('disabled', true);
            $(this).closest('fieldset').find('button.next.action-button').css('opacity', '.5');
        }

    };

    return {
        init: init
    };
});


