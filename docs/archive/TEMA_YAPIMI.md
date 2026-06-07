# Omurga Kolay Tema Yapımı

Omurga'da tema yapmak zor olmak zorunda değildir. En basit tema için şu dosyalar yeterlidir:

```text
benim-temam/
  theme.json
  home.php
  single.php
  category.php
```

İsteğe bağlı dosyalar:

```text
  page.php
  header.php
  footer.php
  screenshot.png
  assets/css/style.css
  assets/js/theme.js
```

## theme.json örneği

```json
{
  "name": "Benim Temam",
  "slug": "benim-temam",
  "version": "1.0.0",
  "author": "Adınız",
  "description": "Omurga için özel tema"
}
```

## Kullanılabilecek hazır yardımcılar

```php
setting('site_name')          // Site adı
omurga_url()                 // Site ana URL
post_url($post)              // İçerik linki
category_url($category)      // Kategori linki
image_url($path)             // Görsel URL
excerpt($html, 120)          // Kısa açıklama
menu_items()                 // Ana menü öğeleri
```

## Tema yükleme

Tema klasörünü zip yapıp panelden yükleyebilirsin:

```text
Omurga Panel > Temalar > Yeni Tema Yükle
```

Zip içinde tema klasörü olabilir veya dosyalar direkt zip kökünde olabilir. Omurga `theme.json` dosyasını bulur ve kontrol eder.

## Not

Admin panel tasarımı temadan etkilenmez. Tema sadece ziyaretçinin gördüğü ön yüzü değiştirir.


## Tema Blokları

Tema geliştiricileri `blocks/blok-adi/block.json` ve `view.php` dosyalarıyla kendi bloklarını oluşturabilir. Ayrıntılar için `DUZEN_BLOK_SISTEMI.md` dosyasına bakın.

## v1.6 Şablon Desteği

Temalar artık sabit sayfa ve yazı/haber detay şablonları tanımlayabilir.

En basit tema yine şu dosyalarla çalışır:

```text
benim-temam/
  theme.json
  home.php
  single.php
  category.php
  page.php
```

Ek şablon istenirse:

```text
benim-temam/templates/page-fullwidth.php
benim-temam/templates/single-wide.php
```

`theme.json` içine:

```json
"templates": {
  "page": {
    "fullwidth": {"name":"Tam Genişlik", "file":"templates/page-fullwidth.php"}
  },
  "single": {
    "wide": {"name":"Geniş Görselli", "file":"templates/single-wide.php"}
  }
}
```
