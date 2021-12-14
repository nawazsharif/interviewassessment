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
 * External Library
 *
 * @package    local
 * @subpackage rating_helper
 * @author     Brain Station 23
 * @copyright  2021 Brain Station 23 Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir . '/externallib.php');

/*
 *
 * @subpackage rating_helper
 */

class interviewassessment_services extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function get_all_criteria_parameters() {
        return new external_function_parameters(
            array(
                'cmid' =>
                    new external_value(PARAM_INT, 'The Id of the course module to check for.'
                    ),
                'userid' =>
                    new external_value(PARAM_INT, 'The Id of the course module to check for.'
                    )
            )
        );
    }

    /**
     * @param $cmid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_all_criteria($cmid,$userid) {

        global $DB, $OUTPUT, $CFG, $PAGE;
        $newArr = [];

        // Parameter validation.
        $params = self::validate_parameters(
            self::get_all_criteria_parameters(),
            array(
                'cmid' => $cmid,
                'userid' => $userid,
            )
        );

        require_once($CFG->dirroot . '/mod/interviewassessment/lib.php');
        $PAGE->set_context(context_system::instance());

        $criterias = $DB->get_records('interview_assessment');

        foreach ($criterias as $criterion) {
            $newArr[] = array(
                'id' => $criterion->id,
                'name' => $criterion->name,
            );
        }
        $output['userid'] = $userid;
        $output['result'] = $newArr;

        return $output;
    }

    /**
     * @return external_single_structure
     */
    public static function get_all_criteria_returns() {
        return new external_single_structure(
            array(
                'userid'=> new external_value(PARAM_INT, 'The user id'),
                'result' => new external_multiple_structure(self::criteria_list_structure())
            )
        );
    }
    /**
     * Returns a issued certificated structure
     *
     * @return external_single_structure External single structure
     */
    private static function criteria_list_structure() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Issue id'),
                'name' => new external_value(PARAM_TEXT, 'User id')
            )
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function interview_assessment_select_user_parameters() {
        return new external_function_parameters(
            array(
                'userid' =>
                    new external_value(PARAM_INT, 'The Id of the course module to check for.'
                    ),
                'cmid' =>
                    new external_value(PARAM_INT, 'The Id of the course module to check for.'
                    )
            )
        );
    }
    /**
     * @param $cmid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function interview_assessment_select_user($userid, $cmid) {

        global $DB, $OUTPUT, $CFG, $PAGE, $USER;
        $newArr = [];

        // Parameter validation.
        $params = self::validate_parameters(
            self::interview_assessment_select_user_parameters(),
            array(
                'userid' => $userid,
                'cmid' => $cmid
            )
        );
        require_once($CFG->dirroot . '/mod/interviewassessment/lib.php');
        $PAGE->set_context(context_system::instance());

        if ($DB->record_exists('user', array('id' => $params['userid']))) {
            $sql = 'select * from {interview_submissions} as isub left join {interview_assessment} as ia on ia.id = isub.assessment_id where isub.userid = ? and isub.course = ? and interviewer_id = '.$USER ->id;
            $data = $DB->get_records_sql($sql, array($params['userid'], $params['cmid']));
            $newArr = [];
            if($data) {
                foreach ($data as $val) {
                    $outpuArr = [
                        'id' => $val->id,
                        'assessment_id' => $val->assessment_id,
                        'userid' => $val->userid,
                        'grade' => $val->grade,
                        'course' => $val->course,
                        'name' => $val->name,
                    ];
                    array_push($newArr, $outpuArr);
                }

                $output['result'] = $newArr;
                $output['message'] = 'User Found';

            }
            else{
                $output['result'] = $newArr;
                $output['message'] = 'User Found';
            }

        }
        else{

            $output['result'] = [];
            $output['message'] = 'No User Found';
        }
        return $output;

    }
    /**
     * @return external_single_structure
     */
    public static function interview_assessment_select_user_returns() {
        return new external_single_structure(
            array(
                'result' =>new external_multiple_structure(self::criteria_result_list_structure()),
                'message'=> new external_value(PARAM_RAW, 'The result')
            ),'User preferences', VALUE_OPTIONAL
        );
    }

    /**
     * Returns a issued certificated structure
     *
     * @return external_single_structure External single structure
     */
    private static function criteria_result_list_structure() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Issue id'),
                'assessment_id' => new external_value(PARAM_TEXT, 'User id'),
                'userid' => new external_value(PARAM_TEXT, 'User id'),
                'grade' => new external_value(PARAM_FLOAT, 'User id'),
                'course' => new external_value(PARAM_TEXT, 'User id'),
                'name' => new external_value(PARAM_TEXT, 'User id'),
            ),'User preferences', VALUE_OPTIONAL
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function interview_assessment_results_parameters() {
        return new external_function_parameters(
            array(
                'userid' =>
                    new external_value(PARAM_INT, 'The Id of the course module to check for.'
                    ),
                'cmid' =>
                    new external_value(PARAM_INT, 'The Id of the course module to check for.'
                    )
            )
        );
    }
    public static function interview_assessment_results($userid, $cmid) {
        global $DB, $OUTPUT, $CFG, $PAGE, $USER;
//        $DB->set_debug(true);
        $message = '';
        $interviewer=[];
        $interview_result =[];
        $interviewer_sql = 'select DISTINCT ins.interviewer_id,u.firstname,u.email from {interview_submissions} as ins LEFT JOIN {user} as u ON u.id=ins.interviewer_id where ins.userid=? AND course=?';
        $interviewer = $DB->get_records_sql($interviewer_sql, array($userid,$cmid));
//        if($interviewer){
//            $interview_result_sql = 'select ins.interviewer_id from {interview_submissions} ins RIGHT JOIN {interview_assessment} ia on ins.assessment_id = ia.id  where ins.userid=? and ins.course=?';
//            $interview_result = $DB->get_records_sql($interview_result_sql, array($userid, $cmid));

        $interview_result = $DB->get_records_sql('select ins.*,ia.name from {interview_submissions} as ins 
JOIN {interview_assessment} as ia on 
ia.id = ins.assessment_id  where ins.userid=? and ins.course=?',[$userid, $cmid]);

            $message='success';
//        }
//        else{
//            $message = 'No Interviewer Found';
//        }
        $output['interviewers'] = $interviewer;
        $output['reviews'] = $interview_result;
        $output['result'] = $message;

        return $output;
    }

    public static function interview_assessment_results_returns() {

        return new external_single_structure(
            array(
                'interviewers' =>new external_multiple_structure(self::interviewer_list_structure()),
                'reviews'=> new external_multiple_structure(self::interview_result_list_structure()),
                'result' => new external_value(PARAM_RAW, 'The result')
            ),'User preferences', VALUE_OPTIONAL
        );
    }

    private static function interviewer_list_structure() {
        return new external_single_structure(
            array(
                'interviewer_id' => new external_value(PARAM_INT, 'Issue id'),
                'firstname' => new external_value(PARAM_TEXT, 'User id'),
                'email' => new external_value(PARAM_TEXT, 'User id'),
            ),'User preferences', VALUE_OPTIONAL
        );
    }

    private static function interview_result_list_structure() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Issue id'),
                'assessment_id' => new external_value(PARAM_TEXT, 'User id'),
                'userid' => new external_value(PARAM_TEXT, 'User id'),
                'grade' => new external_value(PARAM_FLOAT, 'User id'),
                'course' => new external_value(PARAM_TEXT, 'User id'),
                'interviewer_id' => new external_value(PARAM_INT, 'User id'),
                'name' => new external_value(PARAM_TEXT, 'User id'),

            ),'User preferences', VALUE_OPTIONAL
        );
    }



}