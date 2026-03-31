<?php

namespace local_keycloak_sync;

defined('MOODLE_INTERNAL') || die();

class auth_hook {

    public static function process_keycloak_userinfo(array $rawuserinfo): array {
        global $SESSION;

        $keycloakdata = [
            'raw_claims' => $rawuserinfo,
        ];

        if (!empty($rawuserinfo['realm_access']['roles'])) {
            $keycloakdata['realm_access'] = [
                'roles' => $rawuserinfo['realm_access']['roles'],
            ];
            $keycloakdata['roles'] = $rawuserinfo['realm_access']['roles'];
        }

        if (!empty($rawuserinfo['resource_access'])) {
            $keycloakdata['resource_access'] = $rawuserinfo['resource_access'];
        }

        if (!empty($rawuserinfo['course_enrollments'])) {
            $enrollments = $rawuserinfo['course_enrollments'];
            if (is_string($enrollments)) {
                $decoded = json_decode($enrollments, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $keycloakdata['course_enrollments'] = $decoded;
                } else {
                    $keycloakdata['course_enrollments'] = [$enrollments];
                }
            } else {
                $keycloakdata['course_enrollments'] = $enrollments;
            }
        }

        if (!empty($rawuserinfo['groups'])) {
            $keycloakdata['groups'] = $rawuserinfo['groups'];
        }

        $SESSION->keycloak_userdata = $keycloakdata;
        $SESSION->keycloak_userinfo = $rawuserinfo;

        debugging('local_keycloak_sync: Stored Keycloak data in session', DEBUG_DEVELOPER);

        return $keycloakdata;
    }

    public static function get_stored_keycloak_data(): ?array {
        global $SESSION;

        if (!empty($SESSION->keycloak_userdata)) {
            return $SESSION->keycloak_userdata;
        }

        return null;
    }

    public static function clear_stored_keycloak_data(): void {
        global $SESSION;
        unset($SESSION->keycloak_userdata);
        unset($SESSION->keycloak_userinfo);
    }

    public static function log_keycloak_data(array $data, string $context = ''): void {
        if (!debugging('', DEBUG_DEVELOPER)) {
            return;
        }

        $logmessage = 'local_keycloak_sync';
        if (!empty($context)) {
            $logmessage .= ' [' . $context . ']';
        }
        $logmessage .= ': ' . json_encode($data, JSON_PRETTY_PRINT);

        debugging($logmessage, DEBUG_DEVELOPER);
    }
}
