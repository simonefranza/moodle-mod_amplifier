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
 * Strings for component 'amplifier', language 'de'
 *
 * @package   mod_amplifier
 * @copyright 2021 Know Center GmbH
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['amplifier'] = 'Benutzerregistrierung';

$string['amplifier:addinstance'] = 'Training Amplifier Widget hinzufügen';
$string['amplifier:setupgoals'] = 'Training Amplifier Widget einstellen und verwenden';
$string['amplifier:view'] = 'Training Amplifier Widget anzeigen';
$string['amplifiertext'] = 'Training Amplifier Widget Text';

$string['exception:change_lgw'] = 'Das Lernziele Widget kann nicht geändert werden. Bitte löschen Sie die Training Amplifier Instanz und erstellen Sie eine neue.';
$string['exception:instance_not_found'] = 'Die zu aktualisierende Instanz wurde nicht gefunden.';
$string['exception:lgw_missing'] = 'Das Lernziele Widget wurde nicht gefunden.';
$string['exception:requiredactivitymissing'] = 'Ein Lernziele Widget muss im Kurs vorhanden sein, bevor diese Aktivität hinzugefügt werden kann.';
$string['exception:requiredactivitypluginmissing'] = 'Das Lernziele Widget ist nicht installiert.';
$string['exception:setup_done'] = 'Sie haben den Training Amplifier Setup schon erledigt.';

$string['guestaccess'] = 'Dafür müssen sie angemeldet sein';

$string['message:reminder:html'] = '
<h3>Training Amplifier - Kurs {$a->coursetitle}</h3>
<p>
Dies ist eine Erinnerung einmal über das Lernziel <b>{$a->goalname}</b> zu reflektieren.
</p>
<p>
Gehen Sie dazu auf der <a href="{$a->url}">Kursübersichtseite</a> zum Abschnitt mit dem Training Amplifier Widget.
</p>
<p>
Um die Reflexion zu beginnen, klicken Sie beim Lernziel <b>{$a->topicname} - {$a->goalname}</b> auf den "Reflektieren" Button.
</p>
<p>
Viel Erfolg!
</p>';
$string['message:reminder:subject'] = 'Training Amplifier - Lernziel Reflexion';
$string['message:reminder:text'] = '
Training Amplifier - Kurs {$a->coursetitle}

Dies ist eine Erinnerung einmal über das Lernziel {$a->goalname} zu reflektieren.

Auf der Kursübersichtseite sehen Sie das Training Amplifier Widget mit Ihren Lernzielen.
Um die Reflexion zu beginnen, klicken Sie beim Lernziel {$a->topicname} - {$a->goalname} einfach auf den Reflektieren Button.
Mit diesem Link gelangen Sie zur Kursübersichtsseite:
{$a->url}

Viel Erfolg!';

$string['messageprovider:reflection_reminder'] = 'Erinnerung über ein Lernziel zu reflektieren';

$string['modform:selectlearninggoalwidget'] = 'Lernziele Widget';

$string['modulename'] = 'Training Amplifier Widget';
$string['modulename_help'] = '';
$string['modulename_link'] = 'mod/amplifier/view';
$string['modulenameplural'] = 'Training Amplifier Widgets';
$string['noaccess'] = 'Dafür müssen sie angemeldet sein';
$string['pluginadministration'] = 'Training Amplifier Widget Administration';
$string['pluginname'] = 'Training Amplifier Widget';

$string['privacy:metadata'] = '';

$string['privacy:metadata:amplifier_goals'] = 'Informationen über die ausgewählten Lernziele eines Nutzers.';
$string['privacy:metadata:amplifier_goals:amplifierid'] = 'Die ID einer Instanz des Training Amplifier.';
$string['privacy:metadata:amplifier_goals:lgwgoalid'] = 'Die ID des Ziels, das der Nutzer für Erinnerungen und Reflexionen ausgewählt hat.';
$string['privacy:metadata:amplifier_goals:userid'] = 'Die ID des Nutzers.';

$string['privacy:metadata:amplifier_reflections'] = 'Information über eine einzelne Reflexion eines Nutzers zu einem spezifischen Lernziel.';
$string['privacy:metadata:amplifier_reflections:amplifiergoalid'] = 'Die ID eines Eintrags in amplifier_goals.';
$string['privacy:metadata:amplifier_reflections:response'] = 'Die textuelle Darstellung der Reflexion des Nutzers.';
$string['privacy:metadata:amplifier_reflections:timecreated'] = 'Der Zeitstempel der Erstellung der Reflexion.';

$string['privacy:metadata:amplifier_reminders'] = 'Informationen über eine Erinnerungseinstellung eines Nutzers für ein spezifisches Lernziel.';
$string['privacy:metadata:amplifier_reminders:amplifiergoalid'] = 'Die ID eines Eintrags in amplifier_goals.';
$string['privacy:metadata:amplifier_reminders:enddate'] = 'Das Datum, an dem die Erinnerungen enden sollen.';
$string['privacy:metadata:amplifier_reminders:lastnotificationdate'] = 'Das Datum, an dem die letzte Erinnerung gesendet wurde.';
$string['privacy:metadata:amplifier_reminders:reminderhour'] = 'Die Stunde, zu der die Erinnerung gesendet werden soll.';
$string['privacy:metadata:amplifier_reminders:reminderminute'] = 'Die Minute, zu der die Erinnerung gesendet werden soll.';
$string['privacy:metadata:amplifier_reminders:startdate'] = 'Das Datum, an dem die Erinnerungen beginnen sollen.';
$string['privacy:metadata:amplifier_reminders:timezone'] = 'Die Zeitzone, in der die Erinnerung erstellt wurde.';

$string['search:activity'] = 'amplifier';

$string['task:reminder'] = 'Lookup user set reminders and send reflection notification message';

$string['template:general:save'] = 'Speichern';
$string['template:general:submit'] = 'Übernehmen';

$string['template:reflection:headline'] = "Reflexion";
$string['template:reflection:placeholder'] = 'Meine Gedanken...';
$string["template:reflection:text_1"] = "Bitte reflektieren Sie über das folgende Lernziel.";

$string["template:reminder:date:end"] = "Enddatum";
$string["template:reminder:date:start"] = "Startdatum";
$string["template:reminder:frequency:daily"] = "Täglich";
$string["template:reminder:frequency:monthly"] = "Monatlich";
$string["template:reminder:frequency:weekly"] = "Wöchentlich";
$string["template:reminder:headline"] = "Erinnerungseinstellungen";
$string["template:reminder:label:time"] = "Erinnnerungszeit";
$string["template:reminder:month:01"] = "Jänner";
$string["template:reminder:month:02"] = "Februar";
$string["template:reminder:month:03"] = "März";
$string["template:reminder:month:04"] = "April";
$string["template:reminder:month:05"] = "Mai";
$string["template:reminder:month:06"] = "Juni";
$string["template:reminder:month:07"] = "Juli";
$string["template:reminder:month:08"] = "August";
$string["template:reminder:month:09"] = "September";
$string["template:reminder:month:10"] = "Oktober";
$string["template:reminder:month:11"] = "November";
$string["template:reminder:month:12"] = "Dezember";

$string['template:setup:headline'] = "Willkommen beim Training Amplifier";
$string['template:setup:lgw_missing'] = "Die gewählte Lernziele Widget Instanz wurde nicht gefunden oder wurde gelöscht. Bitte löschen Sie diese Instanz des Training Amplifiers.";
$string['template:setup:teacher'] = 'Bitte ändern Sie Ihre Rolle zu Teilnehmer/in, um den Training Amplifier auszuprobieren.';
$string['template:setup:text_1'] = "Training Amplifier soll Sie unterstützen, neuerworbenes theoretisches Wissen in der Praxis anzuwenden.";
$string['template:setup:text_2'] = "Bitte wählen Sie bis zu 5 Ziele aus, die Sie in den kommenden Tagen oder Wochen verfolgen möchten. Für jedes Ihrer ausgewählten Ziele können Sie eine Erinnerung zur Reflexion setzen, die Sie daran erinnert, ob Sie das Ziel bereits in die Praxis umgesetzt haben.";
