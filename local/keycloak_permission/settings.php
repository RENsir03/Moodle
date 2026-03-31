<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_keycloak_permission', 
        new lang_string('settings', 'local_keycloak_permission'),
        'moodle/site:config'
    );

    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading('local_keycloak_permission_heading',
        new lang_string('settings', 'local_keycloak_permission'),
        new lang_string('settings_desc', 'local_keycloak_permission')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_keycloak_permission/enable_permission_check',
        new lang_string('enable_permission_check', 'local_keycloak_permission'),
        new lang_string('enable_permission_check_desc', 'local_keycloak_permission'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_keycloak_permission/allowed_roles',
        new lang_string('allowed_roles', 'local_keycloak_permission'),
        new lang_string('allowed_roles_desc', 'local_keycloak_permission'),
        'moodle-admin,moodle-teacher,moodle-student',
        PARAM_TEXT
    ));
}
