# Database Audit Report
Date: 2026-01-18 13:05:20
Environment: local
Database Driver: sqlite
Database Name: /Users/vahapcansari/Desktop/epsilon-crm/database/database.sqlite

## 1. Table Inventory
| Table | Rows |
|---|---|
| `password_reset_tokens` | 0 |
| `sessions` | 59 |
| `cache` | 0 |
| `cache_locks` | 0 |
| `jobs` | 0 |
| `job_batches` | 0 |
| `failed_jobs` | 0 |
| `saved_views` | 0 |
| `vessel_owner_histories` | 0 |
| `categories` | 1 |
| `product_tag` | 0 |
| `tags` | 1 |
| `work_order_items` | 0 |
| `inventory_balances` | 0 |
| `stock_movements` | 53 |
| `stock_transfer_lines` | 0 |
| `stock_transfers` | 0 |
| `quote_items` | 54 |
| `sales_order_shipment_lines` | 29 |
| `sales_order_returns` | 0 |
| `sales_order_return_lines` | 0 |
| `vessel_contacts` | 0 |
| `sales_order_items` | 37 |
| `invoice_lines` | 0 |
| `sales_order_shipments` | 31 |
| `payment_allocations` | 0 |
| `contract_template_versions` | 4 |
| `contract_templates` | 5 |
| `company_profiles` | 2 |
| `activity_logs` | 817 |
| `work_order_photos` | 53 |
| `work_order_updates` | 0 |
| `work_order_progress` | 0 |
| `contract_attachments` | 0 |
| `contract_deliveries` | 0 |
| `users` | 483 |
| `customers` | 202 |
| `vessels` | 212 |
| `work_orders` | 158 |
| `contracts` | 68 |
| `sales_orders` | 179 |
| `invoices` | 88 |
| `payments` | 55 |
| `follow_ups` | 0 |
| `bank_accounts` | 59 |
| `products` | 148 |
| `warehouses` | 145 |
| `ledger_entries` | 36 |
| `quote_sequences` | 121 |
| `sales_order_sequences` | 98 |
| `contract_sequences` | 58 |
| `tenant_user` | 291 |
| `tenant_invitations` | 71 |
| `currencies` | 6 |
| `quotes` | 116 |
| `plans` | 7 |
| `accounts` | 247 |
| `account_users` | 218 |
| `tenants` | 274 |
| `support_sessions` | 42 |
| `audit_logs` | 225 |
| `plan_change_requests` | 1 |

## 2. Tenant Inventory
Found **274** tenants.

### Tenant: Varsayılan Firma (ID: 1)
- Slug: `varsayilan-firma`
- Domain: ``
- Account ID: `1`

**Data Counts:**
- `customers`: 1
- `activity_logs`: 1

### Tenant: Tenant A (ID: 2)
- Slug: `tenant-a`
- Domain: `tenant-a.test`
- Account ID: `2`

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 2

### Tenant: Tenant B (ID: 3)
- Slug: `tenant-b`
- Domain: `tenant-b.test`
- Account ID: `3`

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `bank_accounts`: 1
- `activity_logs`: 2

### Tenant: Tenant C (ID: 4)
- Slug: `tenant-c`
- Domain: `tenant-c.test`
- Account ID: `4`

**Data Counts:**

### Tenant: Tenant Passive (ID: 5)
- Slug: `tenant-passive`
- Domain: `tenant-p.test`
- Account ID: `5`

**Data Counts:**

### Tenant: Tenant D (ID: 6)
- Slug: `tenant-d`
- Domain: `tenant-d.test`
- Account ID: `6`

**Data Counts:**

### Tenant: Tenant Z (ID: 7)
- Slug: `tenant-z`
- Domain: `tenant-z.test`
- Account ID: `7`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 8)
- Slug: `auto-created-tenant`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Tenant C (ID: 10)
- Slug: `tenant-c-2`
- Domain: `tenant-c.test`
- Account ID: `9`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 11)
- Slug: `auto-created-tenant-2`
- Domain: `auto-tenant.test`
- Account ID: `10`

**Data Counts:**

### Tenant: Tenant C (ID: 13)
- Slug: `tenant-c-3`
- Domain: `tenant-c.test`
- Account ID: `11`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 14)
- Slug: `auto-created-tenant-3`
- Domain: `auto-tenant.test`
- Account ID: `12`

**Data Counts:**

### Tenant: Tenant C (ID: 16)
- Slug: `tenant-c-4`
- Domain: `tenant-c.test`
- Account ID: `13`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 17)
- Slug: `auto-created-tenant-4`
- Domain: `auto-tenant.test`
- Account ID: `14`

**Data Counts:**

### Tenant: Tenant C (ID: 19)
- Slug: `tenant-c-5`
- Domain: `tenant-c.test`
- Account ID: `15`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 20)
- Slug: `auto-created-tenant-5`
- Domain: `auto-tenant.test`
- Account ID: `16`

**Data Counts:**

### Tenant: Lockdown Test Tenant (ID: 22)
- Slug: `lockdown-test-tenant`
- Domain: `tenant-a.test`
- Account ID: `17`

**Data Counts:**

### Tenant: Tenant C (ID: 23)
- Slug: `tenant-c-6`
- Domain: `tenant-c.test`
- Account ID: `18`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 24)
- Slug: `auto-created-tenant-6`
- Domain: `auto-tenant.test`
- Account ID: `19`

**Data Counts:**

### Tenant: Tenant C (ID: 26)
- Slug: `tenant-c-7`
- Domain: `tenant-c.test`
- Account ID: `20`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 27)
- Slug: `auto-created-tenant-7`
- Domain: `auto-tenant.test`
- Account ID: `21`

**Data Counts:**

### Tenant: Tenant C (ID: 29)
- Slug: `tenant-c-8`
- Domain: `tenant-c.test`
- Account ID: `22`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 30)
- Slug: `auto-created-tenant-8`
- Domain: `auto-tenant.test`
- Account ID: `23`

**Data Counts:**

### Tenant: Tenant C (ID: 32)
- Slug: `tenant-c-9`
- Domain: `tenant-c.test`
- Account ID: `24`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 33)
- Slug: `auto-created-tenant-9`
- Domain: `auto-tenant.test`
- Account ID: `25`

**Data Counts:**

### Tenant: Privacy Tenant (ID: 35)
- Slug: `privacy-tenant`
- Domain: `privacy.test`
- Account ID: `27`

**Data Counts:**

### Tenant: T1 Starter (ID: 36)
- Slug: `t1-starter`
- Domain: ``
- Account ID: `28`

**Data Counts:**

### Tenant: T1 Pro (ID: 37)
- Slug: `t1-pro`
- Domain: ``
- Account ID: `29`

**Data Counts:**

### Tenant: T2 Pro (ID: 38)
- Slug: `t2-pro`
- Domain: ``
- Account ID: `29`

**Data Counts:**

### Tenant: T3 Pro (ID: 39)
- Slug: `t3-pro`
- Domain: ``
- Account ID: `29`

**Data Counts:**

### Tenant: T4 Pro (ID: 40)
- Slug: `t4-pro`
- Domain: ``
- Account ID: `29`

**Data Counts:**

### Tenant: T1 Starter (ID: 41)
- Slug: `t1-starter-2`
- Domain: ``
- Account ID: `30`

**Data Counts:**

### Tenant: T1 Pro (ID: 42)
- Slug: `t1-pro-2`
- Domain: ``
- Account ID: `31`

**Data Counts:**

### Tenant: T2 Pro (ID: 43)
- Slug: `t2-pro-2`
- Domain: ``
- Account ID: `31`

**Data Counts:**

### Tenant: T3 Pro (ID: 44)
- Slug: `t3-pro-2`
- Domain: ``
- Account ID: `31`

**Data Counts:**

### Tenant: T4 Pro (ID: 45)
- Slug: `t4-pro-2`
- Domain: ``
- Account ID: `31`

**Data Counts:**

### Tenant: Tenant One (ID: 67)
- Slug: `tenant-one`
- Domain: ``
- Account ID: `40`

**Data Counts:**

### Tenant: Pending Tenant (ID: 68)
- Slug: `pending-tenant`
- Domain: ``
- Account ID: `41`

**Data Counts:**

### Tenant: Edge Tenant (ID: 69)
- Slug: `edge-tenant`
- Domain: ``
- Account ID: `42`

**Data Counts:**

### Tenant: Panel Tenant (ID: 70)
- Slug: `panel-tenant`
- Domain: ``
- Account ID: `43`

**Data Counts:**

### Tenant: Billing Tenant (ID: 71)
- Slug: `billing-tenant`
- Domain: ``
- Account ID: `44`

**Data Counts:**

### Tenant: Tenant One (ID: 72)
- Slug: `tenant-one-2`
- Domain: ``
- Account ID: `45`

**Data Counts:**

### Tenant: Billing Tenant (ID: 73)
- Slug: `billing-tenant-2`
- Domain: ``
- Account ID: `46`

**Data Counts:**

### Tenant:  Downgrade Test (ID: 74)
- Slug: `downgrade-test`
- Domain: ``
- Account ID: `52`

**Data Counts:**

### Tenant: Panel Tenant (ID: 75)
- Slug: `panel-tenant-2`
- Domain: ``
- Account ID: `53`

**Data Counts:**

### Tenant: Panel Tenant (ID: 76)
- Slug: `panel-tenant-3`
- Domain: ``
- Account ID: `54`

**Data Counts:**

### Tenant: Panel Tenant (ID: 77)
- Slug: `panel-tenant-4`
- Domain: ``
- Account ID: `55`

**Data Counts:**

### Tenant:  Downgrade Test (ID: 78)
- Slug: `downgrade-test-2`
- Domain: ``
- Account ID: `56`

**Data Counts:**

### Tenant:  Downgrade Test (ID: 79)
- Slug: `downgrade-test-3`
- Domain: ``
- Account ID: `69`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 81)
- Slug: `auto-created-tenant-10`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Tenant One (ID: 82)
- Slug: `tenant-one-3`
- Domain: ``
- Account ID: `70`

**Data Counts:**

### Tenant:  Downgrade Test (ID: 83)
- Slug: `downgrade-test-4`
- Domain: ``
- Account ID: `71`

**Data Counts:**

### Tenant: Agent Test Tenant (ID: 84)
- Slug: `agent-test-tenant`
- Domain: `agent-test.localhost`
- Account ID: `83`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 86)
- Slug: `auto-created-tenant-11`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 88)
- Slug: `auto-created-tenant-12`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Tenant One (ID: 89)
- Slug: `tenant-one-4`
- Domain: ``
- Account ID: `72`

**Data Counts:**

### Tenant: Billing Tenant (ID: 90)
- Slug: `billing-tenant-3`
- Domain: ``
- Account ID: `73`

**Data Counts:**

### Tenant:  Downgrade Test (ID: 91)
- Slug: `downgrade-test-5`
- Domain: ``
- Account ID: `74`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 93)
- Slug: `auto-created-tenant-13`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Tenant One (ID: 94)
- Slug: `tenant-one-5`
- Domain: ``
- Account ID: `75`

**Data Counts:**

### Tenant: Billing Tenant (ID: 95)
- Slug: `billing-tenant-4`
- Domain: ``
- Account ID: `76`

**Data Counts:**

### Tenant:  Downgrade Test (ID: 96)
- Slug: `downgrade-test-6`
- Domain: ``
- Account ID: `77`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 98)
- Slug: `auto-created-tenant-14`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Tenant One (ID: 99)
- Slug: `tenant-one-6`
- Domain: ``
- Account ID: `78`

**Data Counts:**

### Tenant: Billing Tenant (ID: 100)
- Slug: `billing-tenant-5`
- Domain: ``
- Account ID: `79`

**Data Counts:**

### Tenant:  Downgrade Test (ID: 101)
- Slug: `downgrade-test-7`
- Domain: ``
- Account ID: `80`

**Data Counts:**

### Tenant: Verify Tenant 1 (ID: 102)
- Slug: `verify-tenant-1`
- Domain: `verify1.test`
- Account ID: `84`

**Data Counts:**

### Tenant: Verify Tenant 2 (ID: 103)
- Slug: `verify-tenant-2`
- Domain: `verify2.test`
- Account ID: `85`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 105)
- Slug: `auto-created-tenant-15`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 107)
- Slug: `auto-created-tenant-16`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 109)
- Slug: `auto-created-tenant-17`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 111)
- Slug: `auto-created-tenant-18`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 115)
- Slug: `auto-created-tenant-19`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: Auto Created Tenant (ID: 119)
- Slug: `auto-created-tenant-20`
- Domain: `auto-tenant.test`
- Account ID: `8`

**Data Counts:**

### Tenant: T6b1 A (ID: 144)
- Slug: `t6b1-a`
- Domain: `t6b1a-696c8be157f12.test`
- Account ID: `86`

**Data Counts:**

### Tenant: T6b1 B (ID: 145)
- Slug: `t6b1-b`
- Domain: `t6b1b-696c8be157f12.test`
- Account ID: `86`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 150)
- Slug: `agg-t6b2-a`
- Domain: `agg-a-696c8cf0d29c7.test`
- Account ID: `87`

**Data Counts:**
- `customers`: 1
- `activity_logs`: 1

### Tenant: Agg T6b2 B (ID: 151)
- Slug: `agg-t6b2-b`
- Domain: `agg-b-696c8cf0d29c7.test`
- Account ID: `88`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 152)
- Slug: `agg-t6b2-a-2`
- Domain: `agg-a-696c8cfcc66ed.test`
- Account ID: `89`

**Data Counts:**
- `customers`: 1
- `activity_logs`: 1

### Tenant: Agg T6b2 B (ID: 153)
- Slug: `agg-t6b2-b-2`
- Domain: `agg-b-696c8cfcc66ed.test`
- Account ID: `90`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 154)
- Slug: `agg-t6b2-a-3`
- Domain: `agg-a-696c8d074a28a.test`
- Account ID: `91`

**Data Counts:**
- `customers`: 1
- `activity_logs`: 1

### Tenant: Agg T6b2 B (ID: 155)
- Slug: `agg-t6b2-b-3`
- Domain: `agg-b-696c8d074a28a.test`
- Account ID: `92`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 156)
- Slug: `agg-t6b2-a-4`
- Domain: `agg-a-696c8d133209d.test`
- Account ID: `93`

**Data Counts:**
- `customers`: 1
- `activity_logs`: 1

### Tenant: Agg T6b2 B (ID: 157)
- Slug: `agg-t6b2-b-4`
- Domain: `agg-b-696c8d133209d.test`
- Account ID: `94`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 158)
- Slug: `agg-t6b2-a-5`
- Domain: `agg-a-696c8d20c22d0.test`
- Account ID: `95`

**Data Counts:**
- `customers`: 1
- `vessels`: 1
- `activity_logs`: 2

### Tenant: Agg T6b2 B (ID: 159)
- Slug: `agg-t6b2-b-5`
- Domain: `agg-b-696c8d20c22d0.test`
- Account ID: `96`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 160)
- Slug: `agg-t6b2-a-6`
- Domain: `agg-a-696c8d2a32512.test`
- Account ID: `97`

**Data Counts:**
- `customers`: 1
- `vessels`: 1
- `activity_logs`: 2

### Tenant: Agg T6b2 B (ID: 161)
- Slug: `agg-t6b2-b-6`
- Domain: `agg-b-696c8d2a32512.test`
- Account ID: `98`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 162)
- Slug: `agg-t6b2-a-7`
- Domain: `agg-a-696c8d351e65a.test`
- Account ID: `99`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 163)
- Slug: `agg-t6b2-b-7`
- Domain: `agg-b-696c8d351e65a.test`
- Account ID: `100`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 164)
- Slug: `agg-t6b2-a-8`
- Domain: `agg-a-696c8d400537d.test`
- Account ID: `101`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 165)
- Slug: `agg-t6b2-b-8`
- Domain: `agg-b-696c8d400537d.test`
- Account ID: `102`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 166)
- Slug: `agg-t6b2-a-9`
- Domain: `agg-a-696c8d4f0d6c8.test`
- Account ID: `103`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 167)
- Slug: `agg-t6b2-b-9`
- Domain: `agg-b-696c8d4f0d6c8.test`
- Account ID: `104`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 168)
- Slug: `agg-t6b2-a-10`
- Domain: `agg-a-696c8d599d9c4.test`
- Account ID: `105`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 169)
- Slug: `agg-t6b2-b-10`
- Domain: `agg-b-696c8d599d9c4.test`
- Account ID: `106`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 170)
- Slug: `agg-t6b2-a-11`
- Domain: `agg-a-696c8d66bb991.test`
- Account ID: `107`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 171)
- Slug: `agg-t6b2-b-11`
- Domain: `agg-b-696c8d66bb991.test`
- Account ID: `108`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 172)
- Slug: `agg-t6b2-a-12`
- Domain: `agg-a-696c8d80db3a9.test`
- Account ID: `109`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 173)
- Slug: `agg-t6b2-b-12`
- Domain: `agg-b-696c8d80db3a9.test`
- Account ID: `110`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 174)
- Slug: `agg-t6b2-a-13`
- Domain: `agg-a-696c8d88d0add.test`
- Account ID: `111`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 175)
- Slug: `agg-t6b2-b-13`
- Domain: `agg-b-696c8d88d0add.test`
- Account ID: `112`

**Data Counts:**

### Tenant: T6b1 A (ID: 176)
- Slug: `t6b1-a-2`
- Domain: `t6b1a-696c8d892b4a9.test`
- Account ID: `113`

**Data Counts:**

### Tenant: T6b1 B (ID: 177)
- Slug: `t6b1-b-2`
- Domain: `t6b1b-696c8d892b4a9.test`
- Account ID: `113`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 180)
- Slug: `agg-t6b2-a-14`
- Domain: `agg-a-696c8e8bb1c44.test`
- Account ID: `114`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 181)
- Slug: `agg-t6b2-b-14`
- Domain: `agg-b-696c8e8bb1c44.test`
- Account ID: `115`

**Data Counts:**

### Tenant: T6b1 A (ID: 182)
- Slug: `t6b1-a-3`
- Domain: `t6b1a-696c8e8c15dc5.test`
- Account ID: `116`

**Data Counts:**

### Tenant: T6b1 B (ID: 183)
- Slug: `t6b1-b-3`
- Domain: `t6b1b-696c8e8c15dc5.test`
- Account ID: `116`

**Data Counts:**

### Tenant: T6b1 A (ID: 190)
- Slug: `t6b1-a-4`
- Domain: `t6b1a-696c8f01ce4d4.test`
- Account ID: `117`

**Data Counts:**

### Tenant: T6b1 B (ID: 191)
- Slug: `t6b1-b-4`
- Domain: `t6b1b-696c8f01ce4d4.test`
- Account ID: `117`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 192)
- Slug: `agg-t6b2-a-15`
- Domain: `agg-a-696c8f0221c0e.test`
- Account ID: `118`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 193)
- Slug: `agg-t6b2-b-15`
- Domain: `agg-b-696c8f0221c0e.test`
- Account ID: `119`

**Data Counts:**

### Tenant: T6b1 A (ID: 198)
- Slug: `t6b1-a-5`
- Domain: `t6b1a-696c900d1a388.test`
- Account ID: `120`

**Data Counts:**

### Tenant: T6b1 B (ID: 199)
- Slug: `t6b1-b-5`
- Domain: `t6b1b-696c900d1a388.test`
- Account ID: `120`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 200)
- Slug: `agg-t6b2-a-16`
- Domain: `agg-a-696c900d606aa.test`
- Account ID: `121`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 201)
- Slug: `agg-t6b2-b-16`
- Domain: `agg-b-696c900d606aa.test`
- Account ID: `122`

**Data Counts:**

### Tenant: T6b1 A (ID: 206)
- Slug: `t6b1-a-6`
- Domain: `t6b1a-696c938461af4.test`
- Account ID: `123`

**Data Counts:**

### Tenant: T6b1 B (ID: 207)
- Slug: `t6b1-b-6`
- Domain: `t6b1b-696c938461af4.test`
- Account ID: `123`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 208)
- Slug: `agg-t6b2-a-17`
- Domain: `agg-a-696c9384a7119.test`
- Account ID: `124`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 209)
- Slug: `agg-t6b2-b-17`
- Domain: `agg-b-696c9384a7119.test`
- Account ID: `125`

**Data Counts:**

### Tenant: Verify Plan T1 (ID: 211)
- Slug: `verify-plan-t1-2`
- Domain: ``
- Account ID: `82`

**Data Counts:**

### Tenant: T6b1 A (ID: 216)
- Slug: `t6b1-a-7`
- Domain: `t6b1a-696c94304ad54.test`
- Account ID: `126`

**Data Counts:**

### Tenant: T6b1 B (ID: 217)
- Slug: `t6b1-b-7`
- Domain: `t6b1b-696c94304ad54.test`
- Account ID: `126`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 218)
- Slug: `agg-t6b2-a-18`
- Domain: `agg-a-696c943092564.test`
- Account ID: `127`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 219)
- Slug: `agg-t6b2-b-18`
- Domain: `agg-b-696c943092564.test`
- Account ID: `128`

**Data Counts:**

### Tenant: T6b1 A (ID: 224)
- Slug: `t6b1-a-8`
- Domain: `t6b1a-696c945f9266c.test`
- Account ID: `129`

**Data Counts:**

### Tenant: T6b1 B (ID: 225)
- Slug: `t6b1-b-8`
- Domain: `t6b1b-696c945f9266c.test`
- Account ID: `129`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 226)
- Slug: `agg-t6b2-a-19`
- Domain: `agg-a-696c945fd6b91.test`
- Account ID: `130`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 227)
- Slug: `agg-t6b2-b-19`
- Domain: `agg-b-696c945fd6b91.test`
- Account ID: `131`

**Data Counts:**

### Tenant: T6b1 A (ID: 232)
- Slug: `t6b1-a-9`
- Domain: `t6b1a-696c95699f2ec.test`
- Account ID: `132`

**Data Counts:**

### Tenant: T6b1 B (ID: 233)
- Slug: `t6b1-b-9`
- Domain: `t6b1b-696c95699f2ec.test`
- Account ID: `132`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 234)
- Slug: `agg-t6b2-a-20`
- Domain: `agg-a-696c9569e5297.test`
- Account ID: `133`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 235)
- Slug: `agg-t6b2-b-20`
- Domain: `agg-b-696c9569e5297.test`
- Account ID: `134`

**Data Counts:**

### Tenant: T6b1 A (ID: 240)
- Slug: `t6b1-a-10`
- Domain: `t6b1a-696c95afc7737.test`
- Account ID: `135`

**Data Counts:**

### Tenant: T6b1 B (ID: 241)
- Slug: `t6b1-b-10`
- Domain: `t6b1b-696c95afc7737.test`
- Account ID: `135`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 242)
- Slug: `agg-t6b2-a-21`
- Domain: `agg-a-696c95b018ee3.test`
- Account ID: `136`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 243)
- Slug: `agg-t6b2-b-21`
- Domain: `agg-b-696c95b018ee3.test`
- Account ID: `137`

**Data Counts:**

### Tenant: T6b1 A (ID: 248)
- Slug: `t6b1-a-11`
- Domain: `t6b1a-696c96dfc3a40.test`
- Account ID: `138`

**Data Counts:**

### Tenant: T6b1 B (ID: 249)
- Slug: `t6b1-b-11`
- Domain: `t6b1b-696c96dfc3a40.test`
- Account ID: `138`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 250)
- Slug: `agg-t6b2-a-22`
- Domain: `agg-a-696c96e01484f.test`
- Account ID: `139`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 251)
- Slug: `agg-t6b2-b-22`
- Domain: `agg-b-696c96e01484f.test`
- Account ID: `140`

**Data Counts:**

### Tenant: T6b1 A (ID: 256)
- Slug: `t6b1-a-12`
- Domain: `t6b1a-696c96f84910e.test`
- Account ID: `141`

**Data Counts:**

### Tenant: T6b1 B (ID: 257)
- Slug: `t6b1-b-12`
- Domain: `t6b1b-696c96f84910e.test`
- Account ID: `141`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 258)
- Slug: `agg-t6b2-a-23`
- Domain: `agg-a-696c96f88ef4c.test`
- Account ID: `142`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 259)
- Slug: `agg-t6b2-b-23`
- Domain: `agg-b-696c96f88ef4c.test`
- Account ID: `143`

**Data Counts:**

### Tenant: T6b1 A (ID: 264)
- Slug: `t6b1-a-13`
- Domain: `t6b1a-696c974af1f77.test`
- Account ID: `144`

**Data Counts:**

### Tenant: T6b1 B (ID: 265)
- Slug: `t6b1-b-13`
- Domain: `t6b1b-696c974af1f77.test`
- Account ID: `144`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 266)
- Slug: `agg-t6b2-a-24`
- Domain: `agg-a-696c974b47a21.test`
- Account ID: `145`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 267)
- Slug: `agg-t6b2-b-24`
- Domain: `agg-b-696c974b47a21.test`
- Account ID: `146`

**Data Counts:**

### Tenant: T6b1 A (ID: 272)
- Slug: `t6b1-a-14`
- Domain: `t6b1a-696c991965068.test`
- Account ID: `147`

**Data Counts:**

### Tenant: T6b1 B (ID: 273)
- Slug: `t6b1-b-14`
- Domain: `t6b1b-696c991965068.test`
- Account ID: `147`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 274)
- Slug: `agg-t6b2-a-25`
- Domain: `agg-a-696c9919aab68.test`
- Account ID: `148`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 275)
- Slug: `agg-t6b2-b-25`
- Domain: `agg-b-696c9919aab68.test`
- Account ID: `149`

**Data Counts:**

### Tenant: T6b1 A (ID: 280)
- Slug: `t6b1-a-15`
- Domain: `t6b1a-696c99daa0f08.test`
- Account ID: `150`

**Data Counts:**

### Tenant: T6b1 B (ID: 281)
- Slug: `t6b1-b-15`
- Domain: `t6b1b-696c99daa0f08.test`
- Account ID: `150`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 282)
- Slug: `agg-t6b2-a-26`
- Domain: `agg-a-696c99dae76d8.test`
- Account ID: `151`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 283)
- Slug: `agg-t6b2-b-26`
- Domain: `agg-b-696c99dae76d8.test`
- Account ID: `152`

**Data Counts:**

### Tenant: T6b1 A (ID: 288)
- Slug: `t6b1-a-16`
- Domain: `t6b1a-696c9d1ef3c0c.test`
- Account ID: `153`

**Data Counts:**

### Tenant: T6b1 B (ID: 289)
- Slug: `t6b1-b-16`
- Domain: `t6b1b-696c9d1ef3c0c.test`
- Account ID: `153`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 290)
- Slug: `agg-t6b2-a-27`
- Domain: `agg-a-696c9d1f458b6.test`
- Account ID: `154`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 291)
- Slug: `agg-t6b2-b-27`
- Domain: `agg-b-696c9d1f458b6.test`
- Account ID: `155`

**Data Counts:**

### Tenant: T6b1 A (ID: 296)
- Slug: `t6b1-a-17`
- Domain: `t6b1a-696c9e007f6f0.test`
- Account ID: `156`

**Data Counts:**

### Tenant: T6b1 B (ID: 297)
- Slug: `t6b1-b-17`
- Domain: `t6b1b-696c9e007f6f0.test`
- Account ID: `156`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 298)
- Slug: `agg-t6b2-a-28`
- Domain: `agg-a-696c9e00c59ef.test`
- Account ID: `157`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 299)
- Slug: `agg-t6b2-b-28`
- Domain: `agg-b-696c9e00c59ef.test`
- Account ID: `158`

**Data Counts:**

### Tenant: T6b1 A (ID: 304)
- Slug: `t6b1-a-18`
- Domain: `t6b1a-696c9f7ed5182.test`
- Account ID: `159`

**Data Counts:**

### Tenant: T6b1 B (ID: 305)
- Slug: `t6b1-b-18`
- Domain: `t6b1b-696c9f7ed5182.test`
- Account ID: `159`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 306)
- Slug: `agg-t6b2-a-29`
- Domain: `agg-a-696c9f7f27de6.test`
- Account ID: `160`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 307)
- Slug: `agg-t6b2-b-29`
- Domain: `agg-b-696c9f7f27de6.test`
- Account ID: `161`

**Data Counts:**

### Tenant: T6b1 A (ID: 312)
- Slug: `t6b1-a-19`
- Domain: `t6b1a-696ca24ae0c95.test`
- Account ID: `162`

**Data Counts:**

### Tenant: T6b1 B (ID: 313)
- Slug: `t6b1-b-19`
- Domain: `t6b1b-696ca24ae0c95.test`
- Account ID: `162`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 314)
- Slug: `agg-t6b2-a-30`
- Domain: `agg-a-696ca24b36e50.test`
- Account ID: `163`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 315)
- Slug: `agg-t6b2-b-30`
- Domain: `agg-b-696ca24b36e50.test`
- Account ID: `164`

**Data Counts:**

### Tenant: T6b1 A (ID: 320)
- Slug: `t6b1-a-20`
- Domain: `t6b1a-696ca2744b357.test`
- Account ID: `165`

**Data Counts:**

### Tenant: T6b1 B (ID: 321)
- Slug: `t6b1-b-20`
- Domain: `t6b1b-696ca2744b357.test`
- Account ID: `165`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 322)
- Slug: `agg-t6b2-a-31`
- Domain: `agg-a-696ca27498320.test`
- Account ID: `166`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 323)
- Slug: `agg-t6b2-b-31`
- Domain: `agg-b-696ca27498320.test`
- Account ID: `167`

**Data Counts:**

### Tenant: T6b1 A (ID: 328)
- Slug: `t6b1-a-21`
- Domain: `t6b1a-696ca28c1bb3f.test`
- Account ID: `168`

**Data Counts:**

### Tenant: T6b1 B (ID: 329)
- Slug: `t6b1-b-21`
- Domain: `t6b1b-696ca28c1bb3f.test`
- Account ID: `168`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 330)
- Slug: `agg-t6b2-a-32`
- Domain: `agg-a-696ca28c632c9.test`
- Account ID: `169`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 331)
- Slug: `agg-t6b2-b-32`
- Domain: `agg-b-696ca28c632c9.test`
- Account ID: `170`

**Data Counts:**

### Tenant: T6b1 A (ID: 336)
- Slug: `t6b1-a-22`
- Domain: `t6b1a-696ca68ce1145.test`
- Account ID: `171`

**Data Counts:**

### Tenant: T6b1 B (ID: 337)
- Slug: `t6b1-b-22`
- Domain: `t6b1b-696ca68ce1145.test`
- Account ID: `171`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 338)
- Slug: `agg-t6b2-a-33`
- Domain: `agg-a-696ca68d3854a.test`
- Account ID: `172`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 339)
- Slug: `agg-t6b2-b-33`
- Domain: `agg-b-696ca68d3854a.test`
- Account ID: `173`

**Data Counts:**

### Tenant: T6b1 A (ID: 344)
- Slug: `t6b1-a-23`
- Domain: `t6b1a-696ca7580bb81.test`
- Account ID: `174`

**Data Counts:**

### Tenant: T6b1 B (ID: 345)
- Slug: `t6b1-b-23`
- Domain: `t6b1b-696ca7580bb81.test`
- Account ID: `174`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 346)
- Slug: `agg-t6b2-a-34`
- Domain: `agg-a-696ca75853b93.test`
- Account ID: `175`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 347)
- Slug: `agg-t6b2-b-34`
- Domain: `agg-b-696ca75853b93.test`
- Account ID: `176`

**Data Counts:**

### Tenant: T6b1 A (ID: 352)
- Slug: `t6b1-a-24`
- Domain: `t6b1a-696ca91a52308.test`
- Account ID: `177`

**Data Counts:**

### Tenant: T6b1 B (ID: 353)
- Slug: `t6b1-b-24`
- Domain: `t6b1b-696ca91a52308.test`
- Account ID: `177`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 354)
- Slug: `agg-t6b2-a-35`
- Domain: `agg-a-696ca91a98e7c.test`
- Account ID: `178`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 355)
- Slug: `agg-t6b2-b-35`
- Domain: `agg-b-696ca91a98e7c.test`
- Account ID: `179`

**Data Counts:**

### Tenant: T6b1 A (ID: 360)
- Slug: `t6b1-a-25`
- Domain: `t6b1a-696ca994c527a.test`
- Account ID: `180`

**Data Counts:**

### Tenant: T6b1 B (ID: 361)
- Slug: `t6b1-b-25`
- Domain: `t6b1b-696ca994c527a.test`
- Account ID: `180`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 362)
- Slug: `agg-t6b2-a-36`
- Domain: `agg-a-696ca995179ba.test`
- Account ID: `181`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 363)
- Slug: `agg-t6b2-b-36`
- Domain: `agg-b-696ca995179ba.test`
- Account ID: `182`

**Data Counts:**

### Tenant: T6b1 A (ID: 368)
- Slug: `t6b1-a-26`
- Domain: `t6b1a-696ca9bac86d6.test`
- Account ID: `183`

**Data Counts:**

### Tenant: T6b1 B (ID: 369)
- Slug: `t6b1-b-26`
- Domain: `t6b1b-696ca9bac86d6.test`
- Account ID: `183`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 370)
- Slug: `agg-t6b2-a-37`
- Domain: `agg-a-696ca9bb1a321.test`
- Account ID: `184`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 371)
- Slug: `agg-t6b2-b-37`
- Domain: `agg-b-696ca9bb1a321.test`
- Account ID: `185`

**Data Counts:**

### Tenant: T6b1 A (ID: 377)
- Slug: `t6b1-a-27`
- Domain: `t6b1a-696cabc11f9ce.test`
- Account ID: `187`

**Data Counts:**

### Tenant: T6b1 B (ID: 378)
- Slug: `t6b1-b-27`
- Domain: `t6b1b-696cabc11f9ce.test`
- Account ID: `187`

**Data Counts:**

### Tenant: Agg T6b2 A (ID: 379)
- Slug: `agg-t6b2-a-38`
- Domain: `agg-a-696cabc1664c9.test`
- Account ID: `188`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `invoices`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Agg T6b2 B (ID: 380)
- Slug: `agg-t6b2-b-38`
- Domain: `agg-b-696cabc1664c9.test`
- Account ID: `189`

**Data Counts:**

### Tenant: Upgrade Tenant 696caf6903af4 (ID: 393)
- Slug: `upgrade-tenant-696caf6903af4`
- Domain: `upgrade-696caf6903af4.test`
- Account ID: `194`

**Data Counts:**

### Tenant: delivery Tenant (ID: 441)
- Slug: `delivery-tenant`
- Domain: `delivery-696cbd7d2834e.test`
- Account ID: `208`

**Data Counts:**
- `customers`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: other Tenant (ID: 442)
- Slug: `other-tenant`
- Domain: `other-696cbd7d29ced.test`
- Account ID: `209`

**Data Counts:**
- `customers`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: delivery Tenant (ID: 447)
- Slug: `delivery-tenant-2`
- Domain: `delivery-696cbdab6647d.test`
- Account ID: `210`

**Data Counts:**
- `customers`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: other Tenant (ID: 448)
- Slug: `other-tenant-2`
- Domain: `other-696cbdab67c4d.test`
- Account ID: `211`

**Data Counts:**
- `customers`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: delivery Tenant (ID: 457)
- Slug: `delivery-tenant-3`
- Domain: `delivery-696cbdc8ab03e.test`
- Account ID: `214`

**Data Counts:**
- `customers`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: other Tenant (ID: 458)
- Slug: `other-tenant-3`
- Domain: `other-696cbdc8ac776.test`
- Account ID: `215`

**Data Counts:**
- `customers`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: delivery Tenant (ID: 467)
- Slug: `delivery-tenant-4`
- Domain: `delivery-696cbf1259c0b.test`
- Account ID: `218`

**Data Counts:**
- `customers`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: other Tenant (ID: 468)
- Slug: `other-tenant-4`
- Domain: `other-696cbf125d243.test`
- Account ID: `219`

**Data Counts:**
- `customers`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: tenantA Tenant (ID: 475)
- Slug: `tenanta-tenant`
- Domain: `tenantA-696cc030a90e2.test`
- Account ID: `222`

**Data Counts:**
- `customers`: 1

### Tenant: tenantB Tenant (ID: 476)
- Slug: `tenantb-tenant`
- Domain: `tenantB-696cc030ab933.test`
- Account ID: `223`

**Data Counts:**
- `customers`: 1

### Tenant: tenantA Tenant (ID: 485)
- Slug: `tenanta-tenant-2`
- Domain: `tenantA-696cc04c35f68.test`
- Account ID: `226`

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `vessels`: 1

### Tenant: tenantB Tenant (ID: 486)
- Slug: `tenantb-tenant-2`
- Domain: `tenantB-696cc04c37c63.test`
- Account ID: `227`

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `vessels`: 1

### Tenant: tenantA Tenant (ID: 487)
- Slug: `tenanta-tenant-3`
- Domain: `tenantA-696cc07b9602f.test`
- Account ID: `228`

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `vessels`: 1

### Tenant: tenantB Tenant (ID: 488)
- Slug: `tenantb-tenant-3`
- Domain: `tenantB-696cc07b9903e.test`
- Account ID: `229`

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `vessels`: 1

### Tenant: tenantA Tenant (ID: 497)
- Slug: `tenanta-tenant-4`
- Domain: `tenantA-696cc29f28891.test`
- Account ID: `232`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantB Tenant (ID: 498)
- Slug: `tenantb-tenant-4`
- Domain: `tenantB-696cc29f2bc4e.test`
- Account ID: `233`

**Data Counts:**
- `customers`: 1
- `vessels`: 1

### Tenant: tenantA Tenant (ID: 507)
- Slug: `tenanta-tenant-5`
- Domain: `tenantA-696cc2b570678.test`
- Account ID: `236`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantB Tenant (ID: 508)
- Slug: `tenantb-tenant-5`
- Domain: `tenantB-696cc2b571ff3.test`
- Account ID: `237`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantA Tenant (ID: 509)
- Slug: `tenanta-tenant-6`
- Domain: `tenantA-696cc2cc73f44.test`
- Account ID: `238`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantB Tenant (ID: 510)
- Slug: `tenantb-tenant-6`
- Domain: `tenantB-696cc2cc75854.test`
- Account ID: `239`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantA Tenant (ID: 511)
- Slug: `tenanta-tenant-7`
- Domain: `tenantA-696cc2dca84b8.test`
- Account ID: `240`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantB Tenant (ID: 512)
- Slug: `tenantb-tenant-7`
- Domain: `tenantB-696cc2dca9d48.test`
- Account ID: `241`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantA Tenant (ID: 513)
- Slug: `tenanta-tenant-8`
- Domain: `tenantA-696cc3123ca11.test`
- Account ID: `242`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantB Tenant (ID: 514)
- Slug: `tenantb-tenant-8`
- Domain: `tenantB-696cc3123e385.test`
- Account ID: `243`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: tenantA Tenant (ID: 523)
- Slug: `tenanta-tenant-9`
- Domain: `tenantA-696cc4a968c19.test`
- Account ID: `246`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1
- `activity_logs`: 1

### Tenant: tenantB Tenant (ID: 524)
- Slug: `tenantb-tenant-9`
- Domain: `tenantB-696cc4a96b630.test`
- Account ID: `247`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: Support Test Tenant (ID: 525)
- Slug: `support-test-tenant`
- Domain: `support-test.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant A 696cc4ac80d5e (ID: 526)
- Slug: `tenant-a-696cc4ac80d5e`
- Domain: `tenant-a-696cc4ac80d5e.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `payments`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 5

### Tenant: Tenant B 696cc4ac80d5e (ID: 527)
- Slug: `tenant-b-696cc4ac80d5e`
- Domain: `tenant-b-696cc4ac80d5e.test`
- Account ID: ``

**Data Counts:**

### Tenant: Privacy Test Tenant (ID: 528)
- Slug: `privacy-test-tenant`
- Domain: `privacy-test.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant 6A A (ID: 529)
- Slug: `tenant-6a-a`
- Domain: `t6a-a-696cc4ad18e32.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `work_orders`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Tenant 6A B (ID: 530)
- Slug: `tenant-6a-b`
- Domain: `t6a-b-696cc4ad18e32.test`
- Account ID: ``

**Data Counts:**

### Tenant: tenantA Tenant (ID: 533)
- Slug: `tenanta-tenant-10`
- Domain: `tenantA-696cc5694c1a1.test`
- Account ID: `250`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1
- `activity_logs`: 1

### Tenant: tenantB Tenant (ID: 534)
- Slug: `tenantb-tenant-10`
- Domain: `tenantB-696cc5694efe3.test`
- Account ID: `251`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: Tenant A 696cc5702b906 (ID: 535)
- Slug: `tenant-a-696cc5702b906`
- Domain: `tenant-a-696cc5702b906.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `payments`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 5

### Tenant: Tenant B 696cc5702b906 (ID: 536)
- Slug: `tenant-b-696cc5702b906`
- Domain: `tenant-b-696cc5702b906.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant 6A A (ID: 537)
- Slug: `tenant-6a-a-2`
- Domain: `t6a-a-696cc570b6e8e.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `work_orders`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Tenant 6A B (ID: 538)
- Slug: `tenant-6a-b-2`
- Domain: `t6a-b-696cc570b6e8e.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant A (ID: 541)
- Slug: `tenant-a-2`
- Domain: `tenantA.test`
- Account ID: `254`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 55
- `work_orders`: 40
- `vessels`: 16
- `activity_logs`: 75

### Tenant: Tenant B (ID: 542)
- Slug: `tenant-b-2`
- Domain: `tenantB.test`
- Account ID: `254`

**Data Counts:**
- `customers`: 6
- `sales_orders`: 13
- `vessels`: 13

### Tenant: Tenant A 696ccbf63d344 (ID: 543)
- Slug: `tenant-a-696ccbf63d344`
- Domain: `tenant-a-696ccbf63d344.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `payments`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 5

### Tenant: Tenant B 696ccbf63d344 (ID: 544)
- Slug: `tenant-b-696ccbf63d344`
- Domain: `tenant-b-696ccbf63d344.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant 6A A (ID: 545)
- Slug: `tenant-6a-a-3`
- Domain: `t6a-a-696ccbf6cb867.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `work_orders`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Tenant 6A B (ID: 546)
- Slug: `tenant-6a-b-3`
- Domain: `t6a-b-696ccbf6cb867.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant A 696ccc673950a (ID: 547)
- Slug: `tenant-a-696ccc673950a`
- Domain: `tenant-a-696ccc673950a.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `payments`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 5

### Tenant: Tenant B 696ccc673950a (ID: 548)
- Slug: `tenant-b-696ccc673950a`
- Domain: `tenant-b-696ccc673950a.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant 6A A (ID: 549)
- Slug: `tenant-6a-a-4`
- Domain: `t6a-a-696ccc67c8c17.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `work_orders`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Tenant 6A B (ID: 550)
- Slug: `tenant-6a-b-4`
- Domain: `t6a-b-696ccc67c8c17.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant A 696cce0df0ae3 (ID: 553)
- Slug: `tenant-a-696cce0df0ae3`
- Domain: `tenant-a-696cce0df0ae3.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `payments`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 5

### Tenant: Tenant B 696cce0df0ae3 (ID: 554)
- Slug: `tenant-b-696cce0df0ae3`
- Domain: `tenant-b-696cce0df0ae3.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant 6A A (ID: 555)
- Slug: `tenant-6a-a-5`
- Domain: `t6a-a-696cce0e8cbec.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `work_orders`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Tenant 6A B (ID: 556)
- Slug: `tenant-6a-b-5`
- Domain: `t6a-b-696cce0e8cbec.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant Ops A (ID: 559)
- Slug: `tenant-ops-a`
- Domain: `opsA.test`
- Account ID: `261`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `vessels`: 1

### Tenant: Tenant Ops B (ID: 560)
- Slug: `tenant-ops-b`
- Domain: `opsB.test`
- Account ID: `261`

**Data Counts:**
- `sales_orders`: 1

### Tenant: Tenant A 696cd12144a92 (ID: 561)
- Slug: `tenant-a-696cd12144a92`
- Domain: `tenant-a-696cd12144a92.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `payments`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 5

### Tenant: Tenant B 696cd12144a92 (ID: 562)
- Slug: `tenant-b-696cd12144a92`
- Domain: `tenant-b-696cd12144a92.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant 6A A (ID: 563)
- Slug: `tenant-6a-a-6`
- Domain: `t6a-a-696cd121d2de8.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `work_orders`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Tenant 6A B (ID: 564)
- Slug: `tenant-6a-b-6`
- Domain: `t6a-b-696cd121d2de8.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant Ops A H (ID: 567)
- Slug: `tenant-ops-a-h`
- Domain: `opsAh.test`
- Account ID: `268`

**Data Counts:**
- `customers`: 1
- `sales_orders`: 1
- `vessels`: 1

### Tenant: Tenant Ops B H (ID: 568)
- Slug: `tenant-ops-b-h`
- Domain: `opsBh.test`
- Account ID: `268`

**Data Counts:**
- `sales_orders`: 1

### Tenant: Tenant A 696cd3a1b52c7 (ID: 569)
- Slug: `tenant-a-696cd3a1b52c7`
- Domain: `tenant-a-696cd3a1b52c7.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `payments`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 5

### Tenant: Tenant B 696cd3a1b52c7 (ID: 570)
- Slug: `tenant-b-696cd3a1b52c7`
- Domain: `tenant-b-696cd3a1b52c7.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant 6A A (ID: 571)
- Slug: `tenant-6a-a-7`
- Domain: `t6a-a-696cd3a24cc9b.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `work_orders`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Tenant 6A B (ID: 572)
- Slug: `tenant-6a-b-7`
- Domain: `t6a-b-696cd3a24cc9b.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant A 696cd54665978 (ID: 575)
- Slug: `tenant-a-696cd54665978`
- Domain: `tenant-a-696cd54665978.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `sales_orders`: 1
- `work_orders`: 1
- `invoices`: 1
- `payments`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `bank_accounts`: 1
- `activity_logs`: 5

### Tenant: Tenant B 696cd54665978 (ID: 576)
- Slug: `tenant-b-696cd54665978`
- Domain: `tenant-b-696cd54665978.test`
- Account ID: ``

**Data Counts:**

### Tenant: Tenant 6A A (ID: 577)
- Slug: `tenant-6a-a-8`
- Domain: `t6a-a-696cd547007d7.test`
- Account ID: ``

**Data Counts:**
- `customers`: 1
- `quotes`: 1
- `work_orders`: 1
- `vessels`: 1
- `products`: 1
- `warehouses`: 1
- `activity_logs`: 3

### Tenant: Tenant 6A B (ID: 578)
- Slug: `tenant-6a-b-8`
- Domain: `t6a-b-696cd547007d7.test`
- Account ID: ``

**Data Counts:**

## 3. Integrity & Anomalies
### Null tenant_id Checks
- 🔴 **ALERT**: `customers` has 108 records with NULL tenant_id.
- 🔴 **ALERT**: `vessels` has 103 records with NULL tenant_id.
- 🔴 **ALERT**: `work_orders` has 92 records with NULL tenant_id.
- 🔴 **ALERT**: `sales_orders` has 52 records with NULL tenant_id.
- 🔴 **ALERT**: `invoices` has 46 records with NULL tenant_id.
- 🔴 **ALERT**: `payments` has 47 records with NULL tenant_id.
- 🔴 **ALERT**: `products` has 98 records with NULL tenant_id.
- 🔴 **ALERT**: `warehouses` has 97 records with NULL tenant_id.
- 🔴 **ALERT**: `quotes` has 94 records with NULL tenant_id.
### Cross-Tenant Mismatches
- 🔴 **Mismatch**: 2 Sales Orders have different tenant_id than their Customer.
## 4. Leak & Demo Data Detection
Scanning for patterns: `*.test`, `*@test.com`, `Test Tenant`...
- Found **245** tenants matching test/demo patterns.
- Found **172** customers with test emails.
## 5. Cleanup Recommendations (PR13b)
Based on the audit, the following actions are recommended for the Garden Cleanup:
### Safe to Truncate (Garden)
The following tables contain operational data and should be truncated for a fresh seed:
```json
[
    "categories",
    "tags",
    "stock_movements",
    "quote_items",
    "sales_order_shipment_lines",
    "sales_order_items",
    "sales_order_shipments",
    "contract_template_versions",
    "contract_templates",
    "company_profiles",
    "activity_logs",
    "work_order_photos",
    "customers",
    "vessels",
    "work_orders",
    "contracts",
    "sales_orders",
    "invoices",
    "payments",
    "bank_accounts",
    "products",
    "warehouses",
    "ledger_entries",
    "quote_sequences",
    "sales_order_sequences",
    "contract_sequences",
    "tenant_user",
    "tenant_invitations",
    "currencies",
    "quotes",
    "plans",
    "accounts",
    "account_users",
    "tenants",
    "support_sessions",
    "audit_logs",
    "plan_change_requests"
]
```
### Protected Tables
These tables likely contain system configuration or permanent accounts and should NOT be truncated usually (unless fully re-seeding):
- `users` (Admin access)
- `tenants` (If we want to keep structure, otherwise re-seed)
- `plans` / `accounts` (Billing structure)
- `permissions` / `roles`