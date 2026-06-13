# Upgrade to Omurga CMS 1.2.0 RC1

Omurga CMS 1.2.0 RC1 (`1.2.0-rc.1`) bir stabilizasyon ve release candidate sürümüdür. Yeni özellik sürümü değildir; yeni özellikler dondurulmuştur.

## Yükseltmeden Önce

1. Dosya yedeği alın.
2. Veritabanı yedeği alın.
3. Mevcut `config.php` dosyanızı koruyun.
4. Mevcut `storage/installed.lock` dosyanızı koruyun.
5. Proje kökündeki eski zip paketlerini dağıtım içine almayın.

## Yükseltme

1. RC1 dosyalarını mevcut kurulumun üzerine yükleyin.
2. `config.php` dosyasını değiştirmeyin.
3. `storage/installed.lock` dosyasını silmeyin.
4. Admin panele giriş yapın.
5. `/admin/migrations.php` ekranında migration durumunu kontrol edin.
6. `/admin/health-check.php` ve `/admin/system-tests.php` ekranlarını çalıştırın.
7. `/sitemap.xml`, `/feed.xml`, `/rss.xml`, `/atom.xml`, `/google-news.xml`, `/news-sitemap.xml` ve `/robots.txt` endpointlerini kontrol edin.

## Kontrol Edilecek Akışlar

- Admin giriş ve hatalı giriş mesajları.
- Yazı ekleme, taslak kaydetme, yayınlama ve güncelleme.
- Klasik editör ile blok editör arasında geçiş.
- Medya modalından yükleme ve öne çıkan görsel seçimi.
- Medya işleri kuyruğunu işleme.
- Tema/paket ZIP analiz ve onay ekranları.
- API durum endpointleri: `/api/status` ve `/api/v1/status`.

## Dağıtım Dışında Kalması Gerekenler

- `config.php`
- `storage/installed.lock`
- `storage/logs/*`
- `storage/cache/*`
- `storage/tmp/*`
- Eski veya yerel zip paketleri
