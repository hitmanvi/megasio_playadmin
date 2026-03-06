# OpenAPI 分片源文件

将大单文件 `openapi.json` 拆分为多个小文件，便于维护和减少编辑错误。

## 目录结构

- **base.json** — 根配置：`openapi`、`info`、`servers`、`components.securitySchemes`
- **paths/*.json** — 按路径前缀分组的接口，每个文件可包含多条 path（如 `agents.json` 含 `/agents`、`/agents/{agent}` 等）
- **schemas.json** — 所有 `components.schemas` 定义

## 工作流

1. **修改接口**：编辑 `paths/` 下对应文件（如改代理相关就改 `paths/agents.json`）
2. **修改 Schema**：编辑 `schemas.json` 或后续再拆成 `schemas/*.json`
3. **合并生成**：在项目根目录执行  
   `php artisan openapi:build`  
   会生成 `resources/swagger/openapi.json`，Swagger UI 使用该文件。

## 命令

| 命令 | 说明 |
|------|------|
| `php artisan openapi:build` | 将 sources 合并为 `openapi.json` |
| `php artisan openapi:build --output=其他路径.json` | 指定输出文件 |
| `php artisan openapi:split [--force]` | 从当前 `openapi.json` 拆回 sources（慎用，会覆盖现有 sources） |

## 新增接口

1. 在 `paths/` 下新建或编辑对应前缀的 JSON 文件（如 `paths/agents.json`）
2. 在对象中增加 path 键，例如：`"/agents": { "get": { ... }, "post": { ... } }`
3. 若用到新 Schema，在 `schemas.json` 中增加定义
4. 执行 `php artisan openapi:build`

## 路径与文件对应

路径按**第一段**分组到同名文件，例如：

- `/agents`、`/agents/{agent}`、`/agents/{agent}/reset-two-factor` → **paths/agents.json**
- `/agent-links`、`/agent-links/{agentLink}` → **paths/agent-links.json**
- `/login`、`/logout`、`/mine`、`/password` → **paths/login.json**、**paths/logout.json** 等（可手动合并为 auth 等）
