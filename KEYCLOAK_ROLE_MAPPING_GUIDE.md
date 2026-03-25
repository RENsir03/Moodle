# Keycloak 角色映射配置指南

## 概述

本文档说明如何在 Keycloak 中配置角色，使其能够正确映射到 Moodle 的用户角色。

## 已完成的开发工作

### 1. Moodle 插件增强

已修改和增强以下文件：

- `/var/www/html/moodle/auth/oauth2/classes/auth.php` - 添加了 Keycloak 数据处理钩子
- `/var/www/html/moodle/local/keycloak_sync/classes/observer.php` - 增强了角色提取逻辑
- `/var/www/html/moodle/local/keycloak_sync/classes/auth_hook.php` - 处理 Keycloak 用户数据
- `/var/www/html/moodle/local/keycloak_sync/settings.php` - 角色映射配置页面

### 2. 支持的角色映射

| Keycloak 角色 | Moodle 角色 | 说明 |
|--------------|-------------|------|
| moodle-admin | manager | 站点管理员 |
| moodle-teacher | editingteacher | 教师（可编辑课程） |
| moodle-student | student | 学生 |

**注意**：角色名称可以在 Moodle 插件设置中自定义。

---

## Keycloak 配置步骤

### 步骤 1：在 Keycloak 中创建角色

1. 登录 Keycloak 管理控制台
2. 选择你的 Realm
3. 点击左侧菜单 **Realm Roles**
4. 点击 **Create Role** 按钮
5. 创建以下角色：
   - `moodle-admin` - 管理员角色
   - `moodle-teacher` - 教师角色
   - `moodle-student` - 学生角色

### 步骤 2：为用户分配角色

1. 点击左侧菜单 **Users**
2. 选择要配置的用户
3. 进入 **Role Mappings** 标签页
4. 在 **Available Roles** 中选择相应的角色
5. 点击 **Add selected** 按钮

### 步骤 3：配置客户端作用域（重要）

为了让 Moodle 能够接收到角色信息，需要配置客户端作用域：

1. 点击左侧菜单 **Client Scopes**
2. 点击 **Create** 按钮
3. 填写信息：
   - Name: `moodle-roles`
   - Protocol: `openid-connect`
   - Display on consent screen: **Off**
4. 点击 **Save**

5. 进入刚创建的 `moodle-roles` 作用域
6. 点击 **Mappers** 标签页
7. 点击 **Configure a new mapper** → **User Realm Role**
8. 配置映射器：
   - Name: `realm roles`
   - Realm Role prefix: （留空）
   - Multivalued: **ON**
   - Token Claim Name: `realm_access.roles`
   - Claim JSON Type: `String`
   - Add to ID token: **ON**
   - Add to access token: **ON**
   - Add to userinfo: **ON**

### 步骤 4：将作用域分配给客户端

1. 点击左侧菜单 **Clients**
2. 选择你的 Moodle 客户端
3. 进入 **Client Scopes** 标签页
4. 在 **Available Client Scopes** 中找到 `moodle-roles`
5. 点击 **Add selected** → 选择 **Default**

### 步骤 5：验证配置

1. 在 Keycloak 中，进入 **Clients** → 你的 Moodle 客户端
2. 点击 **Evaluate** 标签页
3. 选择一个测试用户
4. 点击 **Evaluate** 按钮
5. 查看生成的令牌，确认包含 `realm_access.roles` 声明

预期输出示例：
```json
{
  "realm_access": {
    "roles": [
      "moodle-teacher",
      "offline_access",
      "uma_authorization"
    ]
  }
}
```

---

## Moodle 配置步骤

### 步骤 1：访问插件设置

1. 以管理员身份登录 Moodle
2. 进入 **Site Administration** → **Plugins** → **Local plugins** → **Keycloak Role and Enrollment Sync**

### 步骤 2：配置角色映射

在 **Role Mappings** 部分：

- **Admin Role Claim**: `moodle-admin`（或你在 Keycloak 中定义的管理员角色名）
- **Teacher Role Claim**: `moodle-teacher`（或你在 Keycloak 中定义的教师角色名）
- **Student Role Claim**: `moodle-student`（或你在 Keycloak 中定义的学生角色名）

### 步骤 3：配置课程自动注册（可选）

在 **Course Auto-Enrollment** 部分：

- **Course Enrollment Claim**: `course_enrollments`（Keycloak 中自定义声明的名称）
- **Teacher Course Category**: 教师自动注册的课程类别名称

### 步骤 4：启用调试（开发环境）

在 **Debug Settings** 部分：

- **Enable Debug Logging**: 勾选以启用详细日志记录

调试信息将显示在 Moodle 的调试输出中。

---

## 高级配置

### 使用客户端特定角色

如果你的角色是在客户端级别定义的（而非 Realm 级别），插件也支持：

1. 在 Keycloak 中，进入 **Clients** → 你的客户端 → **Roles**
2. 创建客户端角色（如 `teacher`, `admin`）
3. 为用户分配这些角色
4. 插件会自动从 `resource_access.{client_id}.roles` 中提取

### 使用 Groups 作为角色

如果你的 Keycloak 使用 Groups 而不是 Roles：

1. 在 Keycloak 中创建 Groups（如 `/moodle-teachers`, `/moodle-students`）
2. 将用户添加到相应的 Groups
3. 配置 Groups 映射到 Token 声明
4. 插件会自动处理 `groups` 声明

---

## 故障排除

### 问题 1：角色没有被分配

**检查清单**：
1. 确认用户已分配 Keycloak 角色
2. 检查 Moodle 调试日志（启用调试模式）
3. 验证 Token 中是否包含角色声明
4. 确认 Moodle 插件中的角色名称与 Keycloak 完全匹配

**调试命令**：
```php
// 在 Moodle 中临时添加以下代码到 auth.php 以查看原始数据
debugging('Keycloak raw data: ' . json_encode($rawuserinfo), DEBUG_DEVELOPER);
```

### 问题 2：Token 中没有角色声明

**解决方案**：
1. 检查 Client Scope 是否正确配置
2. 确认 Mapper 的 **Add to userinfo** 选项已启用
3. 验证作用域已添加到客户端的 Default Client Scopes
4. 重新生成令牌（重新登录）

### 问题 3：插件设置不生效

**解决方案**：
1. 清除 Moodle 缓存：**Site Administration** → **Development** → **Purge all caches**
2. 确认插件已启用
3. 检查事件观察者是否注册：`/admin/tool/eventlist/index.php`

---

## 测试步骤

1. **创建测试用户**：
   - 在 Keycloak 中创建用户 `testteacher`
   - 分配角色 `moodle-teacher`

2. **登录 Moodle**：
   - 使用 SSO 登录 Moodle
   - 检查用户是否被分配了教师角色

3. **验证角色分配**：
   - 进入 **Site Administration** → **Users** → **Permissions** → **Assign system roles**
   - 确认用户出现在相应的角色列表中

4. **查看调试信息**：
   - 启用调试模式
   - 查看页面底部的调试输出，确认包含 `local_keycloak_sync:` 开头的日志

---

## 安全注意事项

1. **角色名称**：使用不易猜测的角色名称，避免使用 `admin`, `teacher` 等通用名称
2. **HTTPS**：确保 Keycloak 和 Moodle 都使用 HTTPS
3. **Token 验证**：Moodle OAuth2 插件会自动验证 Token 签名
4. **权限最小化**：只分配用户所需的最小权限

---

## 技术细节

### 数据流

```
用户登录 → Keycloak 认证 → 返回 ID Token → 
Moodle OAuth2 插件解析 → local_keycloak_sync 处理角色 → 
分配 Moodle 角色 → 完成登录
```

### 支持的角色声明格式

插件支持多种角色声明格式：

1. **realm_access.roles**（标准 Keycloak 格式）
2. **resource_access.{client_id}.roles**（客户端特定角色）
3. **roles**（直接数组格式）
4. **groups**（组作为角色）

### 代码钩子

插件通过以下方式集成到 Moodle：

1. **OAuth2 登录钩子**：`auth/oauth2/classes/auth.php` 中的 `process_keycloak_userinfo` 调用
2. **事件观察者**：监听 `user_loggedin` 和 `user_created` 事件
3. **Session 存储**：临时存储 Keycloak 数据在 Session 中

---

## 更新日志

### 版本 1.0.0
- 初始版本
- 支持 Realm 角色映射
- 支持客户端特定角色
- 支持 Groups 映射
- 自动课程注册功能

---

## 支持

如有问题，请检查：
1. Moodle 调试日志
2. Keycloak 事件日志
3. 浏览器开发者工具中的网络请求
