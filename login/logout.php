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
 * Logs the user out and sends them to the home page
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');

$PAGE->set_url('/login/logout.php');
$PAGE->set_context(context_system::instance());

$sesskey = optional_param('sesskey', '__notpresent__', PARAM_RAW);
$login   = optional_param('loginpage', 0, PARAM_BOOL);

if ($login) {
    $redirect = get_login_url();
} else {
    $redirect = $CFG->wwwroot.'/';
}

// Check if user is logged in
if (!isloggedin()) {
    redirect($redirect);
}

// Check sesskey
if (!confirm_sesskey($sesskey)) {
    $PAGE->set_title(get_string('logout'));
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('logoutconfirm'), 
        new moodle_url($PAGE->url, array('sesskey'=>sesskey())), 
        $CFG->wwwroot.'/'
    );
    echo $OUTPUT->footer();
    die;
}

// Handle Keycloak Single Logout (SLO)
function handle_keycloak_logout() {
    global $USER, $DB;
    
    // Only process if user has oauth2 auth type
    if (!in_array($USER->auth, ['oauth2', 'keycloak'])) {
        return false;
    }
    
    // Check if Keycloak issuer exists
    try {
        $issuer = $DB->get_record('oauth2_issuer', ['name' => 'Keycloak']);
        if (!$issuer) {
            return false;
        }
        
        // Check if user has linked login (may be null)
        $linkedlogin = $DB->get_record('auth_oauth2_linked_login', [
            'userid' => $USER->id,
            'issuerid' => $issuer->id
        ]);
        
        // Get Keycloak base URL
        $baseurl = rtrim($issuer->baseurl, '/');
        
        // Use Keycloak logout URL (without id_token_hint - Moodle doesn't store it)
        // Keycloak will try to logout the session, and if session is invalid,
        // it will redirect to the post_logout_redirect_uri
        $logouturl = $baseurl . '/protocol/openid-connect/logout';
        
        // Build logout URL with redirect to SLO callback
        $redirecturl = new moodle_url('/auth/oauth2/slo_callback.php');
        $params = [
            'post_logout_redirect_uri' => $redirecturl->out(false),
            'client_id' => $issuer->clientid,
        ];
        
        // Note: id_token_hint is not available as Moodle doesn't store it
        // Adding client_id as alternative for Keycloak
        
        $logouturl .= '?' . http_build_query($params);
        
        return $logouturl;
    } catch (Exception $e) {
        // Log error and skip Keycloak logout
        error_log('Keycloak SLO error: ' . $e->getMessage());
        return false;
    }
}

// Try Keycloak logout
$keycloak_logout_url = handle_keycloak_logout();
if ($keycloak_logout_url) {
    require_logout();
    redirect($keycloak_logout_url);
    die;
}

// Standard logout
$authsequence = get_enabled_auth_plugins();
foreach($authsequence as $authname) {
    $authplugin = get_auth_plugin($authname);
    $authplugin->logoutpage_hook();
}

require_logout();
redirect($redirect);
