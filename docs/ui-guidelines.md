# UI Design Guidelines

## Renk Sistemi (Colors)
- **Nötr (Neutral):** `slate` (örn. `text-slate-600`, `bg-slate-50`)
- **Vurgu (Accent/Brand):** `brand` (örn. `text-brand-600`, `bg-brand-600`)
- **Durum (State/Semantic):** `emerald` (success), `amber` (warning), `rose` (error)

## Yüzeyler (Surfaces)
- **Kartlar (Cards):** `ui-card`
  - Standart: `rounded-2xl` + `shadow-card` + `border-slate-100`
  - *Örnek:* `<div class="ui-card">...</div>` veya `<x-ui.card>...</x-ui.card>`

## Tipografi (Typography)
- **Başlıklar (Headings):**
  - `.text-heading-1`: `text-3xl font-semibold tracking-tight`
  - `.text-heading-2`: `text-2xl font-semibold tracking-tight`
  - `.text-heading-3`: `text-xl font-semibold tracking-tight` (Page Titles)
  - `.text-heading-4`: `text-lg font-semibold tracking-tight` (Card/Modal Titles)
- **Gövde (Body):**
  - `.text-body`: `text-sm leading-6` (Varsayılan gövde metni)
  - `.text-body-sm`: `text-sm leading-6` (İkincil metinler, açıklamalar)
  - `.text-caption`: `text-xs leading-5` (Tablo başlıkları, alt bilgiler)

## Form Elemanları (Inputs)
- **Inputlar:** `ui-input`
  - Özellikler: `rounded-xl` + `border-slate-200` + `ui-focus`
  - **Kullanılmaması Gereken:** `shadow-sm` (Inputlarda gölge yok)

## Butonlar (Buttons)
- **Komponent:** Sadece `<x-ui.button>` kullanılmalı.
- Varyant ve boyut standartlarını component üzerinden yönetiyoruz.
- Manuel `button` tagi veya `a` tagi class yığını yerine componenti tercih edin.

## Kartlar ve Tablolar (Cards & Tables)
- **Önerilen:** `<x-ui.card>` ve `<x-ui.table>`
- Basit sarmalayıcılar yerine bu componentleri kullanarak standart padding/border yapısını koruyun.

## Yoğunluk (Density)

### Table Density
- **Prop:** `density` (`'comfort'` | `'compact'`)
- **Varsayılan:** `comfort`
- **Comfort:** Header `py-3`, Row `py-3` - Formlar ve detay sayfaları için ideal
- **Compact:** Header `py-2`, Row `py-2` - Index/liste sayfaları için daha fazla satır gösterir

**Kullanım:**
```blade
{{-- Index sayfaları için --}}
<x-ui.table density="compact">
    ...
</x-ui.table>

{{-- Detay/form sayfaları için (varsayılan) --}}
<x-ui.table>
    ...
</x-ui.table>
```

### Form Input Size
- **Prop:** `size` (`'md'` | `'sm'`)
- **Varsayılan:** `md`
- **md:** `h-10` - Standart boyut, formlar için önerilen
- **sm:** `h-9 text-sm` - Kompakt görünüm, filtre bölümleri için

**Kullanım:**
```blade
{{-- Standart form inputları (varsayılan) --}}
<x-input type="text" />
<x-select>...</x-select>
<x-textarea />

{{-- Kompakt filtre inputları --}}
<x-input type="text" size="sm" />
<x-select size="sm">...</x-select>
```

**Best Practices:**
- ✅ Index/liste sayfalarında tablo için `density="compact"` kullan
- ✅ Formlar ve detay sayfalarında varsayılan `comfort` kalsın
- ✅ Filtre bölümlerinde input `size="sm"` tercih edilebilir
- ✅ Bir sayfada tutarlı density/size kullan

## Durum ve Bildirimler (Status & Feedback)

### Semantik Durum Renkleri
Badge ve Alert bileşenleri aynı semantik renk haritasını kullanır:

| Durum      | Renk       | Kullanım Alanı                          |
|------------|------------|----------------------------------------|
| `neutral`  | `slate`    | Varsayılan, taslak, beklemede          |
| `info`     | `brand`    | Bilgilendirme, onay bekleyen           |
| `success`  | `emerald`  | Başarılı, tamamlandı, onaylandı        |
| `warning`  | `amber`    | Uyarı, dikkat gerekli                  |
| `danger`   | `rose`     | Hata, iptal, riskli işlem              |

**Ton Paletleri:**
- Background: `bg-{color}-50/50` (çok sakin, yarı saydam)
- Text: `text-{color}-700` (okunaklı, kontrast)
- Border/Ring: `border/ring-{color}-200/60` (yumuşak çerçeve)

### Badge Kullanımı

```blade
{{-- Yeni semantik varyantlar (önerilen) --}}
<x-ui.badge variant="neutral">Taslak</x-ui.badge>
<x-ui.badge variant="info">Bilgi</x-ui.badge>
<x-ui.badge variant="success">Onaylandı</x-ui.badge>
<x-ui.badge variant="warning">Dikkat</x-ui.badge>
<x-ui.badge variant="danger">İptal</x-ui.badge>

{{-- Legacy status varyantları (geriye dönük uyumluluk) --}}
<x-ui.badge variant="draft">Taslak</x-ui.badge>
<x-ui.badge variant="completed">Tamamlandı</x-ui.badge>
```

### Alert Kullanımı

```blade
{{-- Semantik tipler (önerilen) --}}
<x-ui.alert type="neutral" title="Bilgilendirme">
    İşlem bekleniyor.
</x-ui.alert>

<x-ui.alert type="info" title="Dikkat">
    Bu alan zorunludur.
</x-ui.alert>

<x-ui.alert type="success" title="Başarılı">
    Kayıt tamamlandı.
</x-ui.alert>

<x-ui.alert type="warning" title="Uyarı">
    Bu işlem geri alınamaz.
</x-ui.alert>

<x-ui.alert type="danger" title="Hata" dismissible>
    Bir sorun oluştu, lütfen tekrar deneyin.
</x-ui.alert>

{{-- Legacy tipler (primary, secondary, light) hala çalışır --}}
```

**Best Practices:**
- ✅ Yeni kodda semantik varyantları (`neutral`, `info`, `success`, `warning`, `danger`) kullan
- ✅ Badge ve Alert'i aynı durum için aynı renkte kullan
- ✅ Kullanıcıya net geri bildirim için `title` prop'unu tercih et
- ✅ Kritik durumlarda `dismissible` özelliğini aktif et

### Toast Bildirimleri

Flash mesajlar otomatik olarak **toast** olarak gösterilir. Toast bileşeni sağ alt köşede açılır ve 3.5 saniye sonra otomatik kapanır (hata mesajları 6 saniyedir).

**Session Flash Kullanımı (Backend):**
```php
// Controller'da (otomatik toast gösterilir)
return redirect()->route('quotes.index')
    ->with('success', 'Teklif başarıyla oluşturuldu');

return back()->with('error', 'Bir hata oluştu');
return back()->with('warning', 'Bu işlem geri alınamaz');
return back()->with('info', 'İşlem tamamlandı');
```

**Client-Side Event Kullanımı (Frontend):**
```javascript
// JavaScript ile
window.dispatchEvent(new CustomEvent('toast', {
    detail: {
        message: 'İşlem başarılı!',
        variant: 'success' // info|success|warning|danger|neutral
    }
}));
```

**Alpine.js ile:**
```blade
<x-ui.button 
    @click="window.dispatchEvent(new CustomEvent('toast', {detail: {message: 'Kayıt silindi', variant: 'danger'}}))"
>
    Sil
</x-ui.button>
```

## Yapılmaması Gerekenler (Don't List)
Mevcut tasarım dilini bozmamak için aşağıdakilerden kaçının:
- ❌ `gray-*` (Yerine `slate-*`)
- ❌ `indigo-*` (Yerine `brand-*`)
- ❌ `shadow-sm` (Özellikle inputlarda ve kartlarda kaldırıldı)
- ❌ `rounded-lg` (Varsayılan olarak `rounded-xl` veya `rounded-2xl` kullanıyoruz)

## PR Öncesi Kontrol Listesi (Pre-PR Checklist)

Pull request oluşturmadan önce aşağıdaki kontrolleri gerçekleştirin:

### Otomatik Kontroller
```bash
# UI standartlarını kontrol et (bulgu varsa CI fail eder)
npm run ui:check

# Build hatası olmadığını doğrula
npm run build
```

### Manuel Kontroller
- **UI Sayfası Quick Scan:** `/ui` sayfasını tarayıcıda aç ve componentlerin görünümünü doğrula
- **Değişen Sayfaları Test Et:** Değiştirdiğiniz sayfalarda görsel regresyon olmadığını kontrol edin
- **Responsive Kontrol:** En az bir mobil breakpoint'te test edin (özellikle layout değişiklikleri varsa)

**Not:** `npm run ui:check` hata veriyorsa, PR merge edilemez. Bulguları giderdikten sonra tekrar çalıştırın.


## Combobox Event Contract

Combobox bileşeni dış dünya ile `CustomEvent` üzerinden haberleşir. Bu sayede Livewire, Alpine veya Vanilla JS ile yönetilebilir.

### 1. Outgoing Events (Bileşenden Dışarıya)

#### `combobox:selected`
Kullanıcı bir seçim yaptığında tetiklenir.
- **Payload:** `{ name, value, label }`
- **Kullanım:** Form state'ini güncellemek veya cascading dropdown (il-ilçe) tetiklemek için.

#### `combobox:search`
Kullanıcı arama inputuna yazdığında veya dropdown açıldığında tetiklenir.
- **Payload:** `{ name, query, open }`
- **Kullanım:** Server-side filtering veya AJAX arama yapmak için.

### 2. Incoming Events (Dışarıdan Bileşene)

#### `combobox:set-options`
Bileşenin seçenek listesini dinamik olarak güncellemek için gönderilir.
- **Payload:** `{ name, options: [{value, label}, ...] }`
- **Etki:** Mevcut seçenekler silinir, yenileri yüklenir. Eğer o an seçili olan değer yeni listede varsa etiketi güncellenir, yoksa seçili değer korunur (ama etiket bozulabilir, bu yüzden set-value ile takip edilmesi önerilir).

#### `combobox:set-value`
Bileşenin değerini programatik olarak değiştirmek için gönderilir.
- **Payload:** `{ name, value }`
- **Etki:** Verilen `value` mevcut `options` içinde aranır.
  - **Bulunursa:** Seçim yapılır, input etiketi güncellenir.
  - **Bulunamazsa:** Seçim temizlenir (`null`), input boşaltılır. (**Golden Rule**)

### 3. Golden Rules
1. **Name Matching:** Tüm eventler `name` parametresini kontrol eder. Gönderilen `name` ile bileşenin `name` prop'u (trim edilmiş string olarak) birebir uyuşmalıdır.
2. **Options Normalization:** Seçenekler her zaman `{ value, label }` formatında olmalıdır.
3. **Filtering:** `set-options` sonrası client-side filtreleme (arama) yeniden hesaplanır.
4. **Value & Label Integrity:** `set-value` ile bilinen bir değer set edilirse etiket otomatik bulunur. Bilinmeyen bir değer set edilirse bileşen kendini temizler. Asla "Value: 5" gibi etiketi olmayan bir durum oluşmamalıdır.
