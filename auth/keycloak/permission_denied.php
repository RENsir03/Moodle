<?php
require_once(__DIR__ . '/../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_title('访问被拒绝');
$PAGE->set_heading('访问被拒绝');

echo $OUTPUT->header();
echo $OUTPUT->box_start();

// Get Keycloak logout URL from URL parameter
$keycloak_logout_url = optional_param('keycloak_logout', null, PARAM_URL);

// Get debug info from URL parameter (if available)
$debug_roles = optional_param('debug_roles', '', PARAM_TEXT);
$debug_allowed = optional_param('debug_allowed', '', PARAM_TEXT);
$debug_detail_encoded = optional_param('debug_detail', '', PARAM_TEXT);

// Decode detailed debug info
$detailed_debug = '';
if (!empty($debug_detail_encoded)) {
    $detailed_debug = base64_decode($debug_detail_encoded);
}

echo '<div style="text-align: center; padding: 50px;">';
echo '<h2 style="color: #c0392b; margin-bottom: 20px;">❌ 您无权访问该系统</h2>';
echo '<p style="font-size: 16px; color: #333; margin-bottom: 30px;">';
echo '当前账户未被授权使用此服务。<br>';
echo '如需访问权限，请联系系统管理员。';
echo '</p>';

// Show debug information
if ($debug_roles || $debug_allowed || $detailed_debug) {
    echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin-bottom: 20px; text-align: left; max-width: 900px; margin-left: auto; margin-right: auto;">';
    echo '<h4 style="color: #495057; margin-bottom: 10px;">调试信息：</h4>';
    
    if ($debug_roles) {
        echo '<p style="margin: 5px 0;"><strong>您的角色：</strong>' . htmlspecialchars($debug_roles) . '</p>';
    }
    if ($debug_allowed) {
        echo '<p style="margin: 5px 0;"><strong>允许的角色：</strong>' . htmlspecialchars($debug_allowed) . '</p>';
    }
    
    // Show detailed debug info
    if ($detailed_debug) {
        echo '<hr style="margin: 15px 0;">';
        echo '<h5 style="color: #495057; margin-bottom: 10px;">详细调试信息（从Keycloak获取的原始数据）：</h5>';
        echo '<pre style="background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 11px; text-align: left; max-height: 400px; overflow-y: auto;">';
        echo htmlspecialchars($detailed_debug);
        echo '</pre>';
    }
    
    echo '</div>';
}

// If Keycloak logout URL is available, show logout button
if ($keycloak_logout_url) {
    echo '<div style="margin-bottom: 20px;">';
    echo '<a href="' . $keycloak_logout_url . '" class="btn btn-secondary" style="padding: 10px 30px; background: #e74c3c; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;">退出 Keycloak 登录</a>';
    echo '</div>';
    echo '<p style="font-size: 14px; color: #666; margin-bottom: 20px;">';
    echo '提示：点击"退出 Keycloak 登录"可完全退出当前账户，避免自动登录。';
    echo '</p>';
}

echo '<a href="' . new moodle_url('/login/index.php') . '" class="btn btn-primary" style="padding: 10px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">返回登录页面</a>';
echo '</div>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
