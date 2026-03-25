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
 * Event observer class for Keycloak Sync plugin.
 *
 * @package    local_keycloak_sync
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_keycloak_sync;

defined('MOODLE_INTERNAL') || die();

/**
 * Class observer
 *
 * @package    local_keycloak_sync
 */
class observer {

    /**
     * Get plugin config.
     *
     * @return \stdClass
     */
    private static function get_config(): \stdClass {
        return get_config('local_keycloak_sync');
    }

    /**
     * Log debug message if debugging is enabled.
     *
     * @param string $message
     * @return void
     */
    private static function debug(string $message): void {
        $config = self::get_config();
        if (!empty($config->enable_debug) || debugging('', DEBUG_DEVELOPER)) {
            debugging('local_keycloak_sync: ' . $message, DEBUG_DEVELOPER);
        }
    }

    /**
     * Handle user logged in event.
     *
     * @param \core\event\user_loggedin $event
     * @return void
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        global $DB;

        $userid = $event->objectid;
        $user = $DB->get_record('user', ['id' => $userid]);

        if (!$user || $user->auth !== 'oauth2') {
            return;
        }

        // Get Keycloak token data from session.
        $keycloakdata = auth_hook::get_stored_keycloak_data();
        if (empty($keycloakdata)) {
            self::debug('No Keycloak data found in session for user ' . $user->username);
            return;
        }

        self::debug('Processing login for user ' . $user->username);

        // Sync user roles based on Keycloak realm roles.
        self::sync_user_roles($user, $keycloakdata);

        // Sync course enrollments based on custom claims.
        self::sync_course_enrollments($user, $keycloakdata);
    }

    /**
     * Handle user created event.
     *
     * @param \core\event\user_created $event
     * @return void
     */
    public static function user_created(\core\event\user_created $event): void {
        global $DB;

        $userid = $event->objectid;
        $user = $DB->get_record('user', ['id' => $userid]);

        if (!$user || $user->auth !== 'oauth2') {
            return;
        }

        // For new users, we try to get data from the auth_oauth2 session cache.
        $keycloakdata = auth_hook::get_stored_keycloak_data();
        if (empty($keycloakdata)) {
            self::debug('No Keycloak data found for new user ' . $user->username);
            return;
        }

        self::debug('Processing new user creation for ' . $user->username);

        // Set initial roles for new user.
        self::sync_user_roles($user, $keycloakdata);

        // Enroll user in courses.
        self::sync_course_enrollments($user, $keycloakdata);
    }

    /**
     * Handle user logged out event - implements Single Logout (SLO).
     *
     * @param \core\event\user_loggedout $event
     * @return void
     */
    public static function user_loggedout(\core\event\user_loggedout $event): void {
        global $SESSION, $DB;

        $userid = $event->objectid;
        
        // Check if SSO logout is enabled in plugin config.
        $config = self::get_config();
        if (empty($config->enable_sso_logout)) {
            self::debug('SSO logout is disabled');
            return;
        }

        self::debug('Processing logout for user ID: ' . $userid);

        // Get Keycloak issuer configuration.
        $issuer = $DB->get_record('oauth2_issuer', ['name' => 'Keycloak']);
        if (!$issuer) {
            self::debug('Keycloak issuer not found');
            return;
        }

        // Get logout endpoint.
        $logouturl = $DB->get_record('oauth2_endpoint', [
            'issuerid' => $issuer->id,
            'name' => 'logout_endpoint'
        ]);

        if (!$logouturl) {
            self::debug('Logout endpoint not configured');
            // Fallback to constructing logout URL from base URL.
            $baseurl = rtrim($issuer->baseurl, '/'); 
            $logouturl = $baseurl . '/protocol/openid-connect/logout';
        } else {
            $logouturl = $logouturl->url;
        }

        // Get stored refresh token or access token from session.
        $tokens = self::get_user_tokens($userid, $issuer->id);
        
        // Store logout URL in session for the logout page to redirect to.
        $SESSION->keycloak_logout_url = $logouturl;
        $SESSION->keycloak_logout_tokens = $tokens;
        
        self::debug('Stored Keycloak logout URL in session: ' . $logouturl);
    }

    /**
     * Get stored tokens for a user from OAuth2 linked logins.
     *
     * @param int $userid
     * @param int $issuerid
     * @return array
     */
    private static function get_user_tokens(int $userid, int $issuerid): array {
        global $DB;

        $linkedlogin = $DB->get_record('oauth2_linked_login', [
            'userid' => $userid,
            'issuerid' => $issuerid
        ]);

        if (!$linkedlogin) {
            return [];
        }

        return [
            'token' => $linkedlogin->token ?? '',
            'secret' => $linkedlogin->secret ?? '',
        ];
    }

    /**
     * Sync user roles based on Keycloak realm_access.roles claim.
     *
     * @param \stdClass $user
     * @param array $keycloakdata
     * @return void
     */
    private static function sync_user_roles(\stdClass $user, array $keycloakdata): void {
        global $DB;

        $config = self::get_config();

        // Extract roles from realm_access claim.
        $roles = [];
        if (!empty($keycloakdata['realm_access']['roles'])) {
            $roles = $keycloakdata['realm_access']['roles'];
        } elseif (!empty($keycloakdata['roles'])) {
            $roles = $keycloakdata['roles'];
        }

        if (empty($roles)) {
            self::debug('No roles found in Keycloak data');
            return;
        }

        self::debug('Found roles: ' . implode(', ', $roles));

        $systemcontext = \context_system::instance();

        // Check for admin role.
        $adminrole = $config->admin_role ?? 'moodle-admin';
        if (in_array($adminrole, $roles)) {
            self::assign_system_role($user->id, 'manager', $systemcontext);
            self::debug('Assigned manager role to user ' . $user->username);
        }

        // Check for teacher role.
        $teacherrole = $config->teacher_role ?? 'moodle-teacher';
        if (in_array($teacherrole, $roles)) {
            self::assign_system_role($user->id, 'editingteacher', $systemcontext);
            self::enroll_teacher_in_category_courses($user);
            self::debug('Assigned editingteacher role to user ' . $user->username);
        }

        // Check for student role.
        $studentrole = $config->student_role ?? 'moodle-student';
        if (in_array($studentrole, $roles)) {
            self::debug('User ' . $user->username . ' has student role');
        }
    }

    /**
     * Assign a system role to a user.
     *
     * @param int $userid
     * @param string $shortname
     * @param \context $context
     * @return void
     */
    private static function assign_system_role(int $userid, string $shortname, \context $context): void {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => $shortname]);
        if (!$role) {
            self::debug('Role ' . $shortname . ' not found');
            return;
        }

        // Check if already assigned.
        $existing = $DB->get_record('role_assignments', [
            'roleid' => $role->id,
            'userid' => $userid,
            'contextid' => $context->id,
        ]);

        if (!$existing) {
            role_assign($role->id, $userid, $context->id);
            self::debug('Assigned role ' . $shortname . ' to user ' . $userid);
        }
    }

    /**
     * Enroll teacher in all courses within a specific category.
     *
     * @param \stdClass $user
     * @return void
     */
    private static function enroll_teacher_in_category_courses(\stdClass $user): void {
        global $DB;

        $config = self::get_config();
        $categoryname = $config->teacher_category ?? '默认课程类别';

        // Find the category by name.
        $category = $DB->get_record('course_categories', ['name' => $categoryname]);
        if (!$category) {
            self::debug('Category ' . $categoryname . ' not found');
            return;
        }

        // Get all courses in this category.
        $courses = $DB->get_records('course', ['category' => $category->id]);

        foreach ($courses as $course) {
            self::enroll_user_in_course($user->id, $course->id, 'editingteacher');
        }

        self::debug('Enrolled teacher in ' . count($courses) . ' courses');
    }

    /**
     * Sync course enrollments based on course_enrollments claim.
     *
     * @param \stdClass $user
     * @param array $keycloakdata
     * @return void
     */
    private static function sync_course_enrollments(\stdClass $user, array $keycloakdata): void {
        $config = self::get_config();
        $claimname = $config->course_claim ?? 'course_enrollments';

        // Check for custom course_enrollments claim.
        if (empty($keycloakdata[$claimname])) {
            self::debug('No ' . $claimname . ' claim found');
            return;
        }

        $enrollments = $keycloakdata[$claimname];

        // Handle both string JSON and array formats.
        if (is_string($enrollments)) {
            $enrollments = json_decode($enrollments, true);
        }

        if (!is_array($enrollments) || empty($enrollments)) {
            self::debug('Invalid ' . $claimname . ' format');
            return;
        }

        self::debug('Processing course enrollments: ' . implode(', ', $enrollments));

        foreach ($enrollments as $courseshortname) {
            self::enroll_user_by_shortname($user->id, $courseshortname);
        }
    }

    /**
     * Enroll user in a course by shortname.
     *
     * @param int $userid
     * @param string $shortname
     * @param string $roleshortname
     * @return void
     */
    private static function enroll_user_by_shortname(int $userid, string $shortname, string $roleshortname = 'student'): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $shortname]);
        if (!$course) {
            self::debug('Course with shortname ' . $shortname . ' not found');
            return;
        }

        self::enroll_user_in_course($userid, $course->id, $roleshortname);
    }

    /**
     * Enroll user in a specific course with a specific role.
     *
     * @param int $userid
     * @param int $courseid
     * @param string $roleshortname
     * @return void
     */
    private static function enroll_user_in_course(int $userid, int $courseid, string $roleshortname): void {
        global $DB;

        // Get the role ID.
        $role = $DB->get_record('role', ['shortname' => $roleshortname]);
        if (!$role) {
            self::debug('Role ' . $roleshortname . ' not found');
            return;
        }

        // Get the course context.
        $context = \context_course::instance($courseid);

        // Check if already enrolled.
        $existing = $DB->get_record('role_assignments', [
            'roleid' => $role->id,
            'userid' => $userid,
            'contextid' => $context->id,
        ]);

        if ($existing) {
            self::debug('User ' . $userid . ' already enrolled in course ' . $courseid);
            return;
        }

        // Use manual enrollment plugin.
        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            self::debug('Manual enrollment plugin not available');
            return;
        }

        // Get the enrollment instance.
        $instance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual',
        ]);

        if (!$instance) {
            self::debug('Manual enrollment instance not found for course ' . $courseid);
            return;
        }

        // Enroll the user.
        $enrol->enroll_user($instance, $userid, $role->id);
        self::debug('Enrolled user ' . $userid . ' in course ' . $courseid . ' with role ' . $roleshortname);
    }
}
