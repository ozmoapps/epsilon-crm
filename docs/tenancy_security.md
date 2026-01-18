# Epsilon CRM Tenancy Security & Privacy Model

**Versiyon:** v6c (RC)
**Tarih:** 2026-01-18

Bu doküman, Epsilon CRM altyapısındaki "Multi-Tenancy" (Çok Kiracılı) mimarinin güvenlik, izolasyon ve gizlilik standartlarını tanımlar. Geliştirilen her yeni özellik bu kurallara uymak zorundadır.

## 1. Amaç ve Tehdit Modeli

Temel amaç, **Tenant Isolation** hatasızlığını %100 sağlamaktır.
- **Data Leakage (Sızıntı):** Tenant A kullanıcısı, Tenant B verisini göremez, ID tahmin ederek erişemez.
- **Admin Privacy (Yönetici Gizliliği):** Platform Super Admin'ler, davet edilmedikleri veya "Break-Glass" protokolünü başlatmadıkları sürece Tenant operasyonel verisine (Finans, Stok, CRM) erişemez.

## 2. Membership-First SetTenant Akışı

Sistem "Domain Driven" değil, "Membership Driven" (Üyelik Odaklı) çalışır.
- **0 Üyelik:** Kullanıcı `manage.tenants.join` ekranına yönlendirilir.
- **1 Üyelik:** Otomatik olarak o Tenant context'ine sokulur.
- **2+ Üyelik:** Kullanıcı `manage.tenants.select` ekranına yönlendirilir.
- **Middleware:** `SetTenant.php` tüm isteklerde aktif context'i yönetir.

## 3. TenantScoped (Global Scope)

Veri izolasyonunun temel taşı `App\Models\Concerns\TenantScoped` trait'idir.
- Bu trait'i kullanan tüm Model'ler için `tenant_id` filtresi otomatik uygulanır.
- Bu trait **OPT-IN** (seçmeli) değildir, operasyonel model ise **ZORUNLUDUR**.
- Developer'ın `where('tenant_id', ...)` yazmasına gerek kalmadan tüm sorgular filtrelenir.

## 4. Wave-3 Propagation (İlişkisel Bütünlük)

Child kayıtlar için (örn. `QuoteItem`, `StockMovement`, `InvoiceLine`) `tenant_id` sütunu taşınmıştır (Denormalizasyon).
- Amaç: Parent kayıtlara join yapmadan doğrudan izolasyonu sağlamak.
- Verify Script: `verify_tenancy_v6a_wave3_propagation.php` child tabloların tenant_id tutarlılığını doğrular.

## 5. Support Session (Break-Glass)

Super Admin'lerin tenant verisine erişimi kısıtlıdır. Sadece acil durumlarda:
1. Admin, Tenant panelinden "Support Erişimi" talep eder.
2. DB'de `support_sessions` tablosunda süreli (TTL) bir kayıt oluşur.
3. Bu kayıt olmadan Admin tenant context'ine giremez (403 Forbidden). /admin rotaları hariç.

## 6. Yasaklar ve Kısıtlamalar

Production kodunda (Controllers, Services, Jobs, vb.) aşağıdaki kullanımlar **YASAKTIR**:
- `withoutTenantScope()`
- `withoutGlobalScope()`
- `withoutGlobalScopes()`
- `DB::table(...)` kullanırken `tenant_id` filtresi vermemek.

*İstisna:* Migration, Seeder, ve `storage/verify_*.php` test scriptleri.

## 7. Yeni Model Ekleme Checklist

Yeni bir operasyonel model (örn. `Project`, `Task`) eklerken:
- [ ] Migration'da `$table->unsignedBigInteger('tenant_id')->index();` ekle.
- [ ] Model sınıfına `use TenantScoped;` ekle.
- [ ] `User` üzerinden değil `Tenant` üzerinden ilişki kurmayı tercih et.
- [ ] `verify_tenancy_rc.php` çalıştırarak regresyon testi yap.

## 8. Verification & Release Gate

Kod değişikliği sonrası tüm güvenlik kontrollerini tek komutla çalıştırın:

```bash
php storage/verify_tenancy_rc.php
```

Bu komut sırasıyla:
1. Support Session & Privacy Check
2. Negative Leak Tests (ID Guessing)
3. Wave-3 Propagation Check
4. No-Context Denies (Membership Logic)
5. Aggregate/Report Isolation (DB::table checks)
6. Bypass Audit (Static Code Analysis)

**Sonuç "PASS" olmadan PR birleştirilemez.**

Daha fazla detay ve CI entegrasyonu için bkz: [docs/ci_release_gate.md](ci_release_gate.md)
