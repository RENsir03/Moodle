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
 * Library functions for Keycloak Sync plugin.
 *
 * @package    local_keycloak_sync
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook into the after_config to capture OAuth2 data.
 * Note: This function should not modify session data here to avoid conflicts.
 */
function local_keycloak_sync_after_config(): void {
    // Do not process here to avoid session mutations.
    // The actual processing is done in the observer classes.
}

/**
 * Hook to process logout - implements Single Logout (SLO) with Keycloak.
 *
 * @param \stdClass $user The user object
 * @return void
 */
function local_keycloak_sync_pre_user_logout($user): void {
    global $SESSION, $DB;

    // Check if SSO logout is enabled.
    $config = get_config('local_keycloak_sync');
    if (empty($config->enable_sso_logout)) {
        return;
    }

    // Only process OAuth2 users.
    if ($user->auth !== 'oauth2') {
        return;
    }

    // Get Keycloak issuer configuration.
    $issuer = $DB->get_record('oauth2_issuer', ['name' => 'Keycloak']);
    if (!$issuer) {
        return;
    }

    // Get logout endpoint from database.
    $logoutrecord = $DB->get_record('oauth2_endpoint', [
        'issuerid' => $issuer->id,
        'name' => 'logout_endpoint'
    ]);

    if ($logoutrecord) {
        $logouturl = $logoutrecord->url;
    } else {
        // Fallback: construct logout URL from base URL.
        $baseurl = rtrim($issuer->baseurl, '/'); 
        $logouturl = $baseurl . '/protocol/openid-connect/logout';
    }

    // Get the redirect URL after Keycloak logout.
    $redirecturl = new \moodle_url('/'); 

    // Build Keycloak logout URL with post_logout_redirect_uri.
    $params = [
        'post_logout_redirect_uri' => urlencode($redirecturl->out(false)),
    ];

    // Add id_token_hint if we have the user's token.
    $linkedlogin = $DB->get_record('oauth2_linked_login', [
        'userid' => $user->id,
        'issuerid' => $issuer->id
    ]);

    if ($linkedlogin && !empty($linkedlogin->token)) {
        $params['id_token_hint'] = $linkedlogin->token;
    }

    // Build final logout URL.
    $logouturl .= '?' . http_build_query($params);

    // Store logout URL in session - will be processed by logout page.
    $SESSION->keycloak_slo_url = $logouturl;
}
