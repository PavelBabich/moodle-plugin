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

defined('MOODLE_INTERNAL') || die();

/**
 * Add link to index.php into navigation drawer.
 *
 * @param navigation_node $frontpage Node representing the front page in the navigation tree.
 */
function local_scormcourse_extend_navigation(global_navigation $root)
{
    if (!isloggedin()) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_scormcourse'),
        new moodle_url('/local/scormcourse/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        null,
        new pix_icon('t/message', '')
    );
    $node->showinflatnavigation = true;

    $root->add_node($node);
}

/**
 * Parse hash and title of the questions from json file
 *
 * @param mixed $file Puth to the json file.
 * @return array an array of sections data
 */
function hashtitle($file)
{
    $sectionarr = array();
    $questionarr = array();
    foreach ($file as $array => $value) {
        if ($array == 'sections') {
            $sectionarr = array_column($value, 'title', 'id');
            foreach ($value as $section) {
                foreach ($section as $element => $value) {
                    if ($element == 'questions') {
                        $questionarr[] = array_column($value, 'title', 'id');
                    }
                }
            }
        }
    }
    return array($sectionarr, array_combine(array_keys($sectionarr), $questionarr));
}
