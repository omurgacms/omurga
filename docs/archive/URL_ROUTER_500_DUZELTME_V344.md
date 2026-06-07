# Omurga v3.4.4 - URL / Router 500 Düzeltmesi

Bu sürüm, içerik bağlantılarına tıklanınca görülen 500 sistem hatasını düzeltir.

## Düzeltilen ana sorun

`index.php` içindeki eski kök haber bağlantılarını yeni `/haber/slug` yapısına yönlendiren kod, `redirect()` fonksiyonuna HTTP durum kodu gönderiyordu. Eski `redirect()` fonksiyonu yalnızca tek parametre kabul ettiği için bazı sunucularda fatal hata oluşuyordu.

Düzeltme:

```php
redirect(string $path, int $statusCode = 302)
```

Artık şu kullanım güvenlidir:

```php
redirect('haber/yazi-slug', 301);
```

## URL yapısı

- Haber/yazı: `/haber/yazi-slug`
- Sabit sayfa: `/sayfa-slug`
- Eski sabit sayfa: `/sayfa/sayfa-slug` -> `/sayfa-slug`
- Eski kök haber: `/yazi-slug` -> `/haber/yazi-slug`

## Not

Manşet, son dakika, mobil manşet gibi tema/blok işleri çekirdeğe eklenmedi.
