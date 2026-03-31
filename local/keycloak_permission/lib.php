<?php
defined('MOODLE_INTERNAL') || die();

function local_keycloak_permission_before_user_authenticated_event(\core\event\user_loggedin $event) {
    global $SESSION, $DB;
    
    $userid = $event->objectid;
    $user = $DB->get_record('user', ['id' => $userid]);
    
    if (!$user || $user->auth !== 'oauth2') {
        return;
    }
    
    $config = get_config('local_keycloak_permission');
    
    if (empty($config->enable_permission_check)) {
        return;
    }
    
    $keycloakdata = $SESSION->keycloak_userinfo ?? [];
    
    if (empty($keycloakdata)) {
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
        require_logout();
        $SESSION->loginerrormsg = get_string('permission_denied', 'local_keycloak_permission');
        redirect(new moodle_url('/login/index.php'));
    }
}

function local_keycloak_permission_extend_navigation(\navigation_node $navigation) {
    global $PAGE;
    
    if ($PAGE->url->get_path() === '/login/index.php') {
        if (!empty($SESSION->loginerrormsg)) {
            $error_msg = $SESSION->loginerrormsg;
            if (strpos($error_msg, 'permission_denied') !== false) {
                $PAGE->set_title(get_string('permission_denied', 'local_keycloak_permission'));
            }
        }
    }
}
