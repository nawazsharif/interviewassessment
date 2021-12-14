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
 * Page module version information
 *
 * @package mod_page
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
global $PAGE, $DB, $CFG, $USER, $COURSE, $OUTPUT;


require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->dirroot . '/mod/page/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$p = optional_param('p', 0, PARAM_INT);  // Page instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);


if ($p) {
    if (!$page = $DB->get_record('interviewassessment', array('id' => $p))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('page', $page->id, $page->course, false, MUST_EXIST);

} else {

    if (!$cm = get_coursemodule_from_id('interviewassessment', $id)) {
        print_error('invalidcoursemodule');
    }
    $page = $DB->get_record('interviewassessment', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/interviewassessment:view', $context);

$PAGE->set_url('/mod/interviewassessment/view.php', array('id' => $cm->id));
$PAGE->requires->js_call_amd('mod_interviewassessment/selectuser', 'init');
$PAGE->requires->js_call_amd('mod_interviewassessment/userresult', 'init');

echo $OUTPUT->header();
if (!isset($options['printheading']) || !empty($options['printheading'])) {
    echo $OUTPUT->heading(format_string($page->name), 2);
}
$submissioncandidates = get_enrolled_users($context, 'mod/interviewassessment:submit');

// Seletct user section html
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-6');

echo html_writer::start_tag('form', array('method' => 'post', 'action' => ''));
echo html_writer::tag('label', get_string('selectuserforreview', 'mod_interviewassessment'),['style'=>'width:100%']);
echo html_writer::start_tag('select', array("id" => "select_user", "class" => "custom-select urlselect", "name" => "userid"));
echo "<option value=''>Select User</option>";
foreach ($submissioncandidates as $key => $record) {
    echo '<option value="' . $record->id . '">' . $record->firstname . ' ' . $record->lastname . ' (' . $record->email . ')</option>';
}
echo html_writer::end_tag('select');
echo html_writer::end_tag("form");

echo html_writer::end_div();
echo html_writer::start_div('col-md-6');
// reslut for student html
echo html_writer::start_tag('form', array('method' => 'post', 'action' => ''));
echo html_writer::tag('label', get_string('selectuserforresult', 'mod_interviewassessment'),['style'=>'width:100%']);
echo html_writer::start_tag('select', array("id" => "select_user_result", "class" => "custom-select urlselect", "name" => "userid"));
echo "<option value=''>Select User</option>";
foreach ($submissioncandidates as $key => $record) {
    echo '<option value="' . $record->id . '">' . $record->firstname . ' ' . $record->lastname . ' (' . $record->email . ')</option>';
}
echo html_writer::end_tag('select');
echo html_writer::end_tag("form");

echo html_writer::end_div();
echo html_writer::end_div();

// store interview assessment grade
if (isset($_POST['assessment_submit']) && $_POST['assessment_submit']) {
    if (count($_POST['rating']) > 0) {
        foreach ($_POST['rating'] as $key => $reting) {
            $DB->insert_record('interview_submissions', array('userid' => $_POST['userid'], 'assessment_id' => $key, 'grade' => $reting, 'course' => $course->id,'interviewer_id'=>$USER->id));
        }
    }
}

// Interview assessment Result Div
echo html_writer::start_div("d-none", ['id' => "user_rating"]);
echo html_writer::end_div();
echo html_writer::start_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'id' => 'courseid', 'value' => $course->id));

// Load Interview assessment Grade Submission form
echo html_writer::start_tag('form', array('method' => 'post', 'class' => 'd-none', 'action' => '', 'id' => 'assessment_form'));
echo html_writer::end_tag("input");
echo html_writer::start_div("row", ['id' => 'criteria_render']);
echo html_writer::end_div();
echo html_writer::start_div('text-center');
echo html_writer::tag('button', 'Assessment Submit', ['name' => 'assessment_submit', 'class' => 'btn btn-primary', 'value' => 'assessment_submit']);
echo html_writer::end_div();
echo html_writer::end_tag('form');
// End Submission form

echo html_writer::start_tag('div',['id'=>'all_result_render']);
echo html_writer::end_tag('div');

echo $OUTPUT->footer();

