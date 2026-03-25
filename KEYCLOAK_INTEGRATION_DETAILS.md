# Moodle 与 Keycloak 集成详细文档

## 目录

1. [系统架构概览](#1-系统架构概览)
2. [环境信息](#2-环境信息)
3. [Keycloak 服务端配置](#3-keycloak-服务端配置)
4. [Moodle 端配置](#4-moodle-端配置)
5. [OAuth2 端点配置](#5-oauth2-端点配置)
6. [用户属性映射](#6-用户属性映射)
7. [角色映射配置](#7-角色映射配置)
8. [单点登出配置](#8-单点登出配置)
9. [课程自动注册](#9-课程自动注册)
10. [安全与调试](#10-安全与调试)
11. [故障排除](#11-故障排除)
12. [相关文件](#12-相关文件)

---

## 1. 系统架构概览

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│     用户        │────▶│   Keycloak       │────▶│     Moodle      │
│   (浏览器)       │◀────│  (身份提供者)     │◀────│   (服务提供方)   │
└─────────────────┘     └──────────────────┘     └─────────────────┘
         │                                               │
         │            OpenID Connect 协议               │
         │         (OAuth 2.0 + JWT Token)              │
         └───────────────────────────────────────────────┘
```

**集成流程：**
1. 用户访问 Moodle，选择 "使用 Keycloak 登录"
2. 重定向到 Keycloak 登录页面
3. 用户在 Keycloak 认证成功后，携带 Authorization Code 返回 Moodle
4. Moodle 使用 Code 向 Keycloak 换取 Access Token 和 ID Token
5. Moodle 解析 Token 获取用户信息，完成登录
6. 可选：同步用户角色、自动注册课程

---

## 2. 环境信息

| 组件 | 地址/版本 | 说明 |
|------|----------|------|
| **Keycloak Server** | `http://10.70.5.223:8080` | 身份认证服务器 |
| **Moodle Server** | `http://10.70.5.223/moodle` | 学习管理系统 |
| **Keycloak Realm** | `master` | 使用的 Realm |
| **Client ID** | `moodle-realm` | Moodle 在 Keycloak 中注册的客户端 ID |
| **协议** | OpenID Connect | 使用 OIDC 协议进行 SSO |

---

## 3. Keycloak 服务端配置

### 3.1 基础配置

| 配置项 | 值 | 说明 |
|--------|-----|------|
| **Client ID** | `moodle-realm` | 客户端标识符 |
| **Client Secret** | `JgXAR9cvOLGkRmP8GWj9e0UWe50hrw1H` | 客户端密钥（保密） |
| **Access Type** | `confidential` | 支持 Client Secret 验证 |
| **Standard Flow Enabled** | ✅ ON | 启用标准 OIDC 流程 |
| **Client Authentication** | ✅ ON | 需要客户端认证 |

### 3.2 有效重定向 URI

```
http://10.70.5.223/moodle/admin/oauth2callback.php
http://10.70.5.223/moodle/*
```

**说明：**
- 第一个 URI 是 OAuth2 回调地址，处理 Keycloak 返回的授权码
- 第二个 URI 是通配符，允许 Moodle 所有子路径

### 3.3 Web Origins

```
http://10.70.5.223
```

**说明：** 允许 Moodle 域名跨域访问 Keycloak 资源

### 3.4 协议映射器 (Protocol Mappers)

#### 3.4.1 Email Mapper
| 属性 | 值 |
|------|-----|
| Name | `email` |
| Token Claim Name | `email` |
| Claim JSON Type | String |
| Add to ID Token | ✅ ON |
| Add to Access Token | ✅ ON |
| Add to Userinfo | ✅ ON |

#### 3.4.2 Username Mapper
| 属性 | 值 |
|------|-----|
| Name | `preferred_username` |
| Token Claim Name | `preferred_username` |
| Claim JSON Type | String |
| Add to ID Token | ✅ ON |
| Add to Access Token | ✅ ON |
| Add to Userinfo | ✅ ON |

#### 3.4.3 Given Name Mapper
| 属性 | 值 |
|------|-----|
| Name | `given_name` |
| Token Claim Name | `given_name` |
| Claim JSON Type | String |
| Add to ID Token | ✅ ON |
| Add to Access Token | ✅ ON |
| Add to Userinfo | ✅ ON |

#### 3.4.4 Family Name Mapper
| 属性 | 值 |
|------|-----|
| Name | `family_name` |
| Token Claim Name | `family_name` |
| Claim JSON Type | String |
| Add to ID Token | ✅ ON |
| Add to Access Token | ✅ ON |
| Add to Userinfo | ✅ ON |

---

## 4. Moodle 端配置

### 4.1 认证插件启用顺序

```
1. keycloak (自定义 OIDC 插件)
2. oauth2 (Moodle 内置 OAuth2)
3. manual (手动/邮件认证)
```

### 4.2 认证配置数据库表

| 表名 | 说明 |
|------|------|
| `mdl_oauth2_issuer` | OAuth2 服务提供商配置 |
| `mdl_oauth2_endpoint` | OAuth2 端点配置 |
| `mdl_oauth2_user_field_mapping` | 用户字段映射配置 |
| `mdl_oauth2_linked_login` | 用户 OAuth 登录关联记录 |
| `mdl_config` | 系统配置参数 |

### 4.3 auth_keycloak 插件配置

**文件位置：** `/var/www/html/moodle/auth/keycloak/`

#### 主要配置项：

| 配置项 | 数据库名 | 默认值 | 说明 |
|--------|---------|--------|------|
| Enable SLO | `auth_keycloak/enable_slo` | `1` | 启用单点登出 |
| Login Scopes | `auth_keycloak/login_scopes` | `openid profile email` | 登录请求 scope |
| Client ID | `oauth2_client_id` | `moodle-realm` | Keycloak 客户端 ID |
| Client Secret | `oauth2_client_secret` | (加密存储) | Keycloak 客户端密钥 |

#### 额外登录参数：

```php
public function get_additional_login_parameters(): array {
    return [
        'prompt' => 'login',
    ];
}
```

**作用：** 强制显示登录页面，防止自动 SSO 跳转，允许用户切换到其他账号。

### 4.4 local_keycloak_sync 插件配置

**文件位置：** `/var/www/html/moodle/local/keycloak_sync/`

| 配置项 | 数据库名 | 默认值 | 说明 |
|--------|---------|--------|------|
| Enable SSO Logout | `local_keycloak_sync/enable_sso_logout` | `1` | 启用 SSO 登出 |
| Admin Role Claim | `local_keycloak_sync/admin_role` | `moodle-admin` | 管理员角色声明 |
| Teacher Role Claim | `local_keycloak_sync/teacher_role` | `moodle-teacher` | 教师角色声明 |
| Student Role Claim | `local_keycloak_sync/student_role` | `moodle-student` | 学生角色声明 |
| Course Enrollment Claim | `local_keycloak_sync/course_claim` | `course_enrollments` | 课程注册声明名 |
| Teacher Course Category | `local_keycloak_sync/teacher_category` | `默认课程类别` | 教师课程类别 |
| Enable Debug Logging | `local_keycloak_sync/enable_debug` | `0` | 启用调试日志 |

---

## 5. OAuth2 端点配置

### 5.1 端点列表

| 端点类型 | URL | 数据库 ID |
|---------|-----|-----------|
| **Authorization Endpoint** | `http://10.70.5.223:8080/realms/master/protocol/openid-connect/auth` | `auth` |
| **Token Endpoint** | `http://10.70.5.223:8080/realms/master/protocol/openid-connect/token` | `token` |
| **Userinfo Endpoint** | `http://10.70.5.223:8080/realms/master/protocol/openid-connect/userinfo` | `userinfo` |
| **Logout Endpoint** | `http://10.70.5.223:8080/realms/master/protocol/openid-connect/logout` | `logout` |

### 5.2 完整 Base URL

```
http://10.70.5.223:8080/realms/master
```

### 5.3 授权请求参数

```
GET /realms/master/protocol/openid-connect/auth?
  client_id=moodle-realm&
  response_type=code&
  scope=openid profile email&
  redirect_uri=http://10.70.5.223/moodle/admin/oauth2callback.php&
  state=xxxxxxxx&
  prompt=login
```

### 5.4 Token 请求参数

```
POST /realms/master/protocol/openid-connect/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&
code=xxxxxxxx&
redirect_uri=http://10.70.5.223/moodle/admin/oauth2callback.php&
client_id=moodle-realm&
client_secret=JgXAR9cvOLGkRmP8GWj9e0UWe50hrw1H
```

---

## 6. 用户属性映射

### 6.1 字段映射表

| Moodle 字段 | Keycloak 声明 | 说明 | 必填 |
|------------|--------------|------|------|
| `username` | `preferred_username` | 用户登录名 | ✅ 是 |
| `email` | `email` | 用户邮箱地址 | ✅ 是 |
| `firstname` | `given_name` | 用户名字 | ✅ 是 |
| `lastname` | `family_name` | 用户姓氏 | ✅ 是 |

### 6.2 数据库存储结构

```sql
-- 字段映射配置表 (mdl_oauth2_user_field_mapping)
+----+-----------+--------------+----------------+------------------+--------+
| id | issuerid  | externalfield| internalfield  | updateinternal   | locked |
+----+-----------+--------------+----------------+------------------+--------+
| 1  | 1         | username     | username       | 1                | 0      |
| 2  | 1         | email        | email          | 1                | 0      |
| 3  | 1         | firstname    | firstname      | 1                | 0      |
| 4  | 1         | lastname     | lastname       | 1                | 0      |
+----+-----------+--------------+----------------+------------------+--------+
```

### 6.3 ID Token 示例

```json
{
  "exp": 1704067200,
  "iat": 1704063600,
  "sub": "user-uuid-12345",
  "preferred_username": "zhangsan",
  "email": "zhangsan@example.com",
  "given_name": "三",
  "family_name": "张",
  "realm_access": {
    "roles": ["moodle-student"]
  }
}
```

---

## 7. 角色映射配置

### 7.1 角色对照表

| Keycloak 角色 | Moodle 角色 | 权限级别 | 说明 |
|--------------|------------|---------|------|
| `moodle-admin` | Manager | 站点管理员 | 全站管理权限 |
| `moodle-teacher` | Editing Teacher | 教师 | 课程创建和编辑权限 |
| `moodle-student` | Student | 学生 | 学习权限 |

### 7.2 角色声明位置

**在 ID Token 中：**
```json
{
  "realm_access": {
    "roles": [
      "moodle-student",
      "offline_access"
    ]
  },
  "resource_access": {
    "moodle-realm": {
      "roles": ["moodle-student"]
    }
  }
}
```

### 7.3 角色同步逻辑

```php
// 从 Keycloak 角色映射到 Moodle 角色
$keycloak_roles = $token_data['realm_access']['roles'] ?? [];

$role_mapping = [
    'moodle-admin'    => ['manager', 'coursecreator'],
    'moodle-teacher'  => ['editingteacher', 'teacher'],
    'moodle-student'  => ['student'],
];

foreach ($keycloak_roles as $kc_role) {
    if (isset($role_mapping[$kc_role])) {
        // 分配对应的 Moodle 角色
        assign_moodle_roles($user, $role_mapping[$kc_role]);
    }
}
```

---

## 8. 单点登出配置

### 8.1 SLO 流程

```
1. 用户点击 Moodle 登出
2. Moodle 调用 Keycloak 登出端点
3. Keycloak 销毁用户会话
4. 重定向回 Moodle 首页
5. 用户在其他应用也会被登出
```

### 8.2 配置参数

| 配置项 | 值 | 说明 |
|--------|-----|------|
| **SLO 启用状态** | 已启用 | `auth_keycloak/enable_slo = 1` |
| **Post Logout Redirect URI** | `http://10.70.5.223/moodle/` | 登出后跳转地址 |
| **ID Token Hint** | 从数据库获取 | 可选参数，加快登出处理 |

### 8.3 登出 URL 构建

```php
$logout_url = 'http://10.70.5.223:8080/realms/master/protocol/openid-connect/logout';

$params = [
    'post_logout_redirect_uri' => 'http://10.70.5.223/moodle/',
];

// 添加 id_token_hint（如果存在）
if ($linkedlogin && !empty($linkedlogin->token)) {
    $params['id_token_hint'] = $linkedlogin->token;
}

$full_logout_url = $logout_url . '?' . http_build_query($params);
```

### 8.4 完整登出 URL 示例

```
http://10.70.5.223:8080/realms/master/protocol/openid-connect/logout?
  post_logout_redirect_uri=http://10.70.5.223/moodle/&
  id_token_hint=eyJhbGciOiJSUzI1NiIs...
```

---

## 9. 课程自动注册

### 9.1 配置参数

| 配置项 | 数据库名 | 默认值 | 说明 |
|--------|---------|--------|------|
| Course Enrollment Claim | `local_keycloak_sync/course_claim` | `course_enrollments` | Token 中的课程声明名 |
| Teacher Course Category | `local_keycloak_sync/teacher_category` | `默认课程类别` | 教师创建课程的类别 |

### 9.2 Token 中的课程声明示例

```json
{
  "course_enrollments": [
    {
      "course_id": "CS101",
      "role": "student"
    },
    {
      "course_id": "CS201",
      "role": "teacher"
    }
  ]
}
```

### 9.3 自动注册逻辑

1. 用户登录时解析 `course_enrollments` 声明
2. 根据 `course_id` 查找或创建对应课程
3. 根据 `role` 分配相应的 Moodle 角色
4. 教师角色自动分配到指定类别下的课程

---

## 10. 安全与调试

### 10.1 cURL 安全配置

| 配置项 | 数据库名 | 值 | 说明 |
|--------|---------|-----|------|
| Blocked Hosts | `curlsecurityblockedhosts` | （空） | 允许访问内网 Keycloak |
| Allowed Ports | `curlsecurityallowedport` | `443,80,8080` | 包含 Keycloak 8080 端口 |

### 10.2 调试配置

| 配置项 | 数据库名 | 默认值 | 说明 |
|--------|---------|--------|------|
| Enable Debug Logging | `local_keycloak_sync/enable_debug` | `0` | 设置为 `1` 启用详细日志 |

### 10.3 调试日志位置

- **Moodle 调试日志：** `moodledata/debug.log`
- **Web 服务器错误日志：** `/var/log/apache2/error.log`
- **Keycloak 日志：** `keycloak/standalone/log/`

### 10.4 安全建议

1. **使用 HTTPS** - 生产环境必须使用 HTTPS
2. **定期轮换密钥** - 建议 90 天更换一次 Client Secret
3. **限制 Token 有效期** - 设置合理的 Access Token 过期时间
4. **启用审计日志** - 记录所有认证和授权操作
5. **使用 PKCE** - 增强授权码流程安全性

---

## 11. 故障排除

### 11.1 常见问题

#### 问题 1：redirect_uri_mismatch 错误
**原因：** Keycloak 中配置的重定向 URI 与 Moodle 请求的不匹配
**解决：** 检查 Keycloak Client 配置中的 "Valid Redirect URIs"

#### 问题 2：invalid_client_credentials 错误
**原因：** Client ID 或 Client Secret 不正确
**解决：** 在 Moodle OAuth2 配置中重新输入 Client ID 和 Secret

#### 问题 3：用户字段映射失败
**原因：** Keycloak 中未配置对应的 Protocol Mapper
**解决：** 在 Keycloak 中添加所需的 User Attribute Mappers

#### 问题 4：SLO 不生效
**原因：** 未正确配置 Post Logout Redirect URI
**解决：** 检查 Keycloak Client 的 "Valid Post Logout Redirect URIs"

### 11.2 调试命令

```bash
# 测试 Keycloak 端点可访问性
curl -I http://10.70.5.223:8080/realms/master/.well-known/openid-configuration

# 查看 Moodle OAuth2 配置
mysql -u root -p moodle -e "SELECT * FROM mdl_oauth2_issuer;"

# 查看字段映射
mysql -u root -p moodle -e "SELECT * FROM mdl_oauth2_user_field_mapping;"

# 检查插件配置
mysql -u root -p moodle -e "SELECT * FROM mdl_config WHERE name LIKE '%keycloak%';"
```

---

## 12. 相关文件

### 12.1 核心文件

| 文件 | 路径 | 说明 |
|------|------|------|
| Keycloak Auth 主文件 | `auth/keycloak/auth.php` | 认证插件核心逻辑 |
| Keycloak Auth 设置 | `auth/keycloak/settings.php` | 插件配置页面 |
| Keycloak Sync 库 | `local/keycloak_sync/lib.php` | 同步功能库函数 |
| Keycloak Sync 设置 | `local/keycloak_sync/settings.php` | 同步插件配置 |
| 数据库升级 | `local/keycloak_sync/db/upgrade.php` | 数据库升级脚本 |
| 事件监听 | `local/keycloak_sync/db/events.php` | Moodle 事件监听 |
| 观察者 | `local/keycloak_sync/classes/observer.php` | 事件处理类 |
| 认证钩子 | `local/keycloak_sync/classes/auth_hook.php` | 认证流程钩子 |

### 12.2 辅助脚本

| 文件 | 路径 | 说明 |
|------|------|------|
| SSO 配置脚本 | `configure_moodle_sso.php` | 自动配置 SSO 参数 |
| SLO 修复脚本 | `fix_slo.php` | 修复单点登出问题 |
| Keycloak 修复脚本 | `fix_keycloak.php` | 修复 Keycloak 连接问题 |

### 12.3 语言文件

| 文件 | 路径 | 说明 |
|------|------|------|
| auth_keycloak 英文 | `auth/keycloak/lang/en/auth_keycloak.php` | 认证插件英文语言包 |
| local_keycloak_sync 英文 | `local/keycloak_sync/lang/en/local_keycloak_sync.php` | 同步插件英文语言包 |

### 12.4 权限配置

| 文件 | 路径 | 说明 |
|------|------|------|
| Access 定义 | `auth/keycloak/db/access.php` | 插件权限定义 |
| Version 信息 | `auth/keycloak/version.php` | 插件版本信息 |
| Version 信息 | `local/keycloak_sync/version.php` | 同步插件版本 |

---

## 附录 A：快速检查清单

- [ ] Keycloak 服务可访问 (`http://10.70.5.223:8080`)
- [ ] Client ID 正确配置 (`moodle-realm`)
- [ ] Client Secret 正确填写
- [ ] 重定向 URI 包含回调地址
- [ ] Protocol Mappers 已添加
- [ ] 用户属性映射正确配置
- [ ] Moodle OAuth2 插件已启用
- [ ] cURL 安全设置允许 8080 端口
- [ ] SLO 配置正确（如需要）
- [ ] 调试日志已启用（排查问题时）

---

## 附录 B：数据库配置查询

```sql
-- 查询 OAuth2 服务提供商配置
SELECT id, name, clientid, baseurl FROM mdl_oauth2_issuer;

-- 查询 OAuth2 端点
SELECT name, url FROM mdl_oauth2_endpoint WHERE issuerid = 1;

-- 查询字段映射
SELECT externalfield, internalfield FROM mdl_oauth2_user_field_mapping WHERE issuerid = 1;

-- 查询 Keycloak 相关配置
SELECT name, value FROM mdl_config 
WHERE name LIKE '%keycloak%' OR name LIKE '%oauth2%';
```

---

**文档版本：** 1.0  
**最后更新：** 2024年  
**适用 Moodle 版本：** 4.x  
**适用 Keycloak 版本：** 20.x+
