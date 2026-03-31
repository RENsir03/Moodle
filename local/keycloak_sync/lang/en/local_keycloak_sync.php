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
 * Language strings for Keycloak Sync plugin.
 *
 * @package    local_keycloak_sync
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Keycloak Role and Enrollment Sync';
$string['pluginname_desc'] = 'Synchronizes user roles and course enrollments based on Keycloak OIDC token claims.';

// Settings page strings
$string['role_mappings'] = 'Role Mappings';
$string['role_mappings_desc'] = 'Configure how Keycloak realm roles map to Moodle roles';
$string['admin_role'] = 'Admin Role Claim';
$string['admin_role_desc'] = 'Keycloak role name that grants Moodle Site Administrator access (default: moodle-admin)';
$string['teacher_role'] = 'Teacher Role Claim';
$string['teacher_role_desc'] = 'Keycloak role name that grants Moodle Teacher access (default: moodle-teacher)';
$string['student_role'] = 'Student Role Claim';
$string['student_role_desc'] = 'Keycloak role name that identifies students (default: moodle-student)';

$string['course_enrollment'] = 'Course Auto-Enrollment';
$string['course_enrollment_desc'] = 'Configure automatic course enrollment based on Keycloak claims';
$string['course_claim'] = 'Course Enrollment Claim';
$string['course_claim_desc'] = 'Name of the custom claim containing course shortnames for enrollment (default: course_enrollments)';
$string['teacher_category'] = 'Teacher Course Category';
$string['teacher_category_desc'] = 'Course category name for auto-enrolling teachers (default: 默认课程类别)';

$string['debug_settings'] = 'Debug Settings';
$string['debug_settings_desc'] = 'Configure debugging options';
$string['enable_debug'] = 'Enable Debug Logging';
$string['enable_debug_desc'] = 'Log detailed information about Keycloak data processing';

$string['sso_logout'] = 'Single Sign-Out (SSO)';
$string['sso_logout_desc'] = 'Configure Single Logout with Keycloak';
$string['enable_sso_logout'] = 'Enable SSO Logout';
$string['enable_sso_logout_desc'] = 'Redirect users to Keycloak logout when logging out from Moodle';
