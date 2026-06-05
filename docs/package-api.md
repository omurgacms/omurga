# Omurga CMS 1.0.2 Beta Package API

Omurga CMS 1.0.2 Beta için resmi paket klasörü `packages/` dizinidir. Yeni geliştirme yalnızca `packages/` içinde yapılmalıdır.

## Zorunlu Yapi

```text
packages/ornek-paket/
  package.json
  package.php
  blocks/
    ornek-blok/
      block.json
      view.omg
```

## package.json

```json
{
  "name": "Ornek Paket",
  "slug": "ornek-paket",
  "version": "1.0.0",
  "description": "Omurga CMS paketi.",
  "author": "Omurga",
  "min_php": "8.1",
  "min_omurga": "4.0.0",
  "requires": [],
  "main": "package.php",
  "blocks": [],
  "admin_pages": []
}
```

## Etkinlestirme

Paketler `admin/packages.php` ekranindan etkinlestirilir veya devre disi birakilir. Gereksinimleri karsilanmayan paketler siteyi cokertmez; admin panelinde uyari gosterilir.

## Hook Kullanimi

`package.php` icinde hook ve blok API kullanilabilir:

```php
omurga_add_action('omurga_package_loaded', function ($slug, $meta) {
    // paket yuklendi
});
```
