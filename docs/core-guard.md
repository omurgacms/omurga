# Omurga Çekirdek Koruma Sistemi

Omurga 1.0.2 Beta ile tema ve paketlerin çekirdeğe zarar vermesini azaltmak için çekirdek koruma sistemi eklendi.

## Korunan alanlar

Varsayılan olarak aşağıdaki alanlar tema ve paket işlemlerine kapalıdır:

- `admin/`
- `core/`
- `install/`
- `vendor/`
- `bootstrap.php`
- `config.php`
- `config.sample.php`
- `.htaccess`

Tema ve paketler kendi klasörlerinde çalışmaya devam eder:

- `themes/tema-slug/`
- `packages/paket-slug/`
- `storage/`
- `uploads/`

## Geliştirici modu

Güvenlik Merkezi üzerinden geliştirici modu açılabilir. Bu mod normal sitelerde kapalı kalmalıdır.

Ayar yolu:

`Admin > Güvenlik Merkezi > Çekirdek koruması`

## Paket izinleri

`package.json` içinde izinler belirtilebilir:

```json
{
  "slug": "ornek-paket",
  "name": "Örnek Paket",
  "version": "1.0.0",
  "permissions": ["blocks", "admin_pages"]
}
```

Riskli izinler sadece güvenilir geliştiriciler için düşünülmelidir:

- `shell`
- `core_write`
- `unsafe_core_access`

## Bütünlük kontrolü

Güvenlik Merkezi içinden çekirdek dosyaları için hash kaydı oluşturulabilir. Daha sonra `admin/`, `core/`, `install/` veya `bootstrap.php` değişirse panel uyarı verir.
