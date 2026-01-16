---
name: epsilon-ui-system
description: (TR) epsilon-crm UI standardizasyonu: x-ui.* ve x-icon.* kullanımı, badge/pill kuralları, calm UI. (EN) UI system enforcement for epsilon-crm.
---

# Epsilon UI System

## Talimat
1) Önce `resources/UI_STYLE_GUIDE.md` dosyasını oku ve **HARFİYEN** uy.
2) İkon gerektiğinde asla tahmin yürütme; `grep` ile `x-icon.` araması yapıp var olanı kullan.
3) Badge/Pill gerektiğinde ASLA `class="bg-red-500 ..."` yazma. `<x-ui.badge variant="danger">` kullan.
4) Tüm UI metinleri **Türkçe** olmalıdır. Codebase'de İngilizce varsa bile Türkçeleştir.
5) “Görüntüyü düzeltmek için” gereksiz refactor yapma; minimum diff ile standardize et.
6) Yeni UI eklerken mevcut bileşen desenlerini kopyala (page header, card, badge, button).
