<?php
/**
 * Keycloak Single Logout (SLO) Callback
 * 
 * This page handles the return from Keycloak after Single Logout (SLO).
 * When Keycloak completes logout, it redirects the user back here.
 */

require_once(__DIR__ . '/../../config.php');

$PAGE->set_url('/auth/oauth2/slo_callback.php');
$PAGE->set_context(context_system::instance());

// Get return parameters from Keycloak
$state = optional_param('state', null, PARAM_RAW);

$logoutmessage = get_string('loggedoutsuccessfully');

// If user is already logged out (session expired), just show success message
if (!isloggedin()) {
    $PAGE->set_title(get_string('logout') . ' - ' . $SITE->fullname);
    $PAGE->set_heading($SITE->fullname);
    
    echo $OUTPUT->header();
    
    echo $OUTPUT->box_start('generalbox', 'notice');
    echo html_writer::tag('p', $logoutmessage);
    echo html_writer::tag('p', html_writer::link(new moodle_url('/login/index.php'), get_string('login')));
    echo $OUTPUT->box_end();
    
    echo $OUTPUT->footer();
    exit;
}

// If user is still logged in (shouldn't happen normally), log them out
if (isloggedin()) {
    $authsequence = get_enabled_auth_plugins();
    foreach($authsequence as $authname) {
        $authplugin = get_auth_plugin($authname);
        $authplugin->logoutpage_hook();
    }
    require_logout();
}

// Redirect to home page with success message
redirect($CFG->wwwroot, $logoutmessage, 1);
