# UI Design System Audit

## 1. Özet (Executive Summary)

**Durum**: Proje şu anda hibrit bir yapıdadır. Modern `ui` bileşenleri (`resources/views/components/ui`) mevcuttur, ancak eski kök bileşenler (`resources/views/components/*.blade.php`) bu yeni bileşenlere birer **vekil (wrapper/proxy)** olarak hizmet etmektedir.

**En Kritik 5 Problem:**
1.  **Yüksek Eski Bileşen Kullanımı**: `x-button` kullanımı (111 adet), modern `x-ui.button` kullanımının (54 adet) iki katıdır. Bu durum kod tabanında çift standart yaratmaktadır.
2.  **Yaygın Hardcoded Renkler**: `gray-*` paleti 94 farklı dosyada manuel olarak kullanılmıştır. Bu durum tema bütünlüğünü (Slate standardı) bozmaktadır.
3.  **Standart Dışı Gölgeler**: `shadow-sm` sınıfı 48 dosyada kalmıştır. Tasarım sisteminin `shadow-soft` veya `shadow-card` standartlarına uymayan eski bir gölgedir.
4.  **Standart Dışı Border Radius**: `rounded-lg` kullanımı 48 dosyada mevcuttur. Tasarım standardı `rounded-xl` veya `rounded-2xl` olmalıdır.
5.  **Bileşen Çiftliği**: Mantıksal olarak aynı işi yapan ancak farklı isimlendirilmiş iki set bileşen (`x-card` vs `x-ui.card`) geliştirici deneyimini karmaşıklaştırmaktadır.

---

## 2. Bileşen Çiftleri Analizi

| Dosya (Legacy) | Dosya (Modern UI) | Fark Durumu | Öneri |
| :--- | :--- | :--- | :--- |
| `components/button.blade.php` | `components/ui/button.blade.php` | **Wrapper**: Legacy dosya, modern bileşeni çağırıyor (`<x-ui.button>`). | Kısa vadede kalabilir, ancak `x-ui.button` kullanımı teşvik edilmeli. Uzun vadede legacy wrapper kaldırılmalı. |
| `components/card.blade.php` | `components/ui/card.blade.php` | **Wrapper**: Legacy dosya, modern bileşeni çağırıyor (`<x-ui.card>`). | `header` slot mantığı korunmuş. `x-ui.card` doğrudan kullanılmaya başlanmalı ve legacy kullanımlar refactor edilmeli. |
| `components/badge.blade.php` | `components/ui/badge.blade.php` | **Wrapper**: Legacy dosya, `status` prop'unu `variant` prop'una map ediyor. | Bu mapping faydalı olabilir ancak standartlaşma için `variant` prop'una geçiş yapılmalı ve wrapper kaldırılmalı. |

---

## 3. Kullanım Sayıları

| Bileşen / Etiket | Kullanım Sayısı |
| :--- | :--- |
| `<x-button ...>` | **111** |
| `<x-ui.button ...>` | 54 |
| `<x-card ...>` | 51 |
| `<x-ui.card ...>` | 35 |
| `<x-badge ...>` | 8 |
| `<x-ui.badge ...>` | 19 |
| `<x-ui.empty-state ...>` | 7 |

---

## 4. Stil Kalıntıları (Legacy Style Leftovers)

### Indigo Renk Kalıntıları (14 Dosya)
*(Tasarım sisteminde `brand` rengine dönüştürülmeli)*
- `resources/views/vessels/show.blade.php`
- `resources/views/quotes/_item_form.blade.php`
- `resources/views/quotes/show.blade.php`
- `resources/views/contracts/partials/_history.blade.php`
- `resources/views/contracts/partials/_delivery_pack.blade.php`
- `resources/views/admin/users/index.blade.php`
- *(Ve diğerleri...)*

### Gray Renk Kalıntıları (94 Dosya)
*(Tasarım sisteminde `slate` rengine dönüştürülmeli)*
Önemli dosyalar:
- `resources/views/contracts/index.blade.php`
- `resources/views/quotes/index.blade.php`
- `resources/views/sales_orders/index.blade.php`
- `resources/views/vessels/_form.blade.php`
- `resources/views/components/modal.blade.php`
- *(Liste çok uzun, toplu refactor gerektirir)*

### Shadow-sm Kalıntıları (48 Dosya)
*(Tasarım sisteminde `shadow-soft` olmalı)*
- `resources/views/customers/show.blade.php`
- `resources/views/welcome.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/components/ui/table.blade.php`
- `resources/views/components/text-input.blade.php`
- `resources/views/components/input.blade.php`
- `resources/views/components/select.blade.php`
- ...

### Rounded-lg Kalıntıları (48 Dosya)
*(Tasarım sisteminde `rounded-xl` veya `rounded-2xl` olmalı)*
- `resources/views/contract_templates/index.blade.php`
- `resources/views/components/ui/alert.blade.php`
- `resources/views/components/ui/row-actions.blade.php`
- `resources/views/components/dashboard/follow-ups.blade.php`
- `resources/views/quotes/_form.blade.php`
- ...

---

## 5. Önerilen Sonraki Faz Sırası

1.  **Faz 1: UI Wrapper Konsolidasyonu (Kısa Vade)**
    - Legacy bileşenlerin (`button`, `card`) tamamen "pass-through" çalıştığından ve ekstra stil/logic içermediğinden emin olun (Şu an öyle görünüyor).
    - Yeni kodlarda **SADECE** `x-ui.*` bileşenlerinin kullanılması kuralı getirin.

2.  **Faz 2: Stil Standardizasyonu (Orta Vade)**
    - `gray-*` -> `slate-*` dönüşümü için toplu "Find & Replace" işlemi.
    - `indigo-*` -> `brand-*` dönüşümü.
    - `rounded-lg` -> `rounded-xl` global değişimi.

3.  **Faz 3: Legacy Temizliği (Uzun Vade)**
    - `x-button`, `x-card`, `x-badge` kullanımlarının taranıp `x-ui.*` karşılıklarına dönüştürülmesi.
    - Legacy wrapper dosyalarının silinmesi.
