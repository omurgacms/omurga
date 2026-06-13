
## 1.2.0 RC1 - Stabilization and Release Candidate Preparation

- Fixed false positive ZIP security errors in theme/package upload analysis without disabling path traversal protection.
- Fixed theme/package `admin/` folder false positives: extension admin panel files are allowed only when the calculated target is `themes/{slug}/admin/...` or `packages/{slug}/admin/...`, never the Omurga root `admin/` folder.
- Added ZIP entry normalization for backslashes, repeated slashes and leading `./` paths.
- Ignored common system files such as `__MACOSX/`, `.DS_Store` and `Thumbs.db` with warnings instead of blocking installation.
- ZIP analysis now reports checked file count, ignored system files, warnings, blocked files and whether installation is allowed.
- Unsafe paths still blocked with explicit entry names: `../`, absolute paths, Windows drive paths, null bytes, symlinks, `core/`, `install/`, `config.php`, `bootstrap.php` and `storage/installed.lock`.
- This is not a new feature release; feature work is frozen for RC1.
- Updated technical version to `1.2.0-rc.1` and visible release name to Omurga CMS 1.2.0 RC1.
- Hardened admin compatibility helpers used by older screens: `require_capability`, `csrf_check`, `csrf_input` and missing `mb_strtoupper` fallback.
- Tightened REST API settings POST handling with the standard CSRF verifier.
- Reduced duplicate filesystem/database checks in Health Check and System Tests.
- Revalidated installer, admin, editor, media, theme/package, SEO, migration, health, system test, API and security flows for XAMPP subfolder installs.
- Release package rules remain strict: do not include `config.php`, `storage/installed.lock`, logs, cache files or old zip packages.

## 1.1.8 Beta - Testing, Installer & Health Check Update

- Added `/admin/health-check.php` for post-install server, permissions, migration, security and endpoint checks.
- Added `/admin/system-tests.php` for safe non-destructive admin tests.
- Added CLI tools: `tools/health-check.php` and `tools/run-tests.php`.
- Improved installer requirements with ZipArchive, storage/cache, storage/logs, packages and themes write checks.
- Added `docs/TEST_CHECKLIST.md` in Turkish and English.
- Added System menu entries for Health Check and System Tests.

## 1.1.8-beta - Theme & Package Upload Experience + Security Update

- Tema yükleme sayfası üç aşamalı hale getirildi: ZIP seç, analiz et, kurulumu onayla.
- Paket yükleme sayfası üç aşamalı hale getirildi: ZIP seç, manifest/izinleri analiz et, kurulumu onayla.
- Büyük tema/paket yüklemelerinde sayfanın boş kalmaması için yükleniyor bilgilendirme katmanı eklendi.
- Yükleme sırasında butonlar kilitlenir, çift tıklama ve tekrar gönderim riski azaltılır.
- Kurulum öncesi sürüm karşılaştırma raporu eklendi: yeni sürüm, aynı sürüm üzerine yazma, eski sürüme dönme.
- Kurulu sürüm ve yüklenen sürüm bilgileri açıkça gösterilir.
- Aynı slug varsa kurulumdan önce mevcut sürüm otomatik yedeklenir.
- Tema/paket güvenlik taraması, standart uyarıları, manifest izinleri ve uyumluluk notları kurulum öncesi gösterilir.
- Paket izinleri onaylanmadan kurulum yapılamaz.

### English

- Theme upload page now uses a three-step flow: choose ZIP, analyze, confirm installation.
- Package upload page now uses a three-step flow: choose ZIP, analyze manifest/permissions, confirm installation.
- Added a loading overlay so large theme/package uploads no longer leave users with a blank page.
- Upload buttons are locked during processing to reduce duplicate submissions.
- Added pre-install version comparison report: upgrade, overwrite same version, or downgrade.
- Installed and uploaded versions are shown clearly before installation.
- Existing theme/package folders are backed up before overwrite/update/downgrade operations.
- Security scan notes, standard warnings, manifest permissions and compatibility checks are shown before installation.
- Package permissions must be accepted before installation.



## 1.1.8-beta - Password Reset & Account Security Update

- Şifre sıfırlama akışı hashlenmiş, tek kullanımlık `password_resets` tablosuna taşındı.
- Admin giriş ekranındaki Şifremi Unuttum akışı süreli token ve güvenli kayıt sistemiyle yenilendi.
- Hesap Güvenliği ekranı eklendi: şifre değiştirme, son giriş, giriş denemeleri ve reset kayıtları.
- Şifre politikası güçlendirildi: minimum uzunluk, zayıf şifre uyarısı ve eski şifreyle aynı olmama kontrolü.
- 2FA için kullanıcı alanları hazırlandı: `two_factor_enabled`, `two_factor_secret`, `two_factor_recovery_codes`.
- Account security migration ve kurulum şeması güncellendi.

### English

- Password reset flow now uses hashed, single-use tokens stored in the `password_resets` table.
- Admin forgot-password flow was rebuilt with expiring reset tokens and secure logging.
- Added Account Security screen for password changes, last login, login attempts and reset history.
- Strengthened password policy: minimum length, weak password warning and old-password reuse check.
- Prepared 2FA user fields: `two_factor_enabled`, `two_factor_secret`, `two_factor_recovery_codes`.
- Updated account security migration and installer schema.

## 1.1.5-beta - Medya İşleme Kuyruğu ve WebP Stabilizasyonu

- Medya yükleme sonrası ağır görsel işlemleri için `media_jobs` kuyruğu eklendi.
- WebP dönüşümü, thumbnail üretimi, görsel boyut tespiti, alt metin tamamlama ve image sitemap yenileme işleri kuyruk üzerinden işlenebilir hale getirildi.
- Yeni admin ekranı eklendi: `/admin/media-jobs.php`.
- Medya menüsüne **Medya İşleri** bağlantısı eklendi.
- Büyük görsellerde upload sırasında timeout riskini azaltmak için WebP işlemleri varsayılan olarak kuyruğa alınır.
- Hatalı medya işleri panelden tekrar denenebilir veya tek tek işlenebilir.
- Medya ayarları ve sürüm bilgileri 1.1.5 Beta olarak güncellendi.

## 1.1.4-beta - API, Login Security & Error Handling Update

- API istekleri için saatlik rate limit altyapısı eklendi.
- `/api/v1/...` sürümleme desteği başlatıldı; eski `/api/...` yolları geriye uyumlu kaldı.
- API CORS ayarları ve standart JSON hata formatı güçlendirildi.
- Admin girişinde IP/kullanıcı bazlı başarısız deneme kaydı ve geçici kilit sistemi eklendi.
- `admin/login-security.php` ile giriş denemeleri izlenebilir hale getirildi.
- PHP hata/exception logları iyileştirildi ve `admin/error-logs.php` ekranı eklendi.
- Temel güvenlik headerları eklendi.
- Migration kataloguna `security_114_api_login` eklendi.

# Changelog / Değişiklik Günlüğü

## 1.1.4-beta - Migration & Performance Stability Update

- Migration runner altyapısı eklendi.
- `migrations` tablosu ile çalışan/bekleyen/hatalı migration kayıtları takip edilir.
- Eski migration fonksiyonları korunarak tek seferlik runner sistemine bağlandı.
- `omurga_migrate()` artık sürüm ve migration durumu güncelse ağır kontrolleri tekrar çalıştırmaz.
- SEO redirect, 404 ve indeks kuyruğu tarafındaki migration çağrıları merkezi migration kontrolüne yönlendirildi.
- Admin panelde `/admin/migrations.php` Migration Durumu sayfası eklendi.
- Sistem menüsüne Migration Durumu bağlantısı eklendi.
- Sistem Sağlığı ekranındaki migration çalıştırma butonu zorunlu kontrol moduna bağlandı.
- Dokümantasyona Part 2 notları eklendi.

## 1.1.2-beta - Security & Stability Part 1

- Core Guard artık production ortamında developer mode ile devre dışı kalmaz.
- Developer mode production ortamında sadece pasif/uyarı durumunda kalır; koruma kapanmaz.
- ZIP çıkarma sistemi manuel ve güvenli akışa alındı; path traversal ve symlink girişimleri engellenir.
- Admin oturumları için strict session, HTTPOnly, Secure, SameSite=Lax, timeout ve login sonrası session yenileme güçlendirildi.
- Güvenlik engellemeleri storage/logs/security.log dosyasına yazılır.
- Güvenlik Merkezi metinleri yeni güvenlik mantığına göre güncellendi.

## 1.1.1 Beta - Major SEO, Admin and Editor Synchronization Release

### Türkçe

- 1.0.8 Beta’dan 1.0.15.1 Beta’ya kadar yapılan yazı paneli, tema paneli, muhabir paneli, SEO Merkezi, görsel SEO, admin sadeleştirme ve canlı kurulum düzeltmeleri 1.1.1 Beta altında toparlandı.
- Öne çıkan görsel ve medya kütüphanesi yükleme akışı sağlamlaştırıldı.
- Medya modalındaki “Bilgisayardan yükle” alanı artık başlık ipucu, CSRF, WebP, hata mesajı ve otomatik seçim akışıyla daha güvenli çalışır.
- Upload hataları artık sessizce “0 dosya yüklendi” olarak kalmaz; izin, boyut, tür ve geçici klasör hataları kullanıcıya gösterilir.
- Blok editörde eklenen içeriklerin Klasik Editör’e geçince görünmemesi düzeltildi.
- Editör geçişinde blok içerikleri HTML’e çevrilerek klasik editöre aktarılır.
- Klasik editörden blok editöre geçişte içerik temel bloklara dönüştürülür.
- Kaydetme sırasında aktif editör tipine göre hem `content` hem `content_blocks` güvenli şekilde senkronize edilir.
- GitHub için release notları, changelog, güvenlik politikası ve issue/PR şablonları güncellendi.

### English

- Consolidated all post panel, theme panel, reporter panel, SEO Center, image SEO, admin cleanup and live installation fixes from 1.0.8 Beta through 1.0.15.1 Beta into the 1.1.1 Beta release.
- Hardened featured image and media library upload flows.
- The media modal “Upload from computer” action now handles title hints, CSRF, WebP conversion, clear errors and automatic selection more reliably.
- Upload failures no longer silently appear as “0 files uploaded”; permission, size, type and temporary folder errors are reported clearly.
- Fixed block editor content not appearing when switching to Classic Editor.
- Block content is converted into HTML when switching to Classic Editor.
- Classic content can be converted back into basic blocks when switching to Block Editor.
- On save, `content` and `content_blocks` are synchronized safely based on the active editor mode.
- Updated GitHub release notes, changelog, security policy and issue/PR templates.

## 1.0.15.1 Beta - Live Installation SEO Fix

### Türkçe
- Türkçe karakter bozulmaları düzeltildi.
- SEO endpointleri için korunan slug listesi genişletildi.
- Boş veya sistem klasörlerini hedefleyen güvensiz yönlendirmeler engellendi.
- `/admin/seo-test.php` canlı kurulum kontrol sayfası eklendi.
- `storage/cache`, `storage/logs` ve `uploads` klasörleri dağıtıma güvenli şekilde dahil edildi.

### English
- Fixed corrupted Turkish text in fallback pages.
- Expanded reserved slug protection for SEO endpoints.
- Blocked unsafe redirect rules targeting empty paths or system folders.
- Added `/admin/seo-test.php` for live installation checks.
- Included `storage/cache`, `storage/logs` and `uploads` folders safely in the distribution.

## 1.0.15 Beta - SEO Completion Update

### Türkçe
- Gelişmiş schema sistemi eklendi: Organization, WebSite, SearchAction, BreadcrumbList, Article, NewsArticle, WebPage, CollectionPage ve ImageObject.
- Open Graph ve Twitter kartları genişletildi.
- `/sitemap-tags.xml`, `/sitemap-images.xml`, `/sitemap-authors.xml` ve `/atom.xml` eklendi.
- Etiket ve yazar arşivleri eklendi.
- Yönlendirme merkezi ve 404 izleme eklendi.
- E-E-A-T kontrolleri, tema SEO denetimi ve SEO sağlık raporu eklendi.

### English
- Added advanced schema support: Organization, WebSite, SearchAction, BreadcrumbList, Article, NewsArticle, WebPage, CollectionPage and ImageObject.
- Expanded Open Graph and Twitter card metadata.
- Added `/sitemap-tags.xml`, `/sitemap-images.xml`, `/sitemap-authors.xml` and `/atom.xml`.
- Added tag and author archive routes.
- Added redirect manager and 404 tracking.
- Added E-E-A-T checks, theme SEO audit and SEO health report.

## 1.0.14 Beta - Image SEO Update

### Türkçe
- Görsellerin yazı başlığına göre SEO uyumlu adlandırılması eklendi.
- Alt metin otomatik doldurma eklendi.
- Orijinal dosya adı medya kaydında saklanır.
- Admin yazı ekranı, muhabir paneli ve medya yükleme akışına bağlandı.

### English
- Added SEO-friendly image renaming from the post title.
- Added automatic alt text filling.
- Original filenames are preserved in media records.
- Connected to admin post editing, reporter panel and media upload flows.

## 1.0.13 Beta - Admin Menu Centers

### Türkçe
- Kullanıcılar, Roller ve Yetkiler Kullanıcı Yönetimi altında toplandı.
- SEO Merkezi sekmeli hale getirildi.
- Performans / Cache sayfasına güvenli sistem temizliği eklendi.

### English
- Grouped Users, Roles and Permissions under User Management.
- Added tabs to the SEO Center.
- Added safe cleanup tools to Performance / Cache.

## 1.0.12 Beta - Compact Menu and Maintenance

### Türkçe
- SEO Merkezi Ayarlar menüsüne taşındı.
- Performans ve Cache tek sayfada birleştirildi.
- İşlem kayıtları ve geri dön/rollback sayfaları kompakt hale getirildi.

### English
- Moved SEO Center under Settings.
- Merged Performance and Cache into one page.
- Compacted activity logs and rollback pages.

## 1.0.11 Beta - Compact Admin Lists

### Türkçe
- Temalar, Paketler ve Tanılama sayfaları kompakt liste görünümüne alındı.
- Liste/Kart görünüm anahtarı eklendi.
- Mobil görünüm toparlandı.

### English
- Added compact list views for Themes, Packages and Diagnostics.
- Added List/Card view switch.
- Improved mobile admin layout.

## 1.0.10 Beta - SEO Center, RSS and IndexNow

### Türkçe
- Genel RSS ve kategori bazlı RSS eklendi.
- Google News RSS eklendi.
- IndexNow ve indeks kuyruğu eklendi.
- SEO kalite kontrolleri genişletildi.

### English
- Added general RSS and category RSS feeds.
- Added Google News RSS.
- Added IndexNow and index queue.
- Expanded SEO quality checks.

## 1.0.9 Beta - Reporter Panel and Custom Fields

### Türkçe
- `/hesabim` muhabir paneli geliştirildi.
- Ön yüzden haber gönderme formu genişletildi.
- Muhabire açık özel alanlar formda görünür ve kaydedilir hale getirildi.

### English
- Improved the `/hesabim` reporter panel.
- Expanded front-end post submission.
- Added permission-based custom fields for reporter forms.

## 1.0.8 Beta - Post Panel and Theme Admin Menu Fixes

### Türkçe
- Yazılar menüsünde mevcut yazıların görünmemesi düzeltildi.
- Yayınlama sırasında autosave silme hatası düzeltildi.
- Aktif temanın kendi panelinin admin menüde görünmesi sağlandı.

### English
- Fixed existing posts not showing in the Posts menu.
- Fixed autosave deletion error during publishing.
- Added active theme admin panel visibility in the admin menu.
