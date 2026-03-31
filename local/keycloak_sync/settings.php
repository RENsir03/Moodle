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
 * Settings for Keycloak Sync plugin.
 *
 * @package    local_keycloak_sync
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_keycloak_sync', get_string('pluginname', 'local_keycloak_sync'));

    // Role mappings section.
    $settings->add(new admin_setting_heading(
        'local_keycloak_sync/role_mappings',
        get_string('role_mappings', 'local_keycloak_sync'),
        get_string('role_mappings_desc', 'local_keycloak_sync')
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/admin_role',
        get_string('admin_role', 'local_keycloak_sync'),
        get_string('admin_role_desc', 'local_keycloak_sync'),
        'moodle-admin',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/teacher_role',
        get_string('teacher_role', 'local_keycloak_sync'),
        get_string('teacher_role_desc', 'local_keycloak_sync'),
        'moodle-teacher',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/student_role',
        get_string('student_role', 'local_keycloak_sync'),
        get_string('student_role_desc', 'local_keycloak_sync'),
        'moodle-student',
        PARAM_TEXT
    ));

    // Course enrollment section.
    $settings->add(new admin_setting_heading(
        'local_keycloak_sync/course_enrollment',
        get_string('course_enrollment', 'local_keycloak_sync'),
        get_string('course_enrollment_desc', 'local_keycloak_sync')
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/course_claim',
        get_string('course_claim', 'local_keycloak_sync'),
        get_string('course_claim_desc', 'local_keycloak_sync'),
        'course_enrollments',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/teacher_category',
        get_string('teacher_category', 'local_keycloak_sync'),
        get_string('teacher_category_desc', 'local_keycloak_sync'),
        '默认课程类别',
        PARAM_TEXT
    ));

    // SSO Logout section.
    $settings->add(new admin_setting_heading(
        'local_keycloak_sync/sso_logout',
        get_string('sso_logout', 'local_keycloak_sync'),
        get_string('sso_logout_desc', 'local_keycloak_sync')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_keycloak_sync/enable_sso_logout',
        get_string('enable_sso_logout', 'local_keycloak_sync'),
        get_string('enable_sso_logout_desc', 'local_keycloak_sync'),
        0
    ));

    // Debug section.
    $settings->add(new admin_setting_heading(
        'local_keycloak_sync/debug_settings',
        get_string('debug_settings', 'local_keycloak_sync'),
        get_string('debug_settings_desc', 'local_keycloak_sync')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_keycloak_sync/enable_debug',
        get_string('enable_debug', 'local_keycloak_sync'),
        get_string('enable_debug_desc', 'local_keycloak_sync'),
        0
    ));

    $ADMIN->add('localplugins', $settings);
}
