<?php
namespace local_keycloak_permission;

defined('MOODLE_INTERNAL') || die();

class observer {
    
    public static function check_user_permission(\core\event\user_loggedin $event) {
        global $SESSION, $DB;
        
        $userid = $event->objectid;
        $user = $DB->get_record('user', ['id' => $userid]);
        
        if (!$user) {
            return;
        }
        
        if ($user->auth !== 'oauth2') {
            return;
        }
        
        $config = get_config('local_keycloak_permission');
        
        if (empty($config->enable_permission_check)) {
            return;
        }
        
        $keycloakdata = $SESSION->keycloak_userinfo ?? [];
        
        if (empty($keycloakdata)) {
            error_log('local_keycloak_permission: No Keycloak data found in session');
            return;
        }
        
        $allowedroles = explode(',', $config->allowed_roles ?? 'moodle-admin,moodle-teacher,moodle-student');
        $roles = [];
        
        if (!empty($keycloakdata['realm_access']['roles'])) {
            $roles = $keycloakdata['realm_access']['roles'];
        } elseif (!empty($keycloakdata['roles'])) {
            $roles = $keycloakdata['roles'];
        }
        
        $has_permission = false;
        foreach ($allowedroles as $allowedrole) {
            $allowedrole = trim($allowedrole);
            if (in_array($allowedrole, $roles)) {
                $has_permission = true;
                break;
            }
        }
        
        if (!$has_permission) {
            error_log('local_keycloak_permission: User ' . $user->username . ' denied - no required roles');
            
            require_logout();
            
            $SESSION->loginerrormsg = get_string('permission_denied', 'local_keycloak_permission');
            
            redirect(new moodle_url('/login/index.php'), 
                get_string('permission_denied_message', 'local_keycloak_permission'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    }
}
