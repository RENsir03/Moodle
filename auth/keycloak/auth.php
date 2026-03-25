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

        // Check if this user is linked to Keycloak (regardless of auth type)
        $issuer = $DB->get_record('oauth2_issuer', ['name' => 'Keycloak']);
        if (!$issuer) {
            return;
        }

        // Check if user has linked login with Keycloak
        $linkedlogin = $DB->get_record('oauth2_linked_login', [
            'userid' => $USER->id,
            'issuerid' => $issuer->id
        ]);

        // If no linked login, check if user auth is oauth2/keycloak
        if (!$linkedlogin && $USER->auth !== 'oauth2' && $USER->auth !== 'keycloak') {
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

        // Keycloak logout requires id_token_hint for proper logout
        $params = [
            'post_logout_redirect_uri' => $redirecturl->out(false),
        ];

        // Try to get id_token from linked_login
        if ($linkedlogin && !empty($linkedlogin->token)) {
            $params['id_token_hint'] = $linkedlogin->token;
        }

        // Build logout URL
        $logouturl .= '?' . http_build_query($params);

        // Store in session for redirect after Moodle logout
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
