# Omurga Paket API Sistemi

Omurga paketleri `packages/` klasöründe çalışır ve resmi standart dosyası `package.json` dosyasıdır.

## Standart Paket Yapısı

```text
packages/ornek-paket/
  package.json
  package.php
  install.php       # isteğe bağlı
  update.php        # isteğe bağlı
  uninstall.php     # isteğe bağlı
  assets/
  blocks/
  languages/
  src/
```

## package.json

```json
{
  "name": "Örnek Paket",
  "slug": "ornek-paket",
  "version": "1.0.0",
  "description": "Omurga CMS için örnek paket.",
  "author": "Omurga",
  "min_php": "8.1",
  "min_omurga": "1.0.3.7-beta",
  "main": "package.php",
  "requires": [],
  "permissions": ["settings", "admin_pages"],
  "settings": {
    "enabled": {"type":"checkbox", "label":"Aktif", "default":"1"},
    "title": {"type":"text", "label":"Başlık", "default":"Örnek Paket"}
  },
  "admin_pages": [
    {"id":"ornek-paket", "title":"Örnek Paket", "file":"admin.php", "capability":"plugins.manage"}
  ]
}
```

## package.php içinde API Kullanımı

```php
<?php
if(!defined('OMURGA_INIT')) exit;

Omurga::addPermission('ornek.manage', 'Örnek paketi yönetebilir');

Omurga::addPackageSettings('ornek-paket', [
  'enabled' => ['type'=>'checkbox', 'label'=>'Aktif', 'default'=>'1'],
  'title' => ['type'=>'text', 'label'=>'Başlık', 'default'=>'Örnek Paket']
]);

Omurga::addPackageSettingsPage('ornek-paket', 'Örnek Paket Ayarları');

Omurga::addPackagePage('ornek-paket', 'dashboard', 'Örnek Panel', __DIR__.'/admin.php');

Omurga::addAction('omurga_package_loaded', function($slug, $meta){
  if($slug !== 'ornek-paket') return;
  // Paket yüklendi.
});
```

## Paket Ayarları

```php
$title = Omurga::getPackageSetting('ornek-paket', 'title', 'Varsayılan');
omurga_update_package_settings('ornek-paket', ['title' => 'Yeni Başlık']);
```

Desteklenen alan tipleri: `text`, `textarea`, `checkbox`, `select`, `number`, `url`, `email`, `color`.

## Paket Yönetim Sayfası

Paketler hem `package.json > admin_pages` ile hem de `Omurga::addPackagePage()` ile yönetim sayfası ekleyebilir.

## Paket Migration

```php
Omurga::addPackageMigration('ornek-paket', '1.0.1', function(){
  // Veritabanı güncellemesi
});
```

Migration kayıtları bir kez çalışır.

## Lifecycle Dosyaları

- `install.php`: paket ilk kez kurulduğunda
- `update.php`: mevcut paket güncellendiğinde
- `uninstall.php`: paket silinmeden önce

## Paket Asset URL

```php
$url = Omurga::packageAsset('ornek-paket', 'assets/style.css');
```

## Hook Sistemi

Paketler tema API ile aynı hook sistemini kullanır:

```php
Omurga::addAction('omurga.post.saved', function($postId){ });
Omurga::addFilter('omurga.content', function($content){ return $content; });
```

## Not

Eski `plugins/` sistemi pasiftir. Yeni geliştirmeler yalnızca `packages/` standardına göre yapılmalıdır.
