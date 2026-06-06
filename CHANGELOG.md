## 1.0.5 Beta

- Haber V1, Kurumsal V1 ve Topluluk V1 temaları dağıtım paketine eklendi.
- Tema demo yapısı `demos/` altında düzenlendi.
- Kurulum profil seçimine göre varsayılan tema eşlemesi korundu.
- Temiz dağıtım için config, lock, log, cache ve eski paket çıktıları paketten çıkarıldı.
- Resmi sürüm bilgisi 1.0.5 Beta olarak güncellendi.


## 1.0.3.15 Beta

- Admin panelde kalan DLE/mavi renk tonları Omurga turuncu + slate renk sistemine uyarlandı.
- Mavi bilgi/sekme/vurgu renkleri sadeleştirildi.
- PHP syntax kontrolü yapıldı.

# Omurga CMS 1.0.3.15 Beta

- Medya seçici ve medya yükleme API'leri JSON uyumlu hale getirildi.
- Yazı ve sayfa editörlerinde öne çıkan görsel/görsel ekleme sırasında güvenlik hataları artık düz metin yerine JSON döndürür.
- Medya yükleme CSRF token gönderimi düzeltildi.

# Omurga CMS 1.0.3.15 Beta

- REST API sistemi eklendi.
- API anahtarı yönetim ekranı eklendi.
- Posts, pages, categories, tags, media ve users endpointleri eklendi.
- API dokümantasyonu eklendi.

# Omurga CMS 1.0.3.15 Beta

- Tema API yardımcıları eklendi.
- `theme.json` manifest standardı netleştirildi.
- Tema bazlı menü konumları desteklendi.
- Tema ayar API yardımcıları eklendi.
- Aktif temanın `functions.php` dosyası güvenli şekilde yüklenir.
- Hook sistemi tema geliştiriciler için dokümante edildi.
- Varsayılan tema dokümantasyonu eklendi.

# Omurga CMS 1.0.3.15 Beta

- Admin menü görünümü sadeleştirildi.
- Kategori/grup bazlı renkli işaretlemeler azaltıldı.
- Aktif sayfa vurgusu daha sade hale getirildi.
- Panel logosu tıklanabilir yapıldı ve panel ana sayfasına bağlandı.

# Omurga CMS 1.0.3.15 Beta

## Yazılar Ekranı Kullanılabilirlik Düzenlemesi

- 1.0.3 modern liste tasarımı korundu.
- Yazılar ekranında satır ve işlem alanları ekrandan taşmayacak şekilde düzenlendi.
- Mobilde gelişmiş arama kaldırıldı; sadece küçük arama alanı ve Ara butonu bırakıldı.
- Masaüstünde gelişmiş arama WordPress benzeri kısa ve yatay filtre alanı olarak düzenlendi.
- Toplu İşlemler alanına yayınla, taslağa al, çöpe taşı, kategori değiştir, etiket ekle ve etiket kaldır seçenekleri eklendi.
- Satır işlemlerinde taşmayı azaltmak için ek işlemler üç nokta menüsüne alındı.

# Omurga CMS 1.0.3.15 Beta

- Menü Yönetimi sayfasının boş ekran verme riski giderildi.
- Menü ekranına yetki geriye uyumluluğu, varsayılan menü fallback’i ve hata yakalama eklendi.
- Sayfa/kategori/etiket hazır bağlantı listeleri hata verse bile menü ekranı açılmaya devam eder.

# Omurga CMS 1.0.3.15 Beta

- Güncelleme sisteminde aynı sürüm ve sürüm düşürme blokları kaldırıldı; kullanıcı onayıyla devam eden uyarı akışına çevrildi.
- GitHub release kontrolü `/releases` listesi üzerinden beta/pre-release sürümleri de okuyacak şekilde korundu.
- Sistem Sağlığı ve Güncellemeler ekran ayrımı korundu.

- Aynı sürüm veya düşük sürüm güncelleme paketleri artık engellenmez; kullanıcı onayıyla uygulanır ve işlem loglanır.

## Omurga CMS 1.0.3.15 Beta - Güvenli Güncelleme Sistemi

- Admin paneline `Güncellemeler` ekranı eklendi.
- GitHub Releases üzerinden cache destekli güncelleme kontrolü eklendi.
- Otomatik ve manuel güncelleme aynı güvenli installer akışına bağlandı.
- Güncelleme öncesi veritabanı ve çekirdek dosya yedeği alma sistemi eklendi.
- `config.php`, `uploads`, `storage`, kullanıcı temaları ve kullanıcı paketleri koruma altına alındı.
- Zip güvenlik kontrolleri, sürüm düşürme engeli, bakım modu kilidi ve update log sistemi eklendi.
- Migration dosyalarının tek sefer çalışması için `omurga_migrations` kayıt sistemi hazırlandı.

## v1.0.1 Beta - Block Center

- `admin/blocks.php` Blok Merkezi olarak düzenlendi.
- Varsayılan blokların kaynak etiketi `Çekirdek` yapıldı.
- Blok listesine kaynak, kategori, durum, kullanım alanı, context, ayar ve dosya/render bilgisi eklendi.
- Blok Merkezi dokümanı eklendi: `docs/block-center.md`.

# Changelog

## Omurga CMS 1.0.1 Beta

First public beta release.

### Added

- OMG based default theme: Omurga Kolay.
- PHP based default theme: Omurga Sabit.
- Theme engine validation for `template_engine: omg` and `template_engine: php`.
- Theme safety system: active theme protection, system theme protection and minimum 2-theme rule.
- Block API foundation.
- Package API foundation.
- Hook foundation.
- Shared media picker foundation.
- Form builder and submission management beta.
- One sample post and one sample comment after installation.
- MIT License.
- Bilingual README, roadmap, contribution guide and release notes.

### Cleaned

- Removed old release packages, generated logs and cache files from the release package.
- Simplified the default theme set.
- Moved older development notes to `docs/archive/`.

### Note

This is a beta release. Take backups before production use.

---

## Omurga CMS 1.0.1 Beta

İlk herkese açık beta sürümü.

### Eklenenler

- OMG tabanlı varsayılan tema: Omurga Kolay.
- PHP tabanlı varsayılan tema: Omurga Sabit.
- `template_engine: omg` ve `template_engine: php` için tema motoru doğrulama sistemi.
- Tema güvenlik sistemi: aktif tema koruması, sistem teması koruması ve minimum 2 tema kuralı.
- Blok API altyapısı.
- Paket API altyapısı.
- Hook altyapısı.
- Ortak medya seçici altyapısı.
- Form oluşturucu ve form başvuru yönetimi beta.
- Kurulum sonrası 1 örnek yazı ve 1 örnek yorum.
- MIT lisansı.
- Çift dilli README, yol haritası, katkı rehberi ve yayın notları.

### Temizlenenler

- Eski sürüm paketleri, üretilmiş loglar ve cache dosyaları yayın paketinden çıkarıldı.
- Varsayılan tema seti sadeleştirildi.
- Eski geliştirme notları `docs/archive/` altına taşındı.

### Not

Bu sürüm beta durumundadır. Canlı kullanım öncesi yedek alın.

## 1.0.1 Beta - Developer API Standardı

- `core/DeveloperApi.php` eklendi.
- `Omurga::addAction`, `Omurga::addFilter`, `Omurga::addBlock`, `Omurga::addAdminPage`, `Omurga::addRoute`, `Omurga::addStyle`, `Omurga::addScript`, `Omurga::addThemeDemo` API'leri eklendi.
- Paket admin sayfaları için runtime kayıt desteği eklendi.
- Tema ve paket manifestleri filtrelenebilir hale getirildi.
- Çoklu tema demo standardı eklendi.
- Demo içe aktarmada kullanıcı/rol/sistem/SQL alanları çekirdek seviyesinde yasaklandı.
- Geliştirici dokümanı ve örnek tema/paket eklendi.


## 1.0.1 Beta - Platform API
- Standart Hook/Event listesi eklendi.
- Manifest doğrulama standardı eklendi.
- Paket bağımlılık kontrol API'si eklendi.
- Cron/görev zamanlayıcı API'si eklendi.
- Medya yükleme ve WebP dönüştürme API'si eklendi.
- Omurga Merkezi endpoint API'si eklendi.
- Child theme standardı eklendi.
- Temel Omurga CLI eklendi.

## 1.0.1 Beta - Tema/Paket Standardı ve İzin Sistemi
- Resmi tema klasör standardı eklendi.
- Resmi paket klasör standardı eklendi.
- Paket izin katalog sistemi eklendi.
- Paket yüklerken izin onayı zorunlu hale getirildi.
- Temalarda kullanıcı/rol/veritabanı/sistem/çekirdek izinleri engellendi.
- Yönetim ekranlarına standart uyarıları ve izin açıklamaları eklendi.

## 1.0.1 Beta - Security Health Center

- Güvenlik Merkezi durum paneli eklendi.
- Tema/paket riskli fonksiyon taraması eklendi.
- Sistem Sağlığı kontrolleri genişletildi.
- PHP, veritabanı, disk, cron, cache, SSL, hata günlüğü ve klasör izinleri tek ekranda raporlandı.
- Doküman eklendi: `docs/security-health-center.md`.

## 1.0.1 Beta - Otomatik Kaydetme Sistemi

- Yazı ve sayfa editörlerine otomatik taslak kaydı eklendi.
- `post_autosaves` tablosu eklendi.
- `admin/autosave-api.php` eklendi.
- Otomatik kayıt geri yükleme butonu eklendi.
- Normal kaydetme sonrası otomatik kayıt temizleme eklendi.

## 1.0.1 Beta - Dashboard Sistemi

- Kontrol Paneli ekranı yenilendi.
- Yazı, sayfa, yorum, aktivite ve sistem özeti eklendi.
- Hızlı işlem kartları düzenlendi.
- Sistem sağlığı ve güvenlik merkezi özetleri dashboard'a eklendi.
- Son aktiviteler ve bekleyen yorumlar dashboard'da gösterildi.

## 1.0.3.15 Beta
- Yazı ve sayfa editörüne kaydetmeden kullanılabilen Editör Önizleme penceresi eklendi.
- Üst menüdeki Profilim bağlantısı gerçek profil düzenleme ekranına bağlandı.
- /admin/users.php profil modunda kullanıcı listesi yerine sadece mevcut kullanıcının bilgilerini gösterir.
