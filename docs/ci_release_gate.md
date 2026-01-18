# CI & Release Gate Rehberi

Epsilon CRM, "Tenancy Isolation" ve "Code Integrity" konularında katı kurallara sahiptir. Her PR için aşağıdaki **Release Gate** adımları zorunludur.

## 1. Release Gate Nedir?

Release Gate, uygulamanın kritik güvenlik ve izolasyon testlerini içeren bir bariyerdir. Bu testler geçilmeden kodun Production veya Main branch'e girmesi yasaktır.

Script konumu: `storage/verify_tenancy_rc.php`

Bu script şunları test eder:
- Tenancy Scopes (Leak Test)
- Support Session Security
- Admin Privacy
- Wave-3 Propagation
- Legacy Code Audit

## 2. Yerel Kontroller (Local Check)

PR açmadan önce aşağıdaki komutları çalıştırıp **PASS** aldığınızdan emin olun:

### A) Release Gate (PHP/Backend)
```bash
composer verify:rc
# veya
php storage/verify_tenancy_rc.php
```

### B) Frontend Build (UI Değişiklikleri İçin)
```bash
npm run build
```

## 3. GitHub Actions (CI)

Her `push` ve `pull_request` işleminde GitHub Actions otomatik olarak çalışır:
1. PHP 8.2 + SQLite ortamı kurar.
2. `npm run build` ile frontend varlıklarını derler.
3. `composer verify:rc` ile güvenlik testlerini çalıştırır.

Eğer CI fail olursa, PR birleştirilemez.

## 4. Sık Karşılaşılan Hatalar

- **SQLite Database Error:** CI ortamı temiz bir SQLite veritabanı kullanır. Migration dosyalarınızın SQLite uyumlu olduğundan emin olun.
- **Missing UI Component:** Yeni bir Blade componenti eklediyseniz ve build almayı unuttuysanız `npm run build` patlayabilir.
- **Tenant Scope Hatası:** `withoutTenantScope` kullanımı tespit edilirse `verify_tenancy_v6b3_bypass_audit.php` hata verir.
