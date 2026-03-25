<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
require_once($CFG->libdir . '/clilib.php');

echo "Fixing Keycloak OAuth2 Configuration\n";
echo "====================================\n\n";

// Find the Keycloak issuer
$issuer = $DB->get_record('oauth2_issuer', ['name' => 'Keycloak']);

if (!$issuer) {
    echo "ERROR: Keycloak issuer not found in database\n";
    exit(1);
}

echo "Found Keycloak issuer (ID: {$issuer->id})\n";
echo "Current baseurl: {$issuer->baseurl}\n";
echo "Current clientid: {$issuer->clientid}\n\n";

// Update issuer to use master realm
$new_baseurl = 'http://10.70.5.223:8080/realms/master';
$issuer->baseurl = $new_baseurl;
$issuer->clientid = 'moodle-realm';

$DB->update_record('oauth2_issuer', $issuer);
echo "Updated issuer to use master realm\n\n";

// Update endpoints
$endpoints = [
    'authorization_endpoint' => '/protocol/openid-connect/auth',
    'token_endpoint' => '/protocol/openid-connect/token',
    'userinfo_endpoint' => '/protocol/openid-connect/userinfo',
];

foreach ($endpoints as $name => $path) {
    $endpoint = $DB->get_record('oauth2_endpoint', [
        'issuerid' => $issuer->id,
        'name' => $name
    ]);

    if ($endpoint) {
        $old_url = $endpoint->url;
        $new_url = $new_baseurl . $path;
        $new_url = str_replace('/realms/moodle/', '/realms/master/', $new_url);

        $endpoint->url = $new_url;
        $DB->update_record('oauth2_endpoint', $endpoint);
        echo "Updated {$name}:\n";
        echo "  Old: {$old_url}\n";
        echo "  New: {$new_url}\n";
    } else {
        echo "Warning: {$name} not found\n";
    }
}

echo "\nClearing caches...\n";
purge_all_caches();
echo "Caches cleared!\n";

echo "\n========================================\n";
echo "Configuration update complete!\n";
echo "========================================\n";
echo "\nIMPORTANT: Please clear your browser cache or use incognito mode!\n";
echo "The old login URL may be cached in your browser.\n";
