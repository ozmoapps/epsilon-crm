---
name: epsilon-identities
description: (TR) Standart test kullanıcıları (Admin, Tenant Admin, Tenant User) ve giriş bilgileri. (EN) Standard test identities and login procedure.
---

# Epsilon Identities

This skill provides standard identities for testing different roles in the Epsilon CRM application.

## Identities

All users have the password: `password`

### 1. Platform Admin (Sistem Yöneticisi)
- **Role**: Full system access, can manage tenants, billing, etc.
- **Email**: `admin@epsilon.com`
- **Login URL**: `/login` (Main domain)

### 2. Tenant Admin (Müşteri Yöneticisi)
- **Role**: Admin access within a specific tenant (Agent Test Tenant). Can invite users, manage settings.
- **Email**: `tenant_admin@epsilon.com`
- **Tenant**: Agent Test Tenant (slug: `agent-test-tenant`)
- **Login URL**: `/login` (Main domain, will be prompted to switch tenant or auto-routed)

### 3. Tenant User (Standart Kullanıcı)
- **Role**: Standard access within a specific tenant. Restricted from admin settings.
- **Email**: `tenant_user@epsilon.com`
- **Tenant**: Agent Test Tenant
- **Login URL**: `/login`

## Helper Script

If these users do not exist or you need to reset them, run the following command:

```bash
php storage/setup_agent_users.php
```

This script will:
1. Ensure the users exist.
2. Ensure the "Agent Test Tenant" exists.
3. Link the users to the tenant with the correct roles (`admin` or `member`).
4. Set/Reset passwords to `password`.

## Usage in Verification Scripts

When writing verification scripts (`storage/verify_...php`), you can look up these users by email to simulate login:

```php
$user = \App\Models\User::where('email', 'admin@epsilon.com')->first();
Auth::login($user);
```
