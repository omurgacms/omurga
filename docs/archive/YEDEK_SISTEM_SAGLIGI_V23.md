# Omurga v2.3 — Yedekleme + Sistem Sağlığı

Bu sürüm Omurga çekirdeğinde güvenlik, yedekleme ve hata takibini güçlendirir.

## Yedekleme

Panel yolu:

```text
Sistem > Yedekleme
```

Eklenenler:

- Veritabanı yedeği alma
- Upload dosyaları yedeği alma
- Tam yedek alma
- Yedek indirme
- Yedek silme
- SQL yedeğini geri yükleme
- Upload ZIP yedeğini geri yükleme
- İşlem öncesi otomatik güvenlik yedeği

## Maksimum yedek listesi

Sunucu şişmesin diye yedek listesi sınırlandı.

Varsayılan:

```text
30 yedek
```

Panelden değiştirilebilir:

```text
Yedekleme > Yedek Limiti
```

Alt sınır: 5
Üst sınır: 200

Limit aşılırsa en eski yedekler hem veritabanı listesinden hem de `storage/backups` içinden silinir.

## Sistem Sağlığı

Panel yolu:

```text
Sistem > Sistem Sağlığı
```

Kontrol edilenler:

- PHP sürümü
- PDO MySQL
- GD/WebP
- Imagick
- ZipArchive
- JSON
- cURL
- Upload limiti
- Post max size
- Memory limit
- uploads yazılabilir mi
- storage yazılabilir mi
- backup klasörü yazılabilir mi
- cache/log/update klasörleri yazılabilir mi
- Omurga sürümü
- Veritabanı sürümü

## Hata kayıtları

Sistem ekranında son 100 hata gösterilir.

Hata dosyası:

```text
storage/logs/error.log
```

## Güncelleme güvenliği

Güncelleme uygulanırken:

1. Paket yüklenir
2. SQL yedeği alınır
3. Upload yedeği alınır
4. `config.php`, `uploads`, `storage` korunur
5. Paket uygulanır
6. Kayıt update_logs tablosuna yazılır

## Çekirdeğe eklenmeyenler

Bu sürümde manşet, sürmanşet, mobil manşet gibi tema/blok alanları çekirdeğe eklenmedi.
