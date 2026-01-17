# epsilon-local-login

## Amaç
epsilon-crm local ortamında (http://127.0.0.1:8000) browser walkthrough / e2e doğrulama sırasında login sürtünmesini sıfırlamak.
Antigravity, her testte aynı kullanıcı ile giriş yapar; yeni kullanıcı üretmez / farklı credential denemez.

## Default Login
- Base URL: http://127.0.0.1:8000
- Email: vahap@ozmo.com
- Password: password

> Güvenlik notu: Bu credential sadece LOCAL geliştirme içindir. Prod/remote ortamda asla kullanılmaz.

## Ne zaman tetiklenir?
- Browser walkthrough / UI test / e2e / smoke test isteklerinde
- “login”, “epsilon-local-login”, “127.0.0.1:8000”, “tenant switcher” geçen görevlerde
- Verify script sonrası UI üzerinden kontrol isteklerinde

## Kurallar
1) HER ZAMAN önce bu kullanıcıyla login dene. Başarısızsa raporla, alternatif user oluşturma.
2) Login sonrası doğrulama:
   - /dashboard açılıyor mu?
   - Header’da kullanıcı adı/email görünüyor mu?
3) Tenant switcher varsa:
   - Dropdown görünür mü?
   - Mevcut tenant adı header’da görünüyor mu?
4) Browser session mümkünse aynı run içinde korunur; logout edilmez.

## Adımlar (Browser)
1) http://127.0.0.1:8000 aç
2) Login ekranına yönlendirildiyse:
   - Email: vahap@ozmo.com
   - Password: password
   - Submit
3) Başarılı login sonrası /dashboard bekle
4) Test senaryosuna devam et

## Hata durumları
- Login form alanları bulunamazsa: DOM selector/route değişmiş olabilir → raporla.
- 419/CSRF: sayfayı hard refresh + tekrar dene.
- 500: stack trace/screenshot al → raporla.
