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
 * Keycloak auth plugin settings.
 *
 * @package    auth_keycloak
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('auth_keycloak_settings', 
        new lang_string('pluginname', 'auth_keycloak'),
        'moodle/site:config'
    );

    $ADMIN->add('authplugins', $settings);

    $settings->add(new admin_setting_heading('auth_keycloak_slo',
        new lang_string('auth_keycloak_slo_heading', 'auth_keycloak'),
        new lang_string('auth_keycloak_slo_desc', 'auth_keycloak')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'auth_keycloak/enable_slo',
        new lang_string('auth_keycloak_enable_slo', 'auth_keycloak'),
        new lang_string('auth_keycloak_enable_slo_desc', 'auth_keycloak'),
        1
    ));
}
