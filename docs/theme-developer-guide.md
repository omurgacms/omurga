# Omurga Tema Geliştirici Kılavuzu

Bu belge Omurga CMS için üçüncü parti tema geliştirmek isteyenler içindir.

## Temel klasör yapısı

```text
benim-temam/
  theme.json
  functions.php
  screenshot.jpg
  preview.jpg
  assets/
    css/style.css
    js/app.js
  views/
  blocks/
  demos/
  languages/
  home.php veya home.omg
  single.php veya single.omg
  page.php veya page.omg
  category.php veya category.omg
  header.php veya header.omg
  footer.php veya footer.omg
```

## Tema API yardımcıları

```php
om_theme_setting('primary_color', '#f97316');
om_theme_setting_bool('show_author', true);
om_theme_asset('assets/css/style.css');
om_menu('main');
om_region('home_main');
om_body_class();
om_theme_supports('comments');
```

## Menü konumları

Tema `theme.json` içinde kendi menü konumlarını tanımlayabilir. Çekirdeğin standart konumları da kullanılabilir:

- `main` Ana Menü
- `mobile` Mobil Menü
- `footer` Footer Menü
- `top` Üst Menü

Tema içinden menü basmak için:

```php
echo om_menu('main');
```

## Tema ayarları

`theme.json` içindeki `settings` alanı yönetim panelindeki tema ayarlarını oluşturur. Desteklenen temel tipler:

- `text`
- `url`
- `image`
- `color`
- `number`
- `checkbox`
- `select`

## Hook sistemi

Tema `functions.php` dosyasında hook kullanabilir:

```php
omurga_add_action('omurga_theme_loaded', function($theme){
    // Tema yüklendiğinde çalışır.
});

omurga_add_filter('omurga_menu_locations', function($locations){
    $locations['secondary'] = 'İkincil Menü';
    return $locations;
});
```

## Güvenlik kuralları

Temalar görünüm içindir. Tema paketleri kullanıcı/rol/veritabanı/sistem/çekirdek yazma izinleri isteyemez. `config.php`, `storage`, `uploads` gibi kullanıcı verileri tema tarafından ezilmemelidir.
