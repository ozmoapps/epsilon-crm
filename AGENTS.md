# AGENTS.md — Epsilon CRM + Teknik Servis (Laravel)

## Proje hedefi
- Mobil uyumlu (Tailwind)
- Sade, hızlı veri girişi
- Türkçe arayüz metinleri
- Modül modül ilerleme (1 modül = 1 PR)

## Teknoloji
- Laravel + Breeze (Blade)
- DB: sqlite

## Zorunlu standartlar
- Route’lar auth middleware altında olacak.
- CRUD ekranları: index/create/edit/show/delete
- Liste ekranlarında arama (isim) + sayfalama olacak.
- Formlarda validation zorunlu; hata mesajları Türkçe olacak.
- UI değiştiyse: npm run build
- DB değiştiyse: php artisan migrate
