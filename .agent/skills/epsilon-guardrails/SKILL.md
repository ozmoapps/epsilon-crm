---
name: epsilon-guardrails
description: (TR) epsilon-crm’de her görevde minimum diff, refactor yok, dosyaları önce oku, Türkçe UI, manual test plan, verify script disiplini. (EN) Core guardrails for epsilon-crm tasks.
---

# Epsilon CRM Guardrails

## Zorunlu çalışma disiplini
1) **OKU**: Önce mevcut dosyaları (Controller, Model, View, Routes) oku. Tahminle kod yazma.
2) **HARİTA ÇIKAR**: Mevcut route + event contract + UI akışını not et.
3) **MİNİMUM DIFF**: Sadece isteneni yap. "Daha güzel olsun diye" çalışan kodu bozma (Refactor YASAK).
4) **DOĞRULA**: Çalışan akışları bozma; geriye dönük uyumluluk esastır.
5) **TAM DOSYA İSTE**: Bir dosyada değişiklik yapacaksan ve elinde sadece snippet varsa, ASLA snippet üzerine patch yapma. Kullanıcıdan `cat` veya `view_file` ile dosyanın TAMAMINI iste.
6) **TÜRKÇE UI**: Tüm arayüz ve mesajlar Türkçe olmalı. İngilizce button/label görürsen düzelt.
7) Değişiklik sonrası:
   - Manual test plan yaz
   - Mümkünse doğrulama scripti öner (storage/verify_*.php gibi)
   - npm run build / ilgili testleri çalıştır (mümkünse)
8) “Güvenlik” ve “Veri bütünlüğü” risklerini özellikle not et.

## Çıktı formatı standardı
- Plan -> Uygulama -> Değişen dosya listesi -> Riskler -> Manual test plan -> (Varsa) Verify script komutu
