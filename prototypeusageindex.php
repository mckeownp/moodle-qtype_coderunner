<?php
// This file is part of CodeRunner - http://coderunner.org.nz
//
// CodeRunner is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunner is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stack.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Find all the uses of all the prototypes.
 *
 * This script scans all question categories to which the current user
 * has access and builds a table showing all available prototypes and
 * the questions using those prototypes.
 *
 * @package   qtype_coderunner
 * @copyright 2017 Richard Lobb, The University of Canterbury
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_coderunner;

use context;
use context_system;
use context_course;
use html_writer;
use moodle_url;
use qtype_coderunner_util;
use qtype_coderunner_bulk_tester;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(__DIR__ . '/classes/bulk_tester.php');

const BUTTONSTYLE = 'background-color: #FFFFD0; padding: 2px 2px 0px 2px;border: 4px solid white';

// Login and check permissions.
$context = context_system::instance();
require_login();

function display_course_header($coursecontextid, $coursename) {
    $litext = $coursecontextid . ' - ' . $coursename;
    echo html_writer::tag('h3', $litext);
}

function display_context_link($courseid, $name, $contextid, $numcoderunnerquestions) {

    $contexturl = new moodle_url(
        '/question/type/coderunner/prototypeusage.php',
        ['courseid' => $courseid, 'name' => $name, 'contextid' => $contextid]
    );
    $contextdescription = $contextid . ' - ' . $name . ' (' . $numcoderunnerquestions . ') ';

    $contextlink = html_writer::link(
        $contexturl,
        $contextdescription,
        ['style' => BUTTONSTYLE . ';cursor:pointer;text-decoration:none;']
    );

    if (strpos($name, ": Quiz: ") === false) {
        $class = 'questionbrowser coderunner context normal';
    } else {
        $class = 'questionbrowser coderunner context quiz';
    }

    echo html_writer::start_tag('li', ['class' => $class]);
    echo $contextlink;
    echo html_writer::end_tag('li');
}

// We are Moodle 4 or less if don't have mod_qbank.
$oldskool = !(qtype_coderunner_util::using_mod_qbank());

$PAGE->set_url('/question/type/coderunner/prototypeusageindex.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('prototypeusageindex', 'qtype_coderunner'));

// Find questions from contexts which the user can edit questions in.
$availablequestionsbycontext = bulk_tester::get_num_available_coderunner_questions_by_context();

// Display the list of contexts available to the user
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('prototypeusageindex', 'qtype_coderunner'));

if (count($availablequestionsbycontext) == 0) {
    echo html_writer::tag('p', 'You do not have permission to browse questions in any contexts.');
} else {
    if ($oldskool) {
        // Moodle 4 style.
        echo html_writer::tag(
            'p',
            '<strong>Instructions:</strong> Click the link to the course of interest'
        );
        $allcourses = bulk_tester::get_all_courses();
        echo html_writer::start_tag('ul');
        foreach ($allcourses as $course) {
            $courseid = $course->id;
            $contextid = $course->contextid;
            $coursecontext = context_course::instance($courseid);
            if (!has_capability('moodle/grade:viewall', $coursecontext) || !array_key_exists($contextid, $availablequestionsbycontext)) {
                continue;
            }
            $context = context::instance_by_id($contextid);
            $contextdata = $availablequestionsbycontext[$contextid];
            $numquestions = $contextdata['numquestions'];
            display_context_link($courseid, $course->name, $contextid, $numquestions);
        }
        echo \html_writer::end_tag('ul');
    } else {
        // Deal with funky question bank madness in Moodle 5.0.
        echo html_writer::tag('p', 'Moodle >= 5.0 detected. Listing by course then question bank.');
        echo html_writer::tag('p', '<strong>Instructions:</strong> Click the link to the context of interest');
        $allcourses = \qtype_coderunner\bulk_tester::get_all_courses();
        foreach ($allcourses as $courseid => $course) {
            $coursecontext = \context_course::instance($courseid);
            $allbanks = \qtype_coderunner\bulk_tester::get_all_qbanks_for_course($courseid);
            $headerdisplayed = false;
            if (count($allbanks) > 0) {
                echo \html_writer::start_tag('ul');
                foreach ($allbanks as $bank) {
                    $contextid = $bank->contextid;
                    if (array_key_exists($contextid, $availablequestionsbycontext)) {
                        if (!$headerdisplayed) {
                            display_course_header($coursecontext->id, $course->name);
                            $headerdisplayed = true;
                        }
                        $contextdata = $availablequestionsbycontext[$contextid];
                        $name = $contextdata['name'];
                        $numquestions = $contextdata['numquestions'];
                        display_context_link($courseid, $name, $contextid, $numquestions);
                    }
                }
                echo \html_writer::end_tag('ul');
            }
        }
    }
}


echo $OUTPUT->footer();
