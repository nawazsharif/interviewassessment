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
 * Privacy Subsystem implementation for mod_interviewassessment.
 *
 * @package    mod_interviewassessment
 * @copyright  2018 Zig Tan <zig@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_interviewassessment\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\helper;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/interviewassessment/lib.php');

/**
 * Implementation of the privacy subsystem plugin provider for mod_interviewassessment.
 *
 * @copyright  2018 Zig Tan <zig@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'interviewassessment_submissions',
            [
                'userid' => 'privacy:metadata:interviewassessment_submissions:userid',
                'timecreated' => 'privacy:metadata:interviewassessment_submissions:timecreated',
                'timemodified' => 'privacy:metadata:interviewassessment_submissions:timemodified',
                'numfiles' => 'privacy:metadata:interviewassessment_submissions:numfiles',
                'data1' => 'privacy:metadata:interviewassessment_submissions:data1',
                'data2' => 'privacy:metadata:interviewassessment_submissions:data2',
                'grade' => 'privacy:metadata:interviewassessment_submissions:grade',
                'submissioncomment' => 'privacy:metadata:interviewassessment_submissions:submissioncomment',
                'teacher' => 'privacy:metadata:interviewassessment_submissions:teacher',
                'timemarked' => 'privacy:metadata:interviewassessment_submissions:timemarked',
                'mailed' => 'privacy:metadata:interviewassessment_submissions:mailed'
            ],
            'privacy:metadata:interviewassessment_submissions'
        );

        // Legacy mod_interviewassessment preferences from Moodle 2.X.
        $collection->add_user_preference('interviewassessment_filter', 'privacy:metadata:interviewassessmentfilter');
        $collection->add_user_preference('interviewassessment_mailinfo', 'privacy:metadata:interviewassessmentmailinfo');
        $collection->add_user_preference('interviewassessment_perpage', 'privacy:metadata:interviewassessmentperpage');
        $collection->add_user_preference('interviewassessment_quickgrade', 'privacy:metadata:interviewassessmentquickgrade');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT
                       ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {interviewassessment} a ON cm.instance = a.id
                  JOIN {interviewassessment_submissions} s ON s.interviewassessment = a.id
                 WHERE s.userid = :userid
                    OR s.teacher = :teacher";

        $params = [
            'contextmodule'  => CONTEXT_MODULE,
            'modulename'    => 'interviewassessment',
            'userid'        => $userid,
            'teacher'       => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $params = [
            'modulename' => 'interviewassessment',
            'contextlevel' => CONTEXT_MODULE,
            'contextid' => $context->id
        ];
        $sql = "SELECT s.userid
                  FROM {interviewassessment_submissions} s
                  JOIN {interviewassessment} a ON s.interviewassessment = a.id
                  JOIN {modules} m ON m.name = :modulename
                  JOIN {course_modules} cm ON a.id = cm.instance AND cm.module = m.id
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE ctx.id = :contextid
        ";
        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT s.teacher
                  FROM {interviewassessment_submissions} s
                  JOIN {interviewassessment} a ON s.interviewassessment = a.id
                  JOIN {modules} m ON m.name = :modulename
                  JOIN {course_modules} cm ON a.id = cm.instance AND cm.module = m.id
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE ctx.id = :contextid
        ";
        $userlist->add_from_sql('teacher', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist.
     * User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            // Cannot make use of helper::export_context_files(), need to manually export interviewassessment details.
            $interviewassessmentdata = self::get_interviewassessment_by_context($context);

            // Get interviewassessment details object for output.
            $interviewassessment = self::get_interviewassessment_output($interviewassessmentdata);
            writer::with_context($context)->export_data([], $interviewassessment);

            // Check if the user has marked any interviewassessment's submissions to determine interviewassessment submissions to export.
            $teacher = (self::has_marked_interviewassessment_submissions($interviewassessmentdata->id, $user->id) == true) ? true : false;

            // Get the interviewassessment submissions submitted by & marked by the user for an interviewassessment.
            $submissionsdata = self::get_interviewassessment_submissions_by_interviewassessment($interviewassessmentdata->id, $user->id, $teacher);

            foreach ($submissionsdata as $submissiondata) {
                // Default subcontext path to export interviewassessment submissions submitted by the user.
                $subcontexts = [
                    get_string('privacy:submissionpath', 'mod_interviewassessment')
                ];

                if ($teacher == true) {
                    if ($submissiondata->teacher == $user->id) {
                        // Export interviewassessment submissions that have been marked by the user.
                        $subcontexts = [
                            get_string('privacy:markedsubmissionspath', 'mod_interviewassessment'),
                            transform::user($submissiondata->userid)
                        ];
                    }
                }

                // Get interviewassessment submission details object for output.
                $submission = self::get_interviewassessment_submission_output($submissiondata);
                $itemid = $submissiondata->id;

                writer::with_context($context)
                    ->export_data($subcontexts, $submission)
                    ->export_area_files($subcontexts, 'mod_interviewassessment', 'submission', $itemid);
            }
        }
    }

    /**
     * Stores the user preferences related to mod_assign.
     *
     * @param  int $userid The user ID that we want the preferences for.
     */
    public static function export_user_preferences(int $userid) {
        $context = \context_system::instance();
        $interviewassessmentpreferences = [
            'interviewassessment_filter' => [
                'string' => get_string('privacy:metadata:interviewassessmentfilter', 'mod_interviewassessment'),
                'bool' => false
            ],
            'interviewassessment_mailinfo' => [
                'string' => get_string('privacy:metadata:interviewassessmentmailinfo', 'mod_interviewassessment'),
                'bool' => false
            ],
            'interviewassessment_perpage' => [
                'string' => get_string('privacy:metadata:interviewassessmentperpage', 'mod_interviewassessment'),
                'bool' => false
            ],
            'interviewassessment_quickgrade' => [
                'string' => get_string('privacy:metadata:interviewassessmentquickgrade', 'mod_interviewassessment'),
                'bool' => false
            ],
        ];
        foreach ($interviewassessmentpreferences as $key => $preference) {
            $value = get_user_preferences($key, null, $userid);
            if ($preference['bool']) {
                $value = transform::yesno($value);
            }
            if (isset($value)) {
                writer::with_context($context)
                    ->export_user_preference('mod_interviewassessment', $key, $value, $preference['string']);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_MODULE) {
            // Delete all interviewassessment submissions for the interviewassessment associated with the context module.
            $interviewassessment = self::get_interviewassessment_by_context($context);
            if ($interviewassessment != null) {
                $DB->delete_records('interviewassessment_submissions', ['interviewassessment' => $interviewassessment->id]);

                // Delete all file uploads associated with the interviewassessment submission for the specified context.
                $fs = get_file_storage();
                $fs->delete_area_files($context->id, 'mod_interviewassessment', 'submission');
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        // Only retrieve interviewassessment submissions submitted by the user for deletion.
        $interviewassessmentsubmissionids = array_keys(self::get_interviewassessment_submissions_by_contextlist($contextlist, $userid));
        $DB->delete_records_list('interviewassessment_submissions', 'id', $interviewassessmentsubmissionids);

        // Delete all file uploads associated with the interviewassessment submission for the user's specified list of contexts.
        $fs = get_file_storage();
        foreach ($contextlist->get_contextids() as $contextid) {
            foreach ($interviewassessmentsubmissionids as $submissionid) {
                $fs->delete_area_files($contextid, 'mod_interviewassessment', 'submission', $submissionid);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        // If the context isn't for a module then return early.
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        // Fetch the interviewassessment.
        $interviewassessment = self::get_interviewassessment_by_context($context);
        $userids = $userlist->get_userids();

        list($inorequalsql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['interviewassessmentid'] = $interviewassessment->id;

        // Get submission ids.
        $sql = "
            SELECT s.id
            FROM {interviewassessment_submissions} s
            JOIN {interviewassessment} a ON s.interviewassessment = a.id
            WHERE a.id = :interviewassessmentid
            AND s.userid $inorequalsql
        ";

        $submissionids = $DB->get_records_sql($sql, $params);
        list($submissionidsql, $submissionparams) = $DB->get_in_or_equal(array_keys($submissionids), SQL_PARAMS_NAMED);
        $fs = get_file_storage();
        $fs->delete_area_files_select($context->id, 'mod_interviewassessment', 'submission', $submissionidsql, $submissionparams);
        // Delete related tables.
        $DB->delete_records_list('interviewassessment_submissions', 'id', array_keys($submissionids));
    }

    // Start of helper functions.

    /**
     * Helper function to check if a user has marked interviewassessment submissions for a given interviewassessment.
     *
     * @param int $interviewassessmentid The interviewassessment ID to check if user has marked associated submissions.
     * @param int $userid       The user ID to check if user has marked associated submissions.
     * @return bool             If user has marked associated submissions returns true, otherwise false.
     * @throws \dml_exception
     */
    protected static function has_marked_interviewassessment_submissions($interviewassessmentid, $userid) {
        global $DB;

        $params = [
            'interviewassessment' => $interviewassessmentid,
            'teacher'    => $userid
        ];

        $sql = "SELECT count(s.id) as nomarked
                  FROM {interviewassessment_submissions} s
                 WHERE s.interviewassessment = :interviewassessment
                   AND s.teacher = :teacher";

        $results = $DB->get_record_sql($sql, $params);

        return ($results->nomarked > 0) ? true : false;
    }

    /**
     * Helper function to return interviewassessment for a context module.
     *
     * @param object $context   The context module object to return the interviewassessment record by.
     * @return mixed            The interviewassessment details or null record associated with the context module.
     * @throws \dml_exception
     */
    protected static function get_interviewassessment_by_context($context) {
        global $DB;

        $params = [
            'modulename' => 'interviewassessment',
            'contextmodule' => CONTEXT_MODULE,
            'contextid' => $context->id
        ];

        $sql = "SELECT a.id,
                       a.name,
                       a.intro,
                       a.interviewassessmenttype,
                       a.grade,
                       a.timedue,
                       a.timeavailable,
                       a.timemodified
                  FROM {interviewassessment} a
                  JOIN {course_modules} cm ON a.id = cm.instance
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                 WHERE ctx.id = :contextid";

        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Helper function to return interviewassessment submissions submitted by / marked by a user and their contextlist.
     *
     * @param object $contextlist   Object with the contexts related to a userid to retrieve interviewassessment submissions by.
     * @param int $userid           The user ID to find interviewassessment submissions that were submitted by.
     * @param bool $teacher         The teacher status to determine if marked interviewassessment submissions should be returned.
     * @return array                Array of interviewassessment submission details.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected static function get_interviewassessment_submissions_by_contextlist($contextlist, $userid, $teacher = false) {
        global $DB;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'modulename' => 'interviewassessment',
            'userid' => $userid
        ];

        $sql = "SELECT s.id as id,
                       s.interviewassessment as interviewassessment,
                       s.numfiles as numfiles,
                       s.data1 as data1,
                       s.data2 as data2,
                       s.grade as grade,
                       s.submissioncomment as submissioncomment,
                       s.teacher as teacher,
                       s.timemarked as timemarked,
                       s.timecreated as timecreated,
                       s.timemodified as timemodified
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {interviewassessment} a ON cm.instance = a.id
                  JOIN {interviewassessment_submissions} s ON s.interviewassessment = a.id
                 WHERE (s.userid = :userid";

        if ($teacher == true) {
            $sql .= " OR s.teacher = :teacher";
            $params['teacher'] = $userid;
        }

        $sql .= ")";

        $sql .= " AND ctx.id {$contextsql}";
        $params += $contextparams;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Helper function to retrieve interviewassessment submissions submitted by / marked by a user for a specific interviewassessment.
     *
     * @param int $interviewassessmentid     The interviewassessment ID to retrieve interviewassessment submissions by.
     * @param int $userid           The user ID to retrieve interviewassessment submissions submitted / marked by.
     * @param bool $teacher         The teacher status to determine if marked interviewassessment submissions should be returned.
     * @return array                Array of interviewassessment submissions details.
     * @throws \dml_exception
     */
    protected static function get_interviewassessment_submissions_by_interviewassessment($interviewassessmentid, $userid, $teacher = false) {
        global $DB;

        $params = [
            'interviewassessment' => $interviewassessmentid,
            'userid' => $userid
        ];

        $sql = "SELECT s.id as id,
                       s.interviewassessment as interviewassessment,
                       s.numfiles as numfiles,
                       s.data1 as data1,
                       s.data2 as data2,
                       s.grade as grade,
                       s.submissioncomment as submissioncomment,
                       s.teacher as teacher,
                       s.timemarked as timemarked,
                       s.timecreated as timecreated,
                       s.timemodified as timemodified,
                       s.userid as userid
                  FROM {interviewassessment_submissions} s
                 WHERE s.interviewassessment = :interviewassessment
                   AND (s.userid = :userid";

        if ($teacher == true) {
            $sql .= " OR s.teacher = :teacher";
            $params['teacher'] = $userid;
        }

        $sql .= ")";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Helper function generate interviewassessment output object for exporting.
     *
     * @param object $interviewassessmentdata    Object containing interviewassessment data.
     * @return object                   Formatted interviewassessment output object for exporting.
     */
    protected static function get_interviewassessment_output($interviewassessmentdata) {
        $interviewassessment = (object) [
            'name' => $interviewassessmentdata->name,
            'intro' => $interviewassessmentdata->intro,
            'interviewassessmenttype' => $interviewassessmentdata->interviewassessmenttype,
            'grade' => $interviewassessmentdata->grade,
            'timemodified' => transform::datetime($interviewassessmentdata->timemodified)
        ];

        if ($interviewassessmentdata->timeavailable != 0) {
            $interviewassessment->timeavailable = transform::datetime($interviewassessmentdata->timeavailable);
        }

        if ($interviewassessmentdata->timedue != 0) {
            $interviewassessment->timedue = transform::datetime($interviewassessmentdata->timedue);
        }

        return $interviewassessment;
    }

    /**
     * Helper function generate interviewassessment submission output object for exporting.
     *
     * @param object $submissiondata    Object containing interviewassessment submission data.
     * @return object                   Formatted interviewassessment submission output for exporting.
     */
    protected static function get_interviewassessment_submission_output($submissiondata) {
        $submission = (object) [
            'interviewassessment' => $submissiondata->interviewassessment,
            'numfiles' => $submissiondata->numfiles,
            'data1' => $submissiondata->data1,
            'data2' => $submissiondata->data2,
            'grade' => $submissiondata->grade,
            'submissioncomment' => $submissiondata->submissioncomment,
            'teacher' => transform::user($submissiondata->teacher)
        ];

        if ($submissiondata->timecreated != 0) {
            $submission->timecreated = transform::datetime($submissiondata->timecreated);
        }

        if ($submissiondata->timemarked != 0) {
            $submission->timemarked = transform::datetime($submissiondata->timemarked);
        }

        if ($submissiondata->timemodified != 0) {
            $submission->timemodified = transform::datetime($submissiondata->timemodified);
        }

        return $submission;
    }
}
