# Omurga Platform API

Bu katman tema ve paket geliştiricileri için standart platform kurallarını sağlar.

## Standart Event Listesi

- `omurga.init`
- `omurga.admin.init`
- `omurga.front.init`
- `omurga.theme.loaded`
- `omurga.package.loaded`
- `omurga.package.activated`
- `omurga.package.deactivated`
- `omurga.post.save`
- `omurga.post.publish`
- `omurga.user.login`
- `omurga.user.logout`
- `omurga.media.uploaded`
- `omurga.cron.run`
- `omurga.center.check_updates`

Kullanım:

```php
Omurga::addAction('omurga.post.publish', function($post){
    // yayın sonrası işlem
});
```

## Manifest Standardı

Paketlerde `package.json`, temalarda `theme.json` zorunludur.

```json
{
  "name": "SEO Paketi",
  "slug": "seo-paketi",
  "version": "1.0.0",
  "type": "package",
  "permissions": ["database"],
  "requires": []
}
```

Tema manifestlerinde `users`, `roles`, `core`, `system`, `sql` izinleri yasaktır.

## Sürüm Karşılaştırma

```php
$result = Omurga::compareVersion('1.0.0', '1.1.0');
// upgrade, reinstall, downgrade
```

## Bağımlılık Kontrolü

```php
$check = Omurga::checkDependencies($manifest, function($slug){
    return package_is_installed($slug);
});
```

## Cron / Görev Zamanlayıcı

```php
Omurga::schedule('my.package.sync', 'daily');
Omurga::addAction('my.package.sync', function(){
    // günlük görev
});
```

CLI ile çalıştırma:

```bash
php omurga cron:run
```

## Medya API

```php
$result = Omurga::upload($_FILES['image'], 'news');
$webp = Omurga::webp($result['path']);
```

## Omurga Merkezi API

```php
$url = Omurga::center('api/v1/packages');
```

`OMURGA_CENTER_URL` tanımlı değilse varsayılan merkez adresi kullanılır.

## Child Theme Standardı

Tema manifestine parent eklenir:

```json
{
  "name": "Omurga Haber Child",
  "slug": "omurga-haber-child",
  "version": "1.0.0",
  "type": "theme",
  "parent": "omurga-haber"
}
```

Ana tema güncellense bile child tema dosyaları korunur.

## CLI Komutları

```bash
php omurga cache:clear
php omurga cron:run
php omurga manifest:check themes/omurga-haber theme
php omurga version:compare 1.0.0 1.1.0
```
