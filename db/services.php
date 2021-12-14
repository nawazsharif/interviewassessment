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
 * Definition of interviewassessment event handlers
 *
 * @package mod_interviewassessment
 * @category event
 * @copyright 1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'get_all_criteria' => array(
        'classname' => 'interviewassessment_services',
        'methodname' => 'get_all_criteria',
        'classpath' => 'mod/interviewassessment/externallib.php',
        'description' => 'Get all ratings and comments for one course module.',
        'loginrequired' => true,
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/interviewassessment:view',
    ),
    'interview_assessment_select_user' => array(
        'classname' => 'interviewassessment_services',
        'methodname' => 'interview_assessment_select_user',
        'classpath' => 'mod/interviewassessment/externallib.php',
        'description' => 'Get all ratings for one course module.',
        'loginrequired' => true,
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/interviewassessment:view',
    ),
    'interview_assessment_results' => array(
        'classname' => 'interviewassessment_services',
        'methodname' => 'interview_assessment_results',
        'classpath' => 'mod/interviewassessment/externallib.php',
        'description' => 'Get all ratings for one course module.',
        'loginrequired' => true,
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/interviewassessment:view',
    ),

);