# Omurga v3.6.2 — Admin Dil Temizliği ve Geliştirici Hazırlığı

Bu sürümde amaç, v3.6.1 ile gelen dil altyapısını panel ve tema geliştirici tarafında daha kullanılır hale getirmektir.

## Yapılanlar

- Sürüm `3.6.2` yapıldı.
- Admin üst menü ve yan menüdeki ana metinler `om_t()` sistemine bağlandı.
- Profil bazlı menü adları dil anahtarlarıyla çalışacak şekilde düzenlendi.
- Ayarlar menüsüne **Dil Kontrolü** ekranı eklendi.
- `admin/language-check.php` eklendi.
- Çekirdek ve tema dil dosyalarında eksik TR/EN anahtarlarını kontrol eden ekran hazırlandı.
- Tema tarafından çekirdek anahtarlarının ezilip ezilmediği gösterilir.
- `THEME_DEVELOPER_GUIDE.md` eklendi.
- Çekirdek dil dosyalarına admin menüleri ve temel sistem metinleri için yeni anahtarlar eklendi.

## Dil önceliği

```text
1. Aktif tema lang dosyası
2. Çekirdek lang dosyası
3. Anahtarın kendisi
```

## Not

Bu sürüm içerik çeviri sistemi değildir. Yazı/sayfa/kategori çevirileri daha sonra ayrı modül veya eklenti olarak ele alınacaktır.
