<?php

require_once(__DIR__ . '/../../config.php');

$issuerid = required_param('id', PARAM_INT);
$wantsurl = new moodle_url(optional_param('wantsurl', '', PARAM_URL));

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/auth/keycloak/login.php', ['id' => $issuerid]));

require_sesskey();

if (!\auth_oauth2\api::is_enabled()) {
    throw new \moodle_exception('notenabled', 'auth_oauth2');
}

$issuer = new \core\oauth2\issuer($issuerid);
if (!$issuer->is_available_for_login()) {
    throw new \moodle_exception('issuernologin', 'auth_oauth2');
}

if ($issuer->get('name') !== 'Keycloak') {
    redirect(new moodle_url('/auth/oauth2/login.php', ['id' => $issuerid]));
}

$returnparams = ['wantsurl' => $wantsurl, 'sesskey' => sesskey(), 'id' => $issuerid];
$returnurl = new moodle_url('/auth/keycloak/login.php', $returnparams);

$client = \core\oauth2\api::get_user_oauth_client($issuer, $returnurl);

if ($client) {
    if (!$client->is_logged_in()) {
        redirect($client->get_login_url());
    }

    require_once(__DIR__ . '/classes/custom_auth.php');
    $auth = new custom_auth();
    $auth->complete_login($client, $wantsurl);
} else {
    throw new \moodle_exception('Could not get an OAuth client.');
}
