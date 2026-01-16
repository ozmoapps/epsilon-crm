---
name: epsilon-saas-tenancy
description: (TR) epsilon-crm SaaS dönüşümü: tek DB + tenant_id, izolasyon, onboarding, MVP akışı; önce analiz ve plan, sonra minimum riskli adımlar. (EN) SaaS multi-tenant architecture guidance.
---

# SaaS Tenancy Guide (Single DB + tenant_id)

## Öncelik sırası
1) Tenant sınırı (Company) ve data ownership haritası çıkar.
2) Minimum riskli tenancy temeli: tenant_id, scoping, seed/backfill, verify.
3) MVP akışını bozma: müşteri+tekne -> keşif -> teklif -> satış siparişi -> iş emri -> teslim raporu -> finans.
4) Yetkilendirme (RBAC) ayrı faz: şimdilik sadece altyapıyı hazırlayıp ileride role/policy ile bağlanacak şekilde tasarla.

## Çıktı beklentisi
- “SaaS Yol Haritası” artifact: Faz 0-1-2-3
- Risk matrisi (data leak, migration risk, performance)
- Minimal uygulama planı + manual test plan
