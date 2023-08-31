# Training Amplifier

<table>
  <tr>
    <td colspan="2">Training Amplifier</td>
  </tr>
  <tr>
    <td>Type</td>
    <td>Activity Module</td>
  </tr>
  <tr>
    <td>Plugins directory entry</td>
    <td>-</td>
  </tr>
  <tr>
    <td>Discussion</td>
    <td>-</td>
  </tr>
  <tr>
    <td>Maintainer(s)</td>
    <td>Alfred Wertner</td>
  </tr>
</table>

Training Amplifier supports people in applying recently acquired knowledge into their daily routines.
First, people think about the most important aspects out of the recently heard for them personally. Then they make a decision and choose which of these aspects they want to achieve. The Training Amplifier limits the set of aspects to five to avoid getting overwhelmed by too much of them at once.
Second, people need to set a reminder for each of the aspects. People can choose to get notified via mail and/or the Moodle web interface.The Training Amplifier reminds them as long as they have not reflected at least once about an aspect. Reminding them should engage people to reflect about their efforts, if and how applied changes in behaviour belonging to an aspect impacts personal life.

## Prerequisites

Training Amplifier makes use of the Learning Goal Widget plugin. With the Learning Goal Widget the lecturer defines the set of aspects which can be chosen from as well as the reflective questions presented for an aspect.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/amplifier

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
