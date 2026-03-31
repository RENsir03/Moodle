<?php

namespace local_keycloak_sync;

defined('MOODLE_INTERNAL') || die();

class permission_checker {

    // Static variable to store debug info
    private static $debug_info_cache = null;

    public static function check_and_block_if_no_permission(): void {
        global $SESSION, $DB;
        
        $config = get_config('local_keycloak_permission');
        
        if (empty($config->enable_permission_check)) {
            debugging('permission_checker: Permission check is DISABLED', DEBUG_DEVELOPER);
            return;
        }

        $keycloakdata = $SESSION->keycloak_userdata ?? [];
        
        if (empty($keycloakdata)) {
            error_log('Keycloak Permission Check: No Keycloak data in session');
            debugging('permission_checker: No Keycloak data in session', DEBUG_DEVELOPER);
            return;
        }

        debugging('permission_checker: Checking user permissions...', DEBUG_DEVELOPER);
        debugging('permission_checker: Keycloak data: ' . json_encode($keycloakdata), DEBUG_DEVELOPER);

        $allowedroles = explode(',', $config->allowed_roles ?? 'moodle-admin,moodle-teacher,moodle-student');
        debugging('permission_checker: Allowed roles: ' . implode(', ', $allowedroles), DEBUG_DEVELOPER);
        
        $roles = [];

        // Check multiple possible locations for roles
        if (!empty($keycloakdata['realm_access']['roles'])) {
            $roles = $keycloakdata['realm_access']['roles'];
            debugging('permission_checker: Found roles in realm_access: ' . implode(', ', $roles), DEBUG_DEVELOPER);
        }
        
        if (empty($roles) && !empty($keycloakdata['roles'])) {
            $roles = $keycloakdata['roles'];
            debugging('permission_checker: Found roles in roles field: ' . implode(', ', $roles), DEBUG_DEVELOPER);
        }
        
        // Also check raw_claims for roles
        if (empty($roles) && !empty($keycloakdata['raw_claims'])) {
            $raw = $keycloakdata['raw_claims'];
            if (!empty($raw['realm_access']['roles'])) {
                $roles = $raw['realm_access']['roles'];
                debugging('permission_checker: Found roles in raw_claims.realm_access: ' . implode(', ', $roles), DEBUG_DEVELOPER);
            } elseif (!empty($raw['roles'])) {
                $roles = $raw['roles'];
                debugging('permission_checker: Found roles in raw_claims.roles: ' . implode(', ', $roles), DEBUG_DEVELOPER);
            }
        }

        // Get debug info from custom_auth if available
        $debug_info = isset($SESSION->keycloak_debug_info) ? $SESSION->keycloak_debug_info : null;

        $has_permission = false;
        foreach ($allowedroles as $allowedrole) {
            $allowedrole = trim($allowedrole);
            if (in_array($allowedrole, $roles)) {
                $has_permission = true;
                debugging('permission_checker: User has required role: ' . $allowedrole, DEBUG_DEVELOPER);
                break;
            }
        }

        if (!$has_permission) {
            error_log('Keycloak Permission Check: User DENIED - no required roles. Roles found: ' . implode(', ', $roles));
            debugging('permission_checker: User DENIED - no required roles found', DEBUG_DEVELOPER);
            
            // Store debug info in static variable before session cleanup
            self::$debug_info_cache = $debug_info;
            
            // Pass debug info to error page
            self::block_login_and_show_error($roles, $allowedroles, $debug_info);
        } else {
            debugging('permission_checker: User ALLOWED - has required role', DEBUG_DEVELOPER);
        }
    }

    private static function block_login_and_show_error(array $userroles, array $allowedroles, $debug_info = null): void {
        global $CFG;
        
        debugging('permission_checker: Blocking login and showing error', DEBUG_DEVELOPER);
        
        // Get Keycloak logout URL
        $keycloak_logout_url = self::get_keycloak_logout_url();
        
        // Build redirect URL with logout URL and debug info as parameters
        $redirect_params = [];
        if ($keycloak_logout_url) {
            $redirect_params['keycloak_logout'] = $keycloak_logout_url;
        }
        
        // Add debug info
        if (!empty($userroles)) {
            $redirect_params['debug_roles'] = implode(', ', $userroles);
        } else {
            $redirect_params['debug_roles'] = '(无角色)';
        }
        $redirect_params['debug_allowed'] = implode(', ', $allowedroles);
        
        // Add detailed debug info if available
        if ($debug_info) {
            $redirect_params['debug_detail'] = base64_encode($debug_info);
        }
        
        debugging('permission_checker: Redirecting with debug info - roles: ' . $redirect_params['debug_roles'] . ', allowed: ' . $redirect_params['debug_allowed'], DEBUG_DEVELOPER);
        
        // Clear Moodle session AFTER building redirect params
        require_logout();
        
        // Redirect to permission denied page with parameters
        redirect(new \moodle_url('/auth/keycloak/permission_denied.php', $redirect_params));
        exit;
    }
    
    /**
     * Get Keycloak logout URL from OAuth2 issuer configuration
     */
    private static function get_keycloak_logout_url(): ?string {
        global $DB;
        
        try {
            // Get the Keycloak issuer (assuming it's the first/only OAuth2 issuer)
            $issuer = $DB->get_record('oauth2_issuer', ['enabled' => 1], '*', IGNORE_MULTIPLE);
            
            if (!$issuer) {
                debugging('permission_checker: No OAuth2 issuer found', DEBUG_DEVELOPER);
                return null;
            }
            
            // Build Keycloak logout URL
            // Keycloak logout endpoint format: {baseurl}/protocol/openid-connect/logout
            $baseurl = rtrim($issuer->baseurl, '/');
            $logout_url = $baseurl . '/protocol/openid-connect/logout';
            
            debugging('permission_checker: Keycloak logout URL: ' . $logout_url, DEBUG_DEVELOPER);
            return $logout_url;
            
        } catch (\Exception $e) {
            debugging('permission_checker: Error getting Keycloak logout URL: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    public static function get_allowed_roles(): array {
        $config = get_config('local_keycloak_permission');
        $roles = $config->allowed_roles ?? 'moodle-admin,moodle-teacher,moodle-student';
        return array_map('trim', explode(',', $roles));
    }

    public static function has_required_role(array $keycloakdata): bool {
        $allowedroles = self::get_allowed_roles();
        $roles = [];

        if (!empty($keycloakdata['realm_access']['roles'])) {
            $roles = $keycloakdata['realm_access']['roles'];
        } elseif (!empty($keycloakdata['roles'])) {
            $roles = $keycloakdata['roles'];
        }

        foreach ($allowedroles as $allowedrole) {
            if (in_array($allowedrole, $roles)) {
                return true;
            }
        }

        return false;
    }
}
