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
 * Auth hook class for Keycloak Sync plugin.
 *
 * @package    local_keycloak_sync
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_keycloak_sync;

defined('MOODLE_INTERNAL') || die();

/**
 * Class auth_hook
 *
 * @package    local_keycloak_sync
 */
class auth_hook {

    /**
     * Process raw userinfo from OAuth2 provider and extract Keycloak-specific claims.
     *
     * @param array $rawuserinfo Raw userinfo from OAuth2 provider
     * @return array Processed data with Keycloak claims preserved
     */
    public static function process_keycloak_userinfo(array $rawuserinfo): array {
        global $SESSION;

        $keycloakdata = [
            'raw_claims' => $rawuserinfo,
        ];

        // Extract realm_access.roles claim.
        if (!empty($rawuserinfo['realm_access']['roles'])) {
            $keycloakdata['realm_access'] = [
                'roles' => $rawuserinfo['realm_access']['roles'],
            ];
            $keycloakdata['roles'] = $rawuserinfo['realm_access']['roles'];
        }

        // Extract resource_access claims.
        if (!empty($rawuserinfo['resource_access'])) {
            $keycloakdata['resource_access'] = $rawuserinfo['resource_access'];
        }

        // Extract custom course_enrollments claim.
        if (!empty($rawuserinfo['course_enrollments'])) {
            $enrollments = $rawuserinfo['course_enrollments'];
            if (is_string($enrollments)) {
                $decoded = json_decode($enrollments, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $keycloakdata['course_enrollments'] = $decoded;
                } else {
                    $keycloakdata['course_enrollments'] = [$enrollments];
                }
            } else {
                $keycloakdata['course_enrollments'] = $enrollments;
            }
        }

        // Extract groups claim.
        if (!empty($rawuserinfo['groups'])) {
            $keycloakdata['groups'] = $rawuserinfo['groups'];
        }

        // Store in session for later use by observers.
        $SESSION->keycloak_userdata = $keycloakdata;

        debugging('local_keycloak_sync: Stored Keycloak data in session', DEBUG_DEVELOPER);

        return $keycloakdata;
    }

    /**
     * Get stored Keycloak user data from session.
     *
     * @return array|null
     */
    public static function get_stored_keycloak_data(): ?array {
        global $SESSION;

        if (!empty($SESSION->keycloak_userdata)) {
            return $SESSION->keycloak_userdata;
        }

        return null;
    }

    /**
     * Clear stored Keycloak data from session.
     *
     * @return void
     */
    public static function clear_stored_keycloak_data(): void {
        global $SESSION;
        unset($SESSION->keycloak_userdata);
    }

    /**
     * Log Keycloak data for debugging purposes.
     *
     * @param array $data
     * @param string $context
     * @return void
     */
    public static function log_keycloak_data(array $data, string $context = ''): void {
        if (!debugging('', DEBUG_DEVELOPER)) {
            return;
        }

        $logmessage = 'local_keycloak_sync';
        if (!empty($context)) {
            $logmessage .= ' [' . $context . ']';
        }
        $logmessage .= ': ' . json_encode($data, JSON_PRETTY_PRINT);

        debugging($logmessage, DEBUG_DEVELOPER);
    }
}
