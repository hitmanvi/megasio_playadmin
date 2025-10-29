## 设计思路记录
### tag的多语言
表名	字段	描述	备注
tags	tag_id	PK, 标签的唯一 ID	标签的逻辑实体
        tag_key	标签的系统标识符	例如：TECH_GAMES, SPORTS_NEWS
        created_at	创建时间
	
tag_translations	translation_id	PK, 翻译记录的 ID	
    tag_id	FK, 关联到 tags.tag_id	关键外键
    locale_code	语种代码	例如：en, zh-CN, ja, es
    name	标签的本地化名称	实际展示的文字
    description	标签的本地化描述 (可选)	
    updated_at	更新时间

### 多币种余额
#### 表设计
balances
id 
user_id	BIGINT	
currency	
balance	DECIMAL(20, 8)	当前余额	关键：高精度 DECIMAL
version	INT	乐观锁版本号	用于并发控制
created_at	TIMESTAMP	创建时间	
updated_at	TIMESTAMP	更新时间	

transactions
id	BIGINT	PK	Primary Key
user_id	BIGINT	交易涉及用户	索引
currency	
amount	DECIMAL(20, 8)	交易金额	正数：收入，负数：支出
type	VARCHAR	交易类型	例如：DEPOSIT, WITHDRAWAL, FEE, TRANSFER_IN
status	VARCHAR	交易状态	例如：PENDING, COMPLETED, FAILED, REVERSED
related_entity_id	BIGINT	关联的业务 ID	例如：订单 ID, 充值批次 ID
notes	TEXT	交易附注	
transaction_time	TIMESTAMP	交易发生时间	索引
created_at	TIMESTAMP	创建时间	
updated_at	TIMESTAMP	更新时间	

#### 分析
- 乐观锁

### 提供商接入
- 统一余额变动出入口
- 统一订单出入口
- 提供商回调与主服务器分离

### 出入金统一接口

### 前后端服务器分离

api.play.
api.admin.