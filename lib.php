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
 * interviewassessment_base is the base class for interviewassessment types
 *
 * This class provides all the functionality for an interviewassessment
 *
 * @package   mod_interviewassessment
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds an interviewassessment instance
 *
 * Only used by generators so we can create old interviewassessments to test the upgrade.
 *
 * @param stdClass $interviewassessment
 * @param mod_interviewassessment_mod_form $mform
 * @return int intance id
 */
function interviewassessment_add_instance($interviewassessment, $mform = null) {
    global $DB;

    $interviewassessment->timemodified = time();
    $interviewassessment->courseid = $interviewassessment->course;
    $returnid = $DB->insert_record("interviewassessment", $interviewassessment);
    $interviewassessment->id = $returnid;
    return $returnid;
}

/**
 * Deletes an interviewassessment instance
 *
 * @param $id
 */
function interviewassessment_delete_instance($id){
    global $CFG, $DB;

    if (! $interviewassessment = $DB->get_record('interviewassessment', array('id'=>$id))) {
        return false;
    }

    $result = true;
    // Now get rid of all files
    $fs = get_file_storage();
    if ($cm = get_coursemodule_from_instance('interviewassessment', $interviewassessment->id)) {
        $context = context_module::instance($cm->id);
        $fs->delete_area_files($context->id);
    }

    if (! $DB->delete_records('interviewassessment_submissions', array('interviewassessment'=>$interviewassessment->id))) {
        $result = false;
    }

    if (! $DB->delete_records('event', array('modulename'=>'interviewassessment', 'instance'=>$interviewassessment->id))) {
        $result = false;
    }

    grade_update('mod/interviewassessment', $interviewassessment->course, 'mod', 'interviewassessment', $interviewassessment->id, 0, NULL, array('deleted'=>1));

    // We must delete the module record after we delete the grade item.
    if (! $DB->delete_records('interviewassessment', array('id'=>$interviewassessment->id))) {
        $result = false;
    }

    return $result;
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function interviewassessment_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}
