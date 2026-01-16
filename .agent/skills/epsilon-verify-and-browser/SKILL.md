---
name: epsilon-verify-and-browser
description: (TR) epsilon-crm’de değişiklik sonrası otomatik/manuel doğrulama: build, verify script, Antigravity browser walkthrough, toast/500 kontrolü. (EN) Verification workflow + browser QA.
---

# Verify & Browser Walkthrough

## Zorunlu doğrulama adımları
1) `npm run build` çalıştır (KIRMIZI ÇİZGİ: build hatası varken asla görevi tamamla deme).
2) UI değişikliği yapıldıysa **MUTLAKA** browser tool ile walkthrough yap:
   - İlgili sayfayı aç -> Tıkla -> Form doldur -> Kaydet.
   - Sadece kodu okuyarak "bence çalışır" demek YASAK.
3) Kritik Akış Kontrolü:
   - Login -> Dashboard -> Work Orders index/show -> Foto yükleme/silme -> status değişimi
   - Hata/toast kontrolü: Başarı mesajı yeşil, hata kırmızı mi? Türkçe mi?
   - Console 500/JS error var mı? (Browser console loglarına bak).
3) DB değişikliği varsa:
   - migrate çalıştır
   - seed/demo varsa çalıştır
4) Sonuçları “Doğrulama Özeti” artifact’ında raporla.
