# Epsilon UI Sistem Rehberi (Calm / Premium)

- Amaç: Uygulama genelinde tek tip “sakin, kurumsal” görünüm.
- Rozet/Pill: SADECE `x-ui.badge` varyantları kullanılır (neutral/info/success/danger). Asla `span` veya custom class ile badge yapma.
- Butonlar/Input: `x-ui.button`, `x-ui.input`, `x-ui.select` (varsa) tercih et.
- İkonlar: SADECE `x-icon.*` bileşenlerini kullan. ASLA `icon.*` (blade component) veya `svg` tag’i kullanma. `x-icon.plus`, `x-icon.trash` vb.
- Sayfa başlıkları: `x-ui.page-header` kullan.
- Tablo/filtreler: Mevcut yapıları kopyala, `x-ui.table` (varsa) veya standart Tailwind class’larını koru.
- Personel ekranları: Finansal veriler (tutar, bakiye) gizli kalmalı.
- Global: TÜM metinler Türkçe olmalı. İngilizce terim (Invoice, Draft vb.) kalmamalı.
