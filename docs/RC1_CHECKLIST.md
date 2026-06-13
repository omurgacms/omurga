# Omurga CMS 1.2.0 RC1 Checklist

Omurga CMS 1.2.0 RC1 (`1.2.0-rc.1`) yeni özellik sürümü değildir. Yeni özellikler dondurulmuştur. Bu kontrol listesi yalnızca stabilizasyon, hata düzeltme, güvenlik, kurulum, medya, tema/paket, migration ve admin kararlılığı içindir.

## Kurulum

- [x] `/install/index.php` açılır.
- [x] XAMPP alt klasör kurulumu için URL tahmini `http://localhost/omurga` biçimini destekler.
- [x] PHP, PDO MySQL, ZipArchive ve GD/WebP kontrolleri kurulum ekranında görünür.
- [x] `storage/cache`, `storage/logs`, `uploads`, `themes` ve `packages` yazılabilirlik kontrolleri vardır.
- [x] Kurulum `config.php` ve `storage/installed.lock` oluşturur.

## Admin ve İçerik

- [x] `/admin/login.php` giriş, hatalı giriş ve şifremi unuttum sekmelerini gösterir.
- [x] Başarısız giriş denemeleri kayıt altına alınır.
- [x] Eski helper adlarıyla çalışan admin sayfalarında fatal riskleri giderildi.
- [x] Yazı ekranında öne çıkan görsel yalnızca medya seçici üzerinden seçilir.
- [x] Klasik editör ve blok editör arasında içerik senkronizasyonu korunur.

## Medya, Tema ve Paket

- [x] Medya modalı hızlı yükleme ve anlaşılır yükleniyor mesajı içerir.
- [x] Medya işleri kuyruğu panelden işlenebilir.
- [x] Tema ZIP yükleme analiz, güvenlik raporu ve sürüm karşılaştırması içerir.
- [x] Paket ZIP yükleme package.json, izin onayı ve sürüm karşılaştırması içerir.
- [x] Güncelleme/üzerine yazma/eski sürüme dönme işlemleri öncesinde yedek alınır.

## SEO, API ve Sistem

- [x] SEO merkezi sekmeleri ve endpoint linkleri alt klasör kurulumu için `omurga_url()` kullanır.
- [x] `/api/status` ve `/api/v1/status` JSON cevap verecek biçimde korunur.
- [x] API rate limit hatasında fatal yerine kontrollü JSON hata döner.
- [x] Health Check ve System Tests mevcut veriyi silmeden çalışır.
- [x] Migration ekranı tablo durumunu okunabilir biçimde gösterir.

## Dağıtım

- [ ] RC paketini oluşturmadan önce `config.php` dışarıda bırakılmalı.
- [ ] `storage/installed.lock` dışarıda bırakılmalı.
- [ ] Eski zip paketleri dışarıda bırakılmalı.
- [ ] Log, cache, tmp ve yerel test çıktıları dışarıda bırakılmalı.
