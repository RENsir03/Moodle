<?php

require_once(__DIR__ . '/../../oauth2/classes/api.php');

class api extends \auth_oauth2\api {

    public static function complete_login($issuer, $rawusertoken, $browser) {
        global $SESSION, $DB;
        
        require_once(__DIR__ . '/../../local/keycloak_sync/classes/permission_checker.php');
        require_once(__DIR__ . '/../../local/keycloak_sync/classes/auth_hook.php');
        
        $userinfo = self::get_userinfo($issuer, $rawusertoken);
        
        $processeduserinfo = \local_keycloak_sync\auth_hook::process_keycloak_userinfo($userinfo);
        
        $SESSION->keycloak_userdata = $processeduserinfo;
        
        \local_keycloak_sync\permission_checker::check_and_block_if_no_permission();
        
        return parent::complete_login($issuer, $rawusertoken, $browser);
    }
}
