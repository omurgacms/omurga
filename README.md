# Omurga CMS 1.1.0 Beta

<p align="center">
  <strong>Modern CMS for news, corporate and community websites.</strong><br>
  <strong>Haber, kurumsal ve topluluk web siteleri için modern içerik yönetim sistemi.</strong>
</p>

<p align="center">
  <a href="https://omurgacms.com">Website</a> ·
  <a href="#english">English</a> ·
  <a href="#türkçe">Türkçe</a> ·
  <a href="./CHANGELOG.md">Changelog</a> ·
  <a href="./RELEASE_NOTES.md">Release Notes</a>
</p>

<p align="center">
  <img alt="Version" src="https://img.shields.io/badge/version-1.1.0%20Beta-blue">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green">
  <img alt="Status" src="https://img.shields.io/badge/status-beta-orange">
</p>

---

## English

**Omurga CMS 1.1.0 Beta** is a lightweight, extensible CMS foundation for news, corporate and community websites. It includes a theme system, package foundation, admin panel, front-end reporter panel, SEO Center, RSS/sitemap infrastructure, IndexNow, image SEO and compact admin management screens.

### Main features

- Content management: posts, pages, categories, tags, comments and media.
- Theme system with PHP/OMG theme support.
- Package/plugin foundation.
- Front-end user center at `/hesabim` for profile, own posts and reporter submissions.
- Custom fields with admin and front-end visibility controls.
- SEO Center under Settings:
  - Meta, canonical, robots, Open Graph and Twitter cards.
  - XML sitemap, news sitemap, image/tag/author sitemaps.
  - General RSS, category RSS, Google News RSS and Atom feed.
  - IndexNow and index queue.
  - Redirect manager and 404 tracking.
  - Schema, E-E-A-T checks, theme SEO audit and health report.
- Image SEO: uploaded images can be renamed from the post title and alt text can be filled automatically.
- Compact admin screens for themes, packages, diagnostics, logs and rollback.
- Block Editor and Classic Editor synchronization.

### Default themes

- **Haber V1**: default news theme.
- **Kurumsal V1**: corporate/company theme.
- **Topluluk V1**: community/association theme.

### Installation

1. Upload the package files to your hosting root.
2. Open `/install/` in your browser.
3. Enter database information.
4. Create the site and administrator account.
5. Log in to `/admin/`.
6. After installation, run `/admin/seo-test.php` to check SEO endpoints and writable folders.

### Upgrade from 1.0.8+

- Back up files and database.
- Upload the new package over the existing installation.
- Log in to the admin panel.
- Check `/admin/seo-test.php`.
- Test `/sitemap.xml`, `/feed.xml`, `/atom.xml`, `/google-news.xml` and `/robots.txt`.
- Edit one post and switch between Block Editor and Classic Editor to confirm content synchronization.

---

## Türkçe

**Omurga CMS 1.1.0 Beta**, haber, kurumsal ve topluluk web siteleri için geliştirilen hafif ve genişletilebilir bir CMS temelidir. Tema sistemi, paket altyapısı, admin panel, ön yüz muhabir paneli, SEO Merkezi, RSS/sitemap altyapısı, IndexNow, görsel SEO ve kompakt admin yönetim ekranları içerir.

### Ana özellikler

- İçerik yönetimi: yazılar, sayfalar, kategoriler, etiketler, yorumlar ve medya.
- PHP/OMG tema desteği.
- Paket/eklenti altyapısı.
- `/hesabim` üzerinden ön yüz kullanıcı merkezi, muhabir yazı gönderimi ve kendi yazılarını yönetme.
- Admin ve muhabir formu görünürlüğüne sahip özel alanlar.
- Ayarlar altında SEO Merkezi:
  - Meta, canonical, robots, Open Graph ve Twitter kartları.
  - XML sitemap, news sitemap, image/tag/author sitemap.
  - Genel RSS, kategori RSS, Google News RSS ve Atom feed.
  - IndexNow ve indeks kuyruğu.
  - Yönlendirme merkezi ve 404 izleme.
  - Schema, E-E-A-T kontrolleri, tema SEO denetimi ve sağlık raporu.
- Görsel SEO: yüklenen görseller yazı başlığından adlandırılabilir ve alt metin otomatik doldurulabilir.
- Temalar, paketler, tanılama, işlem kayıtları ve geri dönüş ekranları daha kompakt hale getirildi.
- Blok Editör ve Klasik Editör arasında içerik senkronizasyonu eklendi.

### Varsayılan temalar

- **Haber V1**: varsayılan haber teması.
- **Kurumsal V1**: kurumsal/firma teması.
- **Topluluk V1**: dernek, platform ve topluluk teması.

### Kurulum

1. Paket dosyalarını hosting ana dizinine yükleyin.
2. Tarayıcıdan `/install/` adresini açın.
3. Veritabanı bilgilerini girin.
4. Site ve yönetici hesabını oluşturun.
5. `/admin/` üzerinden giriş yapın.
6. Kurulumdan sonra `/admin/seo-test.php` sayfasını çalıştırarak SEO endpointlerini ve yazılabilir klasörleri kontrol edin.

### 1.0.8+ sürümlerinden yükseltme

- Dosya ve veritabanı yedeği alın.
- Yeni paketi mevcut kurulumun üzerine yükleyin.
- Admin panele giriş yapın.
- `/admin/seo-test.php` kontrolünü çalıştırın.
- `/sitemap.xml`, `/feed.xml`, `/atom.xml`, `/google-news.xml` ve `/robots.txt` adreslerini test edin.
- Bir yazıyı düzenleyip Blok Editör ve Klasik Editör arasında geçiş yaparak içerik senkronizasyonunu kontrol edin.

## Documentation / Dokümantasyon

- `RELEASE_NOTES.md`: bilingual 1.1.0 release notes.
- `CHANGELOG.md`: bilingual changelog from 1.0.8 to 1.1.0.
- `KURULUM.md`: installation notes.
- `docs/`: developer and system documentation.

## License

MIT License.
