<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Request handle.
 *
 * @package   local_scormcourse
 * @copyright 2021 Pavel Babich
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/mod/scorm/locallib.php');

$sec = optional_param('sec', '', PARAM_RAW); //hash of the sections
$que = optional_param('que', '', PARAM_RAW); //hash of the questions

if (!$cm = get_coursemodule_from_id('scorm', 5)) {
    print_error('invalidcoursemodule');
}
if (!$scorm = $DB->get_record('scorm', array('id' => $cm->instance))) {
    print_error('invalidcoursemodule');
}

$attempt = scorm_get_last_attempt($scorm->id, $USER->id);

if (strlen($sec) == 32) { //hash length is always 32 characters

    $data = $DB->get_record_select(
        'scorm_scoes_track',
        'userid=? AND scormid=? AND scoid=? AND attempt=? ' .
            'AND element=\'cmi.suspend_data\'',
        array($USER->id, $scorm->id, 8, $attempt)
    );

    $suspenddata = $data->value; //get a field from the table with information about the user's past login to scorm

    if ($suspenddata) {
        $findsec = 'sections/';
        $findque = 'questions/';
        $replace = 'learning-objective';

        $offset = strpos($suspenddata, $findsec) + strlen($findsec);
        $suspenddata =  substr_replace($suspenddata, $sec, $offset, 32); //replace the hash of section

        if (strlen($que) == 32) {
            if ($offset = strpos($suspenddata, $replace)) {
                $suspenddata = substr_replace($suspenddata, $findque . $que, $offset, strlen($replace)); //replace the hash of question
            } else {
                $offset = strpos($suspenddata, $findque) + strlen($findque);
                $suspenddata =  substr_replace($suspenddata, $que, $offset, 32); //replace the hash of question
            }
        } elseif ($offset = strpos($suspenddata, $findque)) {
            $suspenddata = substr_replace($suspenddata, $replace, $offset, strlen($findque) + 32); //remove the hash of question from the string
        }

        try {
            $DB->set_field('scorm_scoes_track', 'value', $suspenddata, array('id' => $data->id));
            redirect('http://smartapp.moodle/mod/scorm/player.php?mode=normal&scoid=8&cm=5&currentorg=easygenerator');
        } catch (dml_exception $e) {
            echo 'Error';
        }
    } else {
        echo 'error';
    }
} elseif ($sec && $que) {
    print_error('invalidhash');
}

require_login();
$url = new moodle_url($CFG->wwwroot . '/local/scormcourse/index.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('pluginname', 'local_scormcourse'));
$PAGE->set_title($SITE->fullname . ': ' . get_string('pluginname', 'local_scormcourse'));
$PAGE->set_url($url);

echo $OUTPUT->header();

$jsonfile = file_get_contents('../../../moodledata/filedir/9d/08/9d08a59d34414f411ef0f99f7c4805d736e57ecd');
$jsonfile = json_decode($jsonfile, true);

$sections = hashtitle($jsonfile);
$questions = $sections[1];
$sections = $sections[0];

foreach ($sections as $skey => $svalue) {
    echo '<a href="http://smartapp.moodle/local/scormcourse/index.php?sec=' . $skey . '">' . $svalue . '</a><br>';
    foreach ($questions as $qkey => $qvalue) {
        if ($skey == $qkey) {
            foreach ($qvalue as $key => $element) {
                echo '**<a href="http://smartapp.moodle/local/scormcourse/index.php?sec=' . $qkey . '&que=' . $key . '">' . $element . '</a><br>';
            }
        }
    }
}

echo $OUTPUT->footer();
