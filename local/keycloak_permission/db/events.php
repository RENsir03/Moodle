<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'local_keycloak_permission_observer::check_user_permission',
        'priority' => 200,
    ],
];
