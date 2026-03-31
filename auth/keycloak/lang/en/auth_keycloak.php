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
 * Language strings for Keycloak auth plugin.
 *
 * @package    auth_keycloak
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Keycloak (OAuth2)';
$string['auth_keycloak_enable_slo'] = '启用单点登出 (SLO)';
$string['auth_keycloak_enable_slo_desc'] = '启用后，用户从 Moodle 登出时也会同时登出 Keycloak。';
$string['auth_keycloak_slo_heading'] = '单点登出 (SLO) 设置';
$string['auth_keycloak_slo_desc'] = '配置 Keycloak 单点登出功能。当用户从 Moodle 登出时，将同时销毁 Keycloak 会话。';
