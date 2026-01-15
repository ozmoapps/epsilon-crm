# Release Checklist

Production deploy öncesi kontrol listesi.

## 1. Automated Checks
Test suite ve statik analizlerin geçtiğinden emin ol.

```bash
# 1. Frontend Build
npm run build

# 2. PHP Tests (Unit & Feature)
php artisan test

# 3. UI Guidelines Check
npm run ui:check
```

## 2. Smoke Tests (Local)
Hızlı manuel kontroller.

- [ ] **/ui route**: Local ortamda `/ui` sayfasına git, bileşenlerin yüklendiğini gör.
- [ ] **Login**: `/login` sayfası açılıyor mu?
- [ ] **Critical Pages**: Login ol ve aşağıdaki sayfaların crash olmadan açıldığını doğrula:
    - `/quotes`
    - `/customers`

## 3. Manual Flows
Kritik bir akışı manuel test et.

- [ ] **Create Quote**: Yeni bir teklif oluştur.
- [ ] **Update Quote**: Oluşturduğun teklifi düzenle.
- [ ] **Delete Quote**: Teklifi sil (Global Confirm Modal çalışıyor mu?).

---
**Not:** Eğer `npm run ui:check` hata verirse, ilgili UI ihlallerini düzeltmeden deploy alma.
