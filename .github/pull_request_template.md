# PR Açıklaması

Bu PR ne yapıyor? Hangi sorunu çözüyor?

## Değişiklik Türü
- [ ] Bug fix
- [ ] New feature
- [ ] Refactor
- [ ] Documentation

## Release Gate Checklist (Zorunlu)

- [ ] `composer verify:rc` PASS (Yerel kontrol)
- [ ] `npm run build` PASS (Eğer UI/Frontend değiştiyse)
- [ ] Yeni route eklendiyse ilgili sidebar menüsüne eklendi
- [ ] UI değiştiyse `x-ui.*` bileşenleri kullanıldı, tasarım bütünlüğü korundu
- [ ] DB::table / raw query varsa `tenant_id` filtresi kontrol edildi
- [ ] Production kodunda `withoutTenantScope` / `withoutGlobalScopes` YASAKLANDI
- [ ] Browser testleri kullanıcı tarafından manuel olarak yapıldı

## Manuel Test Sonuçları
- Test senaryosu: ...
- Sonuç: ...
