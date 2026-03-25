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
        'Role Mappings',
        'Configure how Keycloak realm roles map to Moodle roles'
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/admin_role',
        'Admin Role Claim',
        'Keycloak role name that grants Moodle Site Administrator access',
        'moodle-admin',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/teacher_role',
        'Teacher Role Claim',
        'Keycloak role name that grants Moodle Teacher access',
        'moodle-teacher',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/student_role',
        'Student Role Claim',
        'Keycloak role name that identifies students',
        'moodle-student',
        PARAM_TEXT
    ));

    // Course enrollment section.
    $settings->add(new admin_setting_heading(
        'local_keycloak_sync/course_enrollment',
        'Course Auto-Enrollment',
        'Configure automatic course enrollment based on Keycloak claims'
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/course_claim',
        'Course Enrollment Claim',
        'Name of the custom claim containing course shortnames for enrollment',
        'course_enrollments',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_sync/teacher_category',
        'Teacher Course Category',
        'Course category name for auto-enrolling teachers',
        '默认课程类别',
        PARAM_TEXT
    ));

    // Debug section.
    $settings->add(new admin_setting_heading(
        'local_keycloak_sync/debug',
        'Debug Settings',
        'Configure debugging options'
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_keycloak_sync/enable_debug',
        'Enable Debug Logging',
        'Log detailed information about Keycloak data processing',
        0
    ));

    $ADMIN->add('localplugins', $settings);
}
