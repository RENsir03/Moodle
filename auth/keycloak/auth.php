<?php

require_once(__DIR__ . '/../../auth/oauth2/auth.php');

/**
 * Keycloak authentication plugin.
 * Extends oauth2 plugin with Keycloak-specific features.
 */
class auth_plugin_keycloak extends auth_plugin_oauth2 {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->authtype = 'keycloak';
        $this->config = get_config('auth/keycloak');
    }

    /**
     * Override additional login parameters to force Keycloak to show login page.
     * This prevents SSO session caching and allows user switching.
     *
     * @return array
     */
    public function get_additional_login_parameters(): array {
        return [
            'prompt' => 'login',
        ];
    }

    /**
     * Hook function called when the user is about to be logged out.
     * This implements Single Logout (SLO) with Keycloak.
     */
    public function logoutpage_hook(): void {
        global $SESSION, $USER, $DB;

        if (empty($this->config->enable_slo)) {
            return;
        }

        $issuer = $DB->get_record('oauth2_issuer', ['name' => 'Keycloak']);
        if (!$issuer) {
            return;
        }

        $logoutrecord = $DB->get_record('oauth2_endpoint', [
            'issuerid' => $issuer->id,
            'name' => 'logout_endpoint'
        ]);

        if ($logoutrecord) {
            $logouturl = $logoutrecord->url;
        } else {
            $baseurl = rtrim($issuer->baseurl, '/');
            $logouturl = $baseurl . '/protocol/openid-connect/logout';
        }

        $redirecturl = new moodle_url('/');

        $params = [
            'post_logout_redirect_uri' => urlencode($redirecturl->out(false)),
        ];

        $linkedlogin = $DB->get_record('oauth2_linked_login', [
            'userid' => $USER->id,
            'issuerid' => $issuer->id
        ]);

        if ($linkedlogin && !empty($linkedlogin->token)) {
            $params['id_token_hint'] = $linkedlogin->token;
        }

        $logouturl .= '?' . http_build_query($params);

        $SESSION->keycloak_slo_logout_url = $logouturl;
    }

    /**
     * Returns true if this authentication plugin is SSO capable.
     *
     * @return bool
     */
    public function can_slo(): bool {
        return !empty($this->config->enable_slo);
    }

    /**
     * Hook for modifying the logout URL.
     *
     * @param moodle_url $logouturl
     * @return moodle_url
     */
    public function get_logout_url(moodle_url $logouturl): moodle_url {
        global $SESSION;

        if (!empty($SESSION->keycloak_slo_logout_url)) {
            return new moodle_url($SESSION->keycloak_slo_logout_url);
        }

        return $logouturl;
    }
}
