<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Keycloak Permission Check';
$string['permission_denied'] = '您无权访问该系统';
$string['permission_denied_message'] = '您无权访问该系统，当前账户未被授权使用此服务。如需访问权限，请联系系统管理员。';
$string['permission_denied_title'] = '访问被拒绝';
$string['enable_permission_check'] = '启用权限检查';
$string['enable_permission_check_desc'] = '启用后，只有在 Keycloak 中拥有指定角色的用户才能登录 Moodle。未授权用户将无法访问系统。';
$string['allowed_roles'] = '允许的角色';
$string['allowed_roles_desc'] = '逗号分隔的角色列表，例如: moodle-admin,moodle-teacher,moodle-student。只有拥有这些角色的 Keycloak 用户才能登录 Moodle。';
$string['settings'] = 'Keycloak 权限设置';
$string['settings_desc'] = '配置哪些 Keycloak 用户可以访问 Moodle 系统。';
$string['permission_denied_error'] = '您无权访问该系统';
