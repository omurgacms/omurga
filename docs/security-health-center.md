# Omurga Güvenlik Merkezi ve Sistem Sağlığı

Bu sürümde yönetim paneline iki temel kontrol alanı eklendi.

## Güvenlik Merkezi

Konum: `admin/security.php`

Kontrol edilenler:

- Çekirdek bütünlüğü
- Çekirdek koruması
- Geliştirici modu
- PHP dosya düzenleme durumu
- Paket yükleme/silme izinleri
- Bakım modu
- Giriş deneme limiti
- Engellenen IP listesi
- Tema ve paketlerde riskli fonksiyon taraması

Riskli fonksiyon taraması uyarı amaçlıdır. `exec`, `shell_exec`, `system`, `unlink`, `rename`, `file_put_contents`, `eval`, `base64_decode` gibi kullanımları gösterir.

## Sistem Sağlığı

Konum: `admin/system.php`

Kontrol edilenler:

- PHP sürümü
- PDO MySQL
- GD/WebP
- Imagick
- ZipArchive
- JSON
- cURL
- Upload limitleri
- Memory limit
- Yazılabilir klasörler
- Veritabanı bağlantısı
- SSL/HTTPS
- Disk alanı
- Cron izi
- Cache klasörü
- Hata günlüğü
- Omurga ve veritabanı sürümü

Amaç, Omurga kurulumu sonrası hosting ve sistem problemlerini tek ekranda anlaşılır şekilde göstermektir.
