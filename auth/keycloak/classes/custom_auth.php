<?php

require_once(__DIR__ . '/../../oauth2/classes/auth.php');

class custom_auth extends \auth_oauth2\auth {

    public function complete_login(\core\oauth2\client $client, $wantsurl) {
        global $SESSION, $DB;
        
        require_once(__DIR__ . '/../../../local/keycloak_sync/classes/permission_checker.php');
        require_once(__DIR__ . '/../../../local/keycloak_sync/classes/auth_hook.php');
        
        $userinfo = $client->get_userinfo();
        
        if (is_array($userinfo)) {
            // Try to get token and extract JWT payload
            try {
                $token = $client->get_accesstoken();
                
                $access_token = null;
                if (is_array($token) && isset($token['token'])) {
                    $access_token = $token['token'];
                } elseif (is_object($token) && isset($token->token)) {
                    $access_token = $token->token;
                }
                
                if ($access_token) {
                    // Decode JWT token
                    $jwt_parts = explode('.', $access_token);
                    
                    if (count($jwt_parts) >= 2) {
                        // Base64Url decode
                        $payload_json = base64_decode(str_replace(['-', '_'], ['+', '/'], $jwt_parts[1]));
                        $payload = json_decode($payload_json, true);
                        
                        // Merge JWT claims with userinfo (JWT claims take precedence)
                        if (is_array($payload)) {
                            // Specifically look for realm_access.roles
                            if (isset($payload['realm_access']['roles'])) {
                                $userinfo['realm_access'] = $payload['realm_access'];
                            }
                            
                            // Also check for roles directly
                            if (isset($payload['roles'])) {
                                $userinfo['roles'] = $payload['roles'];
                            }
                            
                            // Merge all payload data
                            $userinfo = array_merge($payload, $userinfo);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue
                debugging('custom_auth: Error getting token: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
            
            $processeduserinfo = \local_keycloak_sync\auth_hook::process_keycloak_userinfo($userinfo);
            $SESSION->keycloak_userdata = $processeduserinfo;
            
            \local_keycloak_sync\permission_checker::check_and_block_if_no_permission();
        }
        
        return parent::complete_login($client, $wantsurl);
    }
}
