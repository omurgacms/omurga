# Omurga Resmi Tema/Paket Standardı ve İzin Sistemi

## Tema standardı

Zorunlu:

```text
theme.json
```

Önerilen resmi yapı:

```text
functions.php
screenshot.jpg
preview.jpg
assets/
views/
blocks/
demos/
languages/
```

Tema görünüm katmanıdır. Bu yüzden temalarda şu izinler çekirdek seviyesinde engellenir:

- users
- roles
- database
- sql
- system
- core_write
- unsafe_core_access
- shell

Tema demoları kullanıcı, rol, SQL ve sistem ayarı içe aktaramaz.

## Paket standardı

Zorunlu:

```text
package.json
package.php veya package.json içindeki main dosyası
```

Önerilen resmi yapı:

```text
install.php
update.php
uninstall.php
assets/
src/
languages/
```

## Paket izinleri

Desteklenen izinler:

- database: Paket kendi tablolarını/özel verilerini yönetebilir.
- media: Medya kütüphanesine erişebilir.
- cron: Zamanlanmış görev oluşturabilir.
- users: Kullanıcı ve rol yönetebilir.
- network: Uzak API bağlantısı kurabilir.
- storage: Kendi storage alanına dosya yazabilir.
- settings: Kendi ayarlarını kaydedebilir.
- admin_pages: Yönetim paneline sayfa ekleyebilir.
- blocks: Builder/blok sistemine blok ekleyebilir.

Paket özel izin istiyorsa yükleme sırasında yönetici onayı gerekir.
