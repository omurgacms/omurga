## Omurga CMS 1.0.2 Beta - Güvenli Güncelleme Sistemi

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
