# epsilon-crm System X-ray / Durum Raporu

**Tarih:** 12 Ocak 2026
**Environment:** Local (macOS)
**PHP:** 8.5.1
**Laravel:** 12.46.0

## 1. Executive Summary

`epsilon-crm`, modern bir Laravel mimarisi (v12.x) üzerine kurulu, aktif geliştirilen bir CRM projesidir. Proje genel olarak sağlıklı bir yapıya sahip olmakla birlikte, kod standartları (styling) ve operasyonel yapılandırma (log yönetimi, queue) konularında iyileştirme fırsatları barındırmaktadır.

**Öne Çıkan Bulgular:**
*   **Modern Altyapı:** PHP 8.5 ve Laravel 12 kullanımı, projenin güncel teknolojilerle geliştirildiğini göstermektedir.
*   **Veritabanı:** SQLite kullanımı development için uygundur ancak production için migration stratejisi (MySQL/PostgreSQL) değerlendirilmelidir. Migration'lar düzenli ve günceldir.
*   **Kod Kalitesi:** `Pint` analizinde çok sayıda stil hatası (whitespace, braces) tespit edilmiştir. Bu durum işlevsel bir sorun olmasa da kod okunabilirliğini etkileyebilir.
*   **Test Altyapısı:** `tests/Feature` ve `tests/Unit` klasörleri dolu, `UiSmokeTest` gibi kritik testler mevcuttur.
*   **Eksik Klasörler:** `app/Jobs`, `app/Events`, `app/Listeners` klasörlerinin **bulunmadığı** gözlemlenmiştir. Bu durum, business logic'in controller veya service'lere sıkışmış olabileceğini veya queue yapısının henüz etkin kullanılmadığını işaret edebilir.

---

## 2. Sistem Envanteri

### Teknik Yığın
| Bileşen | Versiyon | Notlar |
| :--- | :--- | :--- |
| **PHP** | 8.5.1 | Güncel ve performanslı. |
| **Laravel** | 12.46.0 | Gelecek/Alpha sürüm veya development branch kullanımı. |
| **Composer** | 2.9.3 | Bağımlılık yöneticisi güncel. |
| **Veritabanı** | SQLite | `database/database.sqlite` (Development). |
| **Frontend** | Tailwind CSS | `tailwind.config.js` mevcut. |

### Uygulama İstatistikleri
*   **Route Sayısı:** ~ (Route list artifact'inden detaylar)
*   **Controller:** `app/Http/Controllers` altında yoğunlaşmış.
*   **Middleware:** Laravel 11+ yapısında `bootstrap/app.php` üzerinden yönetiliyor.
*   **Migrations:** 43 adet migration çalıştırılmış.

---

## 3. Repository & Kod Yapısı

### Klasör Yapısı Analizi
*   `app/Http/Controllers`: Standart MVC yapısı.
*   `app/Models`: Model dosyaları burada toplanmış (`Vessel`, `Quote`, `Customer` vb.).
*   `app/Services`: Business logic ayrımı için Service katmanı kullanımı mevcut (`ContractPdfService`, `TotalsCalculator` vb.). **(Olumlu)**
*   `app/Support`: Yardımcı sınıflar (`MoneyMath`, `DemoData`).
*   `app/Observers`: Model event'leri için observer kullanımı (`VesselOwnerHistoryObserver`). **(Olumlu)**

**Eksik/Dikkat Çekenler:**
*   `app/Jobs`, `app/Events`, `app/Listeners` klasörleri fiziksel olarak yok. Asenkron işlemlerin nasıl yönetildiği incelenmeli.

---

## 4. Veritabanı ve Bütünlük

*   **Durum:** Tüm migration'lar (43/43) başarıyla çalışmış (`Ran`).
*   **Kritik Tablolar:** `customers`, `vessels`, `quotes`, `sales_orders`, `contracts`.
*   **İlişkiler:** Foreign key kullanımı migration'larda görülüyor (ör: `ensure_customer_id_on_vessels_table`). Veri bütünlüğü önemsenmiş.
*   **Seeders:** `DatabaseSeeder`, `CrmDemoSeeder`, `DemoDataSeeder` mevcut. Demo veri üretimi kurgulanmış.

---

## 5. Kod Kalitesi & Test

### Statik Analiz (Laravel Pint)
*   **Durum:** `FAIL`
*   **Bulgular:** Birçok dosyada PSR standartlarına uymayan formatlamalar mevcut.
    *   Örnek: `app/Http/Controllers/QuoteController.php` (imports, blank lines).
    *   Örnek: `app/Models/Quote.php` (class attributes separation).
*   **Öneri:** Proje genelinde bir kez `pint` çalıştırılarak standart sağlanmalı.

### Test Kapsamı
*   `phpunit.xml` konfigürasyonu mevcut.
*   `tests/Feature` altında `ContractTest`, `UiSmokeTest` gibi anlamlı test dosyaları var.
*   Testlerin varlığı kaliteye verilen önemi gösteriyor.

---

## 6. Güvenlik & Operasyonel

### Güvenlik
*   **Environment:** `app/` dizininde kod içinde hardcoded `env()` kullanımı tespit edilmedi (grep sonucu temiz). Config dosyaları üzerinden erişim sağlanması doğru bir pratik.
*   **Auth:** Standart Laravel auth yapısı ve `resources/views/auth` görülüyor.

### Operasyonel
*   **Loglar:** `storage/logs/laravel.log` boyutu **5.2 MB**. Log rotasyonu (log rotation) konfigürasyonu kontrol edilmeli, aksi takdirde disk dolabilir.
*   **Cache/Queue:** Driver'lar `database` olarak ayarlı. Production ölçeklenmesinde Redis'e geçiş gerekebilir.

---

## 7. Önerilen Yol Haritası

### Kısa Vade (1 Hafta)
1.  **Kod Formatlama:** `bin/pint` komutu çalıştırılarak tüm stil hataları (`reports/_artifacts/pint_test.txt` içindekiler) otomatik düzeltilmeli.
2.  **Log Temizliği:** `laravel.log` arşivlenmeli veya temizlenmeli.
3.  **Smoke Test:** `tests/Feature/UiSmokeTest.php` düzenli CI sürecine eklenmeli.

### Orta Vade (1 Ay)
1.  **Veritabanı Kararı:** Production ortamı için SQLite yerine PostgreSQL/MySQL geçiş planı yapılmalı.
2.  **Job/Queue Yapısı:** Asenkron işlemler (PDF üretimi, e-posta gönderimi) için `app/Jobs` yapısına geçilmeli.

### Uzun Vade (3 Ay)
1.  **CI/CD Pipeline:** GitHub Actions veya benzeri bir araçla test ve linter süreçleri otomatize edilmeli.
2.  **Dokümantasyon:** API endpoint'leri ve sistem mimarisi dokümante edilmeli.

---

**Ekler:**
*   `reports/_artifacts/` klasöründe ham komut çıktıları mevcuttur.
