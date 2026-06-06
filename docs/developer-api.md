# Omurga Developer API Standardı

Bu standart Omurga CMS 1.0.2.2 Beta için tema ve paket geliştiricilerinin çekirdeğe dokunmadan güvenli geliştirme yapmasını sağlar.

## Temel Kurallar

- Tema görünüm işidir; kullanıcı, rol, çekirdek ve sistem ayarı değiştiremez.
- Paket özellik işidir; gerekiyorsa `package.json` içinde izin ister.
- `/core`, `/admin`, `/install`, `bootstrap.php` korumalıdır.
- Demo içe aktarma kullanıcı/rol/şifre/sistem ayarı taşımaz.

## Hook / Event API

```php
Omurga::addAction('omurga_after_theme_demo_import', function($theme, $demo, $content, $options, $result) {
    // işlem
});

Omurga::addFilter('omurga_theme_meta', function($meta, $slug) {
    return $meta;
});
```

Eski fonksiyon adları da desteklenir:

```php
omurga_add_action('hook_adi', $callback);
omurga_add_filter('filter_adi', $callback);
```

## Blok API

```php
Omurga::addBlock('ornek-blok', [
    'name' => 'Örnek Blok',
    'category' => 'custom',
    'source' => 'package',
    'settings' => [
        'title' => ['type'=>'text','label'=>'Başlık','default'=>'Merhaba']
    ],
    'render_callback' => function($settings) {
        return '<div>'.e($settings['title'] ?? '').'</div>';
    }
]);
```

## Paket Manifesti

`package.json` örneği:

```json
{
  "slug": "ornek-paket",
  "name": "Örnek Paket",
  "version": "1.0.0",
  "main": "package.php",
  "permissions": ["database"],
  "admin_pages": [
    {
      "id": "ornek-ayarlari",
      "title": "Örnek Ayarları",
      "file": "admin/settings.php",
      "capability": "plugins.manage"
    }
  ]
}
```

## Paket PHP API

```php
Omurga::addAdminPage([
    'id' => 'ornek-panel',
    'title' => 'Örnek Panel',
    'file' => __DIR__.'/admin/panel.php',
    'capability' => 'plugins.manage'
]);
```

## Tema Manifesti

`theme.json` örneği:

```json
{
  "slug": "omurga-haber",
  "name": "Omurga Haber",
  "version": "1.0.0",
  "settings": {
    "primary_color": {"type":"color", "label":"Ana Renk", "default":"#f97316"}
  }
}
```

## Çoklu Demo Standardı

Tema içinde yapı:

```text
themes/tema-adi/demos/
  haber-ajansi/
    demo.json
    content.json
    preview.jpg
  kurumsal/
    demo.json
    content.json
    preview.jpg
```

`demo.json`:

```json
{
  "slug": "haber-ajansi",
  "name": "Haber Ajansı",
  "description": "Haber sitesi için demo içerik"
}
```

`content.json` içinde güvenli alanlar kullanılabilir:

```json
{
  "settings": {
    "primary_color": "#d71920"
  },
  "pages": [],
  "posts": [],
  "categories": [],
  "menus": [],
  "media": [],
  "builder_layouts": []
}
```

Yasaklı alanlar:

```text
users, user_roles, roles, permissions, admins, admin_users, system_settings, core_settings, config, database, sql
```

Bu alanlar varsa demo içe aktarma çekirdek seviyesinde durdurulur.

## Yeni Yardımcı Fonksiyonlar

```php
omurga_theme_demos($themeSlug);
omurga_import_theme_demo($themeSlug, $demoSlug, ['theme_settings'=>true]);
omurga_register_theme_demo($themeSlug, $demoArray);
omurga_register_admin_page($pageArray);
omurga_enqueue_style($handle, $url);
omurga_enqueue_script($handle, $url);
```
