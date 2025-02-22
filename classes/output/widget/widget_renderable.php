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

namespace mod_amplifier\output\widget;

use renderable;
use renderer_base;
use templatable;
use mod_amplifier\local\amplifier;

/**
 * Training Amplifier Widget Renderable
 *
 * @package   mod_amplifier
 * @copyright University of Technology Graz
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class widget_renderable implements renderable, templatable {
    /**
     * instance id
     *
     * @var int
     */
    private $instanceid;

    /**
     * ctor of widget_renderable
     *
     * @param [type] $instanceid
     */
    public function __construct($instanceid) {
        $this->instanceid = $instanceid;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param  \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) {
        $amplifier = new amplifier($this->instanceid);
        $context = ['instanceId' => $this->instanceid];

        return $amplifier->render($context);
    }
}
