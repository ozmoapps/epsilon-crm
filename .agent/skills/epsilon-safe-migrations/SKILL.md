---
name: epsilon-safe-migrations
description: (TR) SQLite/MySQL/PgSQL uyumlu güvenli migration kuralları: idempotent, non-blocking, data backfill planı. (EN) Cross-DB safe migrations.
---

# Safe Migrations (Cross-DB)

## Kurallar
- SQLite/MySQL/PgSQL uyumunu gözet.
- Büyük tabloda riskli ALTER’lardan kaçın: gerekiyorsa 2 aşamalı migration (ekle -> backfill -> enforce).
- Nullability değişimi / FK ekleme gibi adımlarda veri doğrulama/backfill planı yaz.
- Geri dönüş (down) mantıklı olsun.
- Runtime riski varsa açıkça not et.
