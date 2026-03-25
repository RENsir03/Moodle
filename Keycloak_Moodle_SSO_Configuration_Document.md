# Keycloak 与 Moodle 单点登录（SSO）集成配置文档

## 目录

1. [系统概述](#系统概述)
2. [Keycloak 配置](#keycloak-配置)
3. [Moodle 配置](#moodle-配置)
4. [自定义插件说明](#自定义插件说明)
5. [配置验证](#配置验证)
6. [常见问题排查](#常见问题排查)

---

## 系统概述

### 环境信息

- **Moodle 版本**: 4.5
- **Keycloak 版本**: 24+
- **Moodle 地址**: http://10.70.5.223/moodle
- **Keycloak 地址**: http://10.70.5.223:8080
- **使用 Realm**: master（Keycloak 默认域）

### 实现功能

- ✅ 单点登录（SSO）- 用户在 Keycloak 登录后自动登录 Moodle
- ✅ 自动用户创建 - 首次登录时自动创建 Moodle 账户
- ✅ 角色同步 - 将 Keycloak 角色映射到 Moodle 角色
- ✅ 课程自动注册 - 根据 Keycloak 声明自动注册课程
- ✅ 单点登出（SLO）- 在 Moodle 登出时同时登出 Keycloak
- ✅ 多用户切换 - 允许切换到不同用户登录

---

## Keycloak 配置

### 1. 客户端配置（Client Configuration）

**访问路径**: Keycloak 管理控制台 → master realm → Clients → moodle-realm

#### 基础设置（Settings）

| 配置项 | 值 | 说明 |
|--------|-----|------|
| Client ID | `moodle-realm` | 客户端标识符 |
| Client Protocol | `openid-connect` | 使用 OpenID Connect 协议 |
| Access Type | `confidential` | 需要客户端密钥 |
| Client Authentication | `ON` | 启用客户端认证 |
| Standard Flow Enabled | `ON` | 启用授权码流程 |
| Direct Access Grants Enabled | `ON` | 启用直接访问授权 |
| Implicit Flow Enabled | `OFF` | 禁用隐式流程 |
| Service Accounts Enabled | `ON` | 启用服务账号 |

#### 重定向 URI 配置（Settings → Valid Redirect URIs）

```
http://10.70.5.223/moodle/admin/oauth2callback.php
http://10.70.5.223/moodle/*
```

#### Web Origins 配置（Settings → Web Origins）

```
http://10.70.5.223
```

### 2. 客户端密钥（Credentials）

**访问路径**: Keycloak 管理控制台 → Clients → moodle-realm → Credentials

生成并记录客户端密钥，后续需要在 Moodle 中配置。

### 3. 协议映射器（Client Scopes → moodle-realm dedicated → Mappers）

需要添加以下协议映射器以传递用户信息到 Moodle：

#### 3.1 邮箱映射（Email Mapper）

| 配置项 | 值 |
|--------|-----|
| Name | `email` |
| Mapper Type | User Attribute 或 Hardcoded claim |
| Token Claim Name | `email` |
| Claim JSON Type | `String` |
| Add to ID Token | `ON` |
| Add to access token | `ON` |
| Add to userinfo | `ON` |

#### 3.2 用户名映射（Username Mapper）

| 配置项 | 值 |
|--------|-----|
| Name | `preferred_username` |
| Mapper Type | User Attribute 或 Hardcoded claim |
| Token Claim Name | `preferred_username` |
| Claim JSON Type | `String` |
| Add to ID Token | `ON` |
| Add to access token | `ON` |
| Add to userinfo | `ON` |

#### 3.3 名映射（Given Name Mapper）

| 配置项 | 值 |
|--------|-----|
| Name | `given_name` |
| Mapper Type | User Attribute |
| Token Claim Name | `given_name` |
| Claim JSON Type | `String` |
| Add to ID Token | `ON` |
| Add to access token | `ON` |
| Add to userinfo | `ON` |

#### 3.4 姓映射（Family Name Mapper）

| 配置项 | 值 |
|--------|-----|
| Name | `family_name` |
| Mapper Type | User Attribute |
| Token Claim Name | `family_name` |
| Claim JSON Type | `String` |
| Add to ID Token | `ON` |
| Add to access token | `ON` |
| Add to userinfo | `ON` |

### 4. 角色配置（Roles）

在 master realm 中创建或配置以下角色：

| 角色名称 | 说明 | Moodle 映射 |
|---------|------|-------------|
| `moodle-admin` | 管理员角色 | Site Administrator (manager) |
| `moodle-teacher` | 教师角色 | Editing Teacher |
| `moodle-student` | 学生角色 | Student |

### 5. 用户角色分配（Users）

在 Keycloak 中为用户分配相应的角色：

1. 访问 **Users** 菜单
2. 选择或创建用户
3. 在 **Role Mapping** 标签页中分配角色
4. 用户登录 Moodle 时将自动获得对应权限

### 6. 自定义声明配置（可选 - 用于课程注册）

如果需要自动课程注册，可以在 Keycloak 中添加自定义声明：

#### 6.1 创建课程注册映射器

| 配置项 | 值 |
|--------|-----|
| Name | `course_enrollments` |
| Mapper Type | Hardcoded claim |
| Token Claim Name | `course_enrollments` |
| Claim JSON Type | `Array` |
| Add to ID Token | `ON` |
| Add to access token | `ON` |

#### 6.2 为用户设置课程

1. 编辑用户
2. 在 **Attributes** 标签页添加属性：
   - Key: `course_enrollments`
   - Value: `["course1", "course2", "course3"]`（课程短名称数组）

---

## Moodle 配置

### 1. OAuth2 提供者配置（OAuth2 Services）

**访问路径**: Moodle 管理 → 服务器 → OAuth 2 服务 → Keycloak

#### 基础配置

| 配置项 | 值 |
|--------|-----|
| 名称 | `Keycloak` |
| Base URL | `http://10.70.5.223:8080/realms/master` |
| Client ID | `moodle-realm` |
| Client Secret | （从 Keycloak Credentials 获取） |
| 启用 | ✓ |
| 显示在登录页面 | ✓ |
| basicauth | ✓ |

#### 端点配置（自动发现）

系统会自动发现以下端点：

| 端点名称 | URL |
|---------|-----|
| Authorization Endpoint | `http://10.70.5.223:8080/realms/master/protocol/openid-connect/auth` |
| Token Endpoint | `http://10.70.5.223:8080/realms/master/protocol/openid-connect/token` |
| Userinfo Endpoint | `http://10.70.5.223:8080/realms/master/protocol/openid-connect/userinfo` |
| Logout Endpoint | `http://10.70.5.223:8080/realms/master/protocol/openid-connect/logout` |

### 2. 字段映射配置（Field Mapping）

**访问路径**: OAuth2 服务 → Keycloak → 字段映射

| Moodle 字段 | Keycloak 声明 | 说明 |
|------------|---------------|------|
| `username` | `preferred_username` | 用户登录名 |
| `email` | `email` | 用户邮箱 |
| `firstname` | `given_name` | 用户名 |
| `lastname` | `family_name` | 用户姓氏 |

### 3. cURL 安全配置

**访问路径**: Moodle 管理 → 安全 → HTTP 安全

| 配置项 | 值 | 说明 |
|--------|-----|------|
| curlsecurityblockedhosts | （移除 10.0.0.0/8 限制） | 允许访问内网 Keycloak |
| curlsecurityallowedport | `443,80,8080` | 允许 8080 端口 |

---

## 自定义插件说明

### 1. local_keycloak_sync 插件

**路径**: `/var/www/html/moodle/local/keycloak_sync/`

#### 功能

- 用户登录事件处理
- 用户创建事件处理
- 角色同步（Keycloak roles → Moodle roles）
- 课程自动注册
- 单点登出支持

#### 事件监听（db/events.php）

```php
$observers = [
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'local_keycloak_sync\observer::user_loggedin',
        'priority' => 100,
    ],
    [
        'eventname' => '\core\event\user_created',
        'callback' => 'local_keycloak_sync\observer::user_created',
        'priority' => 100,
    ],
    [
        'eventname' => '\core\event\user_loggedout',
        'callback' => 'local_keycloak_sync\observer::user_loggedout',
        'priority' => 100,
    ],
];
```

#### 角色映射规则（classes/observer.php）

| Keycloak 角色 | Moodle 角色 | 权限级别 |
|--------------|------------|---------|
| `moodle-admin` | Manager | 站点管理员 |
| `moodle-teacher` | Editing Teacher | 教师 |
| `moodle-student` | Student | 学生 |

#### 课程注册配置

从 Keycloak 的 `course_enrollments` 声明中读取课程短名称列表，自动将用户注册到对应课程。

### 2. auth_keycloak 插件

**路径**: `/var/www/html/moodle/auth/keycloak/`

#### 功能

- 继承 oauth2 认证插件
- 添加 Keycloak 特定的登录参数
- 实现单点登出（SLO）
- 支持多用户切换

#### 核心配置

**添加 prompt=login 参数**（auth.php）

```php
public function get_additional_login_parameters(): array {
    return [
        'prompt' => 'login',
    ];
}
```

此配置确保每次登录都显示 Keycloak 登录页面，允许切换用户。

#### 单点登出实现（logoutpage_hook）

当用户从 Moodle 登出时：
1. 获取 Keycloak logout endpoint
2. 构建包含 post_logout_redirect_uri 的 logout URL
3. 将用户重定向到 Keycloak logout URL
4. 销毁 Keycloak 会话

### 3. logout.php 修改

**路径**: `/var/www/html/moodle/login/logout.php`

添加了 Keycloak SSO logout 支持：

```php
foreach($authsequence as $authname) {
    $authplugin = get_auth_plugin($authname);
    $authplugin->logoutpage_hook();
    
    if (!empty($SESSION->keycloak_slo_logout_url)) {
        require_logout();
        redirect($SESSION->keycloak_slo_logout_url);
        die;
    }
}
```

---

## 配置验证

### 1. 验证单点登录

1. 清除浏览器缓存或使用无痕模式
2. 访问 Moodle 登录页面
3. 点击 "Keycloak" 登录按钮
4. 在 Keycloak 登录页面输入凭证
5. 验证成功跳转到 Moodle 并自动登录

**预期结果**:
- ✅ 跳转到 Keycloak 登录页面
- ✅ 登录成功后自动创建/更新 Moodle 账户
- ✅ 自动同步角色和课程注册

### 2. 验证多用户切换

1. 使用用户 A 登录 Moodle
2. 点击"退出登录"
3. 再次点击 "Keycloak" 登录按钮
4. 验证 Keycloak 登录页面出现（而非直接登录）

**预期结果**:
- ✅ 每次登录都会显示 Keycloak 登录页面
- ✅ 可以使用不同用户凭证登录
- ✅ URL 中包含 `prompt=login` 参数

### 3. 验证单点登出

1. 登录 Moodle
2. 点击"退出登录"
3. 验证重定向到 Keycloak logout
4. 验证 Keycloak 会话被销毁

**预期结果**:
- ✅ 退出时同时销毁 Keycloak 会话
- ✅ 再次登录需要重新输入凭证

### 4. 验证角色同步

1. 在 Keycloak 中为用户分配 `moodle-admin` 角色
2. 用户登录 Moodle
3. 检查用户权限

**预期结果**:
- ✅ 用户获得站点管理员权限
- ✅ 可以在 Moodle 管理后台操作

---

## 常见问题排查

### 问题 1: 400 Bad Request 错误

**症状**: 登录时出现 400 Bad Request

**原因**: 
- Keycloak 客户端配置为 bearer-only
- Client ID 不匹配

**解决方案**:
1. 检查 Keycloak 客户端的 Access Type 是否为 confidential
2. 确保 Standard Flow Enabled 为 ON
3. 验证 Client ID 与 Moodle 配置一致

### 问题 2: 403 Forbidden 错误

**症状**: Keycloak 显示 "Bearer-only 的应用不允许通过浏览器登录"

**原因**: 客户端配置为 bearer-only 模式

**解决方案**:
1. 在 Keycloak 客户端设置中关闭 bearer-only
2. 确保 Standard Flow Enabled 为 ON
3. 重新生成客户端密钥并更新 Moodle 配置

### 问题 3: 无法切换用户

**症状**: 登录后无法切换到其他用户

**原因**: Keycloak SSO 会话缓存

**解决方案**:
1. 使用 auth_keycloak 插件
2. 确保 get_additional_login_parameters() 返回 `prompt=login`
3. 清除浏览器缓存

### 问题 4: 账户信息缺失

**症状**: 用户名或邮箱为空

**原因**: 字段映射配置错误

**解决方案**:
1. 检查 mdl_oauth2_user_field_mapping 表
2. 确保 internalfield 和 externalfield 都已正确配置
3. 验证 Keycloak 映射器返回正确的声明

### 问题 5: 角色不同步

**症状**: 用户在 Keycloak 有角色但 Moodle 中没有

**原因**: local_keycloak_sync 插件未正确配置

**解决方案**:
1. 检查插件是否已安装并启用
2. 验证 Keycloak 中的角色名称
3. 检查 mdl_config 表中 local_keycloak_sync 配置

---

## 附录

### 文件结构

```
/var/www/html/moodle/
├── auth/
│   ├── oauth2/          # OAuth2 认证插件
│   │   ├── auth.php
│   │   └── ...
│   └── keycloak/         # Keycloak 自定义插件
│       ├── auth.php      # 主要逻辑
│       ├── version.php
│       ├── settings.php
│       ├── lang/en/auth_keycloak.php
│       └── db/access.php
├── local/
│   └── keycloak_sync/    # 同步插件
│       ├── lib.php
│       ├── version.php
│       ├── settings.php
│       ├── classes/
│       │   ├── observer.php    # 事件观察者
│       │   └── auth_hook.php   # 认证钩子
│       ├── db/
│       │   └── events.php      # 事件定义
│       └── lang/en/local_keycloak_sync.php
└── login/
    └── logout.php        # 已修改支持 SLO
```

### 数据库关键表

| 表名 | 说明 |
|------|------|
| `mdl_oauth2_issuer` | OAuth2 服务配置 |
| `mdl_oauth2_endpoint` | OAuth2 端点配置 |
| `mdl_oauth2_user_field_mapping` | 字段映射配置 |
| `mdl_oauth2_linked_login` | 用户 OAuth 登录记录 |
| `mdl_config` | Moodle 配置 |

### 关键配置项

| 配置名 | 值 | 说明 |
|--------|-----|------|
| `auth_keycloak/enable_slo` | 1 | 启用单点登出 |
| `local_keycloak_sync/enable_sso_logout` | 1 | 启用 SSO 登出 |

---

## 版本信息

- **文档版本**: 1.0
- **创建日期**: 2024-03-24
- **适用环境**: Moodle 4.5 + Keycloak 24+

---

## 联系方式

如有问题，请检查：
1. Keycloak 管理控制台日志
2. Moodle 调试日志（设置 `$CFG->debug = DEBUG_DEVELOPER;`）
3. 服务器错误日志
