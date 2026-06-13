
# Omurga CMS 1.2.0 RC1

## Stabilization and Release Candidate Preparation

Omurga CMS 1.2.0 RC1 (`1.2.0-rc.1`) is not a new feature release. New features are frozen. This release candidate is focused on stabilization, error fixes, security behavior, installation compatibility, media reliability, theme/package upload safety, migration stability and admin panel readiness.

### RC1 scope

- Tema/paket ZIP güvenlik kontrolünde yanlış pozitifler düzeltildi: Windows ters slash yolları, tekrarlı slash, baştaki `./` ve macOS/Windows sistem dosyaları doğru normalize edilir.
- `__MACOSX/`, `.DS_Store` ve `Thumbs.db` gibi gereksiz sistem dosyaları kurulum hatası yerine uyarı olarak raporlanır ve yok sayılır.
- ZIP analiz raporu artık kontrol edilen dosya sayısını, yok sayılan sistem dosyalarını, uyarıları, engellenen dosyaları ve kuruluma izin durumunu gösterir.
- Path traversal, mutlak yol, Windows drive yolu, null byte, symlink ve çekirdek dosya/klasör yazma denemeleri açık dosya adıyla engellenmeye devam eder.
- Real-user testing on XAMPP subfolder installs such as `http://localhost/omurga/`.
- Installer requirements and writable folder checks.
- Admin login, login attempt logging and account/security screens.
- Content editor switching between Classic Editor and Block Editor.
- Media picker upload flow, featured image selection and media job queue.
- Theme and package ZIP analysis, version comparison, permission review and backup-before-overwrite behavior.
- SEO Center and public endpoint generation under subfolder installs.
- Migration status, Health Check, System Tests, Diagnostics, API status and error logs.

### Release freeze

- No new large features.
- No large refactor.
- `bootstrap.php` is intentionally not split in this RC.
- Existing URLs and working theme behavior must remain compatible.
- Distribution packages must not include `config.php`, `storage/installed.lock`, old zip packages, logs or cache outputs.

## 1.1.8 Beta - Testing, Installer & Health Check Update

- Added `/admin/health-check.php` for post-install server, permissions, migration, security and endpoint checks.
- Added `/admin/system-tests.php` for safe non-destructive admin tests.
- Added CLI tools: `tools/health-check.php` and `tools/run-tests.php`.
- Improved installer requirements with ZipArchive, storage/cache, storage/logs, packages and themes write checks.
- Added `docs/TEST_CHECKLIST.md` in Turkish and English.
- Added System menu entries for Health Check and System Tests.

# Omurga CMS 1.1.8 Beta

## Theme & Package Upload Experience + Security Update

Bu sürüm tema ve paket yükleme deneyimini daha güvenli, daha açıklayıcı ve daha kullanıcı dostu hale getirir. Büyük ZIP yüklemelerinde kullanıcıya yükleme/analiz/kurulum durumu gösterilir. Kurulumdan önce sürüm karşılaştırması, güvenlik taraması, manifest izinleri ve uyumluluk raporu görüntülenir.

This release improves the theme and package upload experience with safer, clearer and more user-friendly installation flow. Large ZIP uploads now show upload/analyze/install feedback. Before installation, Omurga shows version comparison, security scan notes, manifest permissions and compatibility report.

### Öne çıkanlar / Highlights

- Üç aşamalı tema/paket yükleme: ZIP seç → analiz → onayla.
- Büyük dosyalarda “Yükleniyor...” bilgilendirmesi.
- Yeni sürüm / aynı sürüm / eski sürüm karşılaştırması.
- Aynı sürümde açık “Üzerine Yaz” akışı.
- Yeni sürümde açık “sürüme güncelle” akışı.
- Eski sürümde açık “Eski sürüme dön” uyarısı.
- Kurulum öncesi otomatik yedek bilgilendirmesi.
- Paket izinlerini kurulumdan önce gösterme ve onaylatma.
- Tema/paket güvenlik ve standart raporu.

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

# Omurga CMS 1.1.5 Beta

Media Processing & WebP Queue Update.

## 1.1.5-beta - Media Processing Queue and WebP Stabilization

- Added `media_jobs` queue for heavy post-upload media processing.
- WebP conversion, thumbnail generation, image size detection, alt text filling and image sitemap refresh can now run through the queue.
- Added a new admin screen: `/admin/media-jobs.php`.
- Added **Media Jobs** item under the Media menu.
- WebP processing is queued by default to reduce timeout risk on large images.
- Failed media jobs can be retried or processed manually from the admin panel.
- Media settings and version references were updated for 1.1.5 Beta.

# Omurga CMS 1.1.4 Beta

## API, Login Security & Error Handling Update

Bu sürüm Omurga CMS çekirdeğinde API güvenliği, panel giriş koruması ve hata loglama tarafını güçlendirir.

### Öne çıkanlar

- API rate limit altyapısı eklendi.
- `/api/v1/...` API sürümleme başlangıcı yapıldı.
- CORS başlıkları ayarlanabilir hale getirildi.
- Başarısız giriş denemeleri kayıt altına alındı.
- 5 başarısız giriş sonrası geçici kilit davranışı eklendi.
- Admin panelde Giriş Güvenliği ekranı eklendi.
- Hata ve exception kayıtları daha düzenli loglanır hale geldi.
- Admin panelde Hata Kayıtları ekranı eklendi.
- Temel security headers eklendi.

### Test önerileri

- `/admin/login.php` üzerinde başarısız giriş denemelerini test edin.
- `/admin/login-security.php` ekranında kayıtların göründüğünü kontrol edin.
- `/admin/error-logs.php` ekranını kontrol edin.
- `/api/v1/status` ve eski `/api/status` adreslerini test edin.

---

# Omurga CMS 1.1.4 Beta

## Migration & Performance Stability Update

This release introduces the second stabilization part for the 1.1.x series. It adds a central migration runner, migration status tracking and a compact admin page for database update status. The goal is to prevent repeated heavy table checks on every request and make live upgrades safer.

### Türkçe

- Merkezi Migration Runner eklendi.
- `migrations` tablosu ile uygulanan, bekleyen ve hatalı migration kayıtları izlenebilir.
- Eski migration fonksiyonları bozulmadan yeni runner sistemine bağlandı.
- `omurga_migrate()` güncel sürümde tekrar tekrar ağır tablo kontrolleri yapmaz.
- SEO redirect, 404 log ve IndexNow kuyruk migration çağrıları merkezi sisteme yönlendirildi.
- Yeni admin sayfası: `/admin/migrations.php`
- Sistem menüsüne “Migration Durumu” bağlantısı eklendi.

### English

- Added a central Migration Runner.
- Added a `migrations` table to track applied, pending and failed migrations.
- Existing migration functions remain backward-compatible and are now wrapped by the runner.
- `omurga_migrate()` skips repeated heavy table checks when the database is already up to date.
- SEO redirect, 404 log and IndexNow queue migration checks now use the central migration flow.
- New admin page: `/admin/migrations.php`
- Added “Migration Status” to the System menu.

---


# Omurga CMS 1.1.4 Beta - Security & Stability Part 1

This release focuses on the first security hardening pass after the 1.1.0/1.1.1 Beta series.

## Türkçe

- Core Guard üretim ortamında zorunlu aktif hale getirildi.
- Developer mode artık production ortamında çekirdek korumasını kapatamaz.
- ZIP çıkarma işlemi path traversal ve symlink risklerine karşı sertleştirildi.
- Oturum güvenliği güçlendirildi: strict mode, HTTPOnly, Secure, SameSite=Lax, 30 dakika timeout ve login sonrası session yenileme.
- Engellenen çekirdek yazma/silme denemeleri `storage/logs/security.log` dosyasına kaydedilir.
- Güvenlik Merkezi yeni davranışa göre güncellendi.

## English

- Core Guard is now mandatory in production.
- Developer mode can no longer disable core protection in production.
- ZIP extraction was hardened against path traversal and symlink based attacks.
- Session security was improved with strict mode, HTTPOnly, Secure, SameSite=Lax, 30-minute timeout and session regeneration after login.
- Blocked core write/delete attempts are logged to `storage/logs/security.log`.
- Security Center copy was updated to match the new security behavior.


# Omurga CMS 1.1.1 Beta Release Notes / Sürüm Notları

## Türkçe

**Omurga CMS 1.1.1 Beta**, 1.0.8 Beta’dan itibaren eklenen düzeltmeleri, admin sadeleştirmelerini, muhabir paneli geliştirmelerini ve gelişmiş SEO altyapısını tek sürüm altında toparlayan büyük beta güncellemesidir.

### Öne çıkanlar

- Yazı paneli ve tema paneli görünürlüğü düzeltildi.
- Muhabirler için `/hesabim` üzerinden admin panele girmeden yazı gönderme, kendi yazılarını görme ve düzenleme akışı geliştirildi.
- Özel alanların muhabir formunda izinli şekilde görünmesi sağlandı.
- SEO Merkezi; sitemap, RSS, kategori feed, Google News RSS, IndexNow, indeks kuyruğu, schema, robots, yönlendirme, 404 izleme, E-E-A-T ve sağlık raporu bölümleriyle genişletildi.
- Görsel SEO eklendi: yüklenen görseller yazı başlığına göre SEO uyumlu adlandırılır, alt metin otomatik doldurulur ve orijinal dosya adı saklanır.
- Genel RSS, kategori RSS, Google News RSS ve Atom feed eklendi.
- Tags, authors ve image sitemap endpointleri eklendi.
- Admin menü sadeleştirildi: SEO Merkezi Ayarlar altında, Performans/Cache/Temizlik tek ekranda, Kullanıcı Yönetimi tek merkezde toplandı.
- Tema, paket, tanılama, işlem kayıtları ve geri dönüş ekranları daha kompakt hale getirildi.
- Blok editörden klasik editöre geçişte blok içeriklerinin görünmemesi sorunu giderildi; editör geçişlerinde içerik iki yönde senkronize edilir.
- Canlı kurulum öncesi SEO test sayfası eklendi: `/admin/seo-test.php`.
- Sürüm numarası 1.1.1 Beta olarak güncellendi.

### 1.0.8 Beta’dan 1.1.1 Beta’ya değişiklik özeti

#### 1.0.8 Beta
- Yazılar menüsünde mevcut yazıların görünmemesi düzeltildi.
- Yazı yayınlarken autosave silme hatası düzeltildi.
- Aktif temaların kendi panel sayfalarının admin menüde görünmesi sağlandı.

#### 1.0.9 Beta
- Muhabir paneli geliştirildi.
- Ön yüzden yazı gönderme formu kategori, görsel, galeri, video ve özel alan desteğiyle genişletildi.
- Muhabire açık özel alanların `/hesabim` formunda görünmesi ve kaydedilmesi sağlandı.

#### 1.0.10 Beta
- SEO Merkezi altyapısı genişletildi.
- Genel RSS, kategori RSS, Google News RSS, IndexNow ve indeks kuyruğu eklendi.

#### 1.0.11 Beta
- Tema, paket ve tanılama ekranları kompakt liste/kart görünümüyle düzenlendi.
- Mobilde daha toplu yönetim ekranları eklendi.

#### 1.0.12 Beta
- SEO Merkezi Ayarlar menüsü altına alındı.
- Performans ve Cache tek sayfada birleştirildi.
- İşlem kayıtları ve geri dönüş sayfaları kompaktlaştırıldı.

#### 1.0.13 Beta
- Kullanıcılar, Roller ve Yetkiler Kullanıcı Yönetimi altında toparlandı.
- SEO Merkezi sekmeli hale getirildi.
- Performans/Cache sayfasına güvenli sistem temizliği eklendi.

#### 1.0.14 Beta
- Görsel SEO eklendi.
- Dosya adı yazı başlığına göre oluşturulur.
- Alt metin otomatik doldurulur.
- Orijinal dosya adı korunur.
- Muhabir paneli, admin yazı ekranı ve medya yükleme akışına bağlandı.

#### 1.0.15 Beta
- Gelişmiş schema, Open Graph ve Twitter kartları geliştirildi.
- Tags, authors, images sitemap ve Atom feed eklendi.
- Yönlendirme merkezi ve 404 izleme eklendi.
- E-E-A-T, tema SEO denetimi ve SEO sağlık raporu eklendi.

#### 1.0.15.1 Beta
- Canlı kurulum öncesi SEO riskleri düzeltildi.
- Korunan slug listesi genişletildi.
- Hatalı yönlendirme eklenmesi engellendi.
- `/admin/seo-test.php` eklendi.
- Storage/cache/logs/uploads klasörleri dağıtım paketine güvenli şekilde dahil edildi.

#### 1.1.1 Beta
- 1.0.8 sonrası güncellemeler tek ana beta sürümde toparlandı.
- Blok editör ↔ klasik editör geçiş senkronizasyonu düzeltildi.
- GitHub release, changelog, güvenlik ve katkı dosyaları güncellendi.

### Yükseltme notları

1. Dosyaları yedekleyin.
2. Veritabanı yedeği alın.
3. Paketi mevcut kurulumun üzerine yükleyin.
4. `/admin/seo-test.php` sayfasını çalıştırın.
5. `/admin/seo.php`, `/sitemap.xml`, `/feed.xml`, `/atom.xml`, `/google-news.xml` ve `/robots.txt` adreslerini kontrol edin.
6. Bir yazıda blok editör ve klasik editör arasında geçiş yaparak içerik senkronizasyonunu test edin.

---

## English

**Omurga CMS 1.1.1 Beta** is a major beta update that consolidates the improvements added since 1.0.8 Beta, including post panel fixes, theme admin panel support, reporter front-end publishing, compact admin screens, and an advanced SEO foundation.

### Highlights

- Fixed post list visibility and autosave deletion issues in the admin panel.
- Added active theme admin panel detection and menu integration.
- Improved the front-end reporter panel at `/hesabim` with post submission, own post management, media, gallery, video and custom field support.
- Expanded the SEO Center with sitemap, RSS, category feeds, Google News RSS, IndexNow, index queue, schema, robots, redirects, 404 tracking, E-E-A-T checks and health reports.
- Added Image SEO: uploaded images can be renamed from the post title, alt text can be filled automatically, and original filenames are preserved.
- Added general RSS, category RSS, Google News RSS and Atom feed endpoints.
- Added tag, author and image sitemaps.
- Simplified admin navigation: SEO Center is under Settings, Performance/Cache/Cleanup is merged, and Users/Roles/Permissions are grouped under User Management.
- Compact list-style screens were added for themes, packages, diagnostics, logs and rollback pages.
- Fixed block editor to classic editor switching: block content is now synchronized into the classic editor, and classic content can be converted back into blocks.
- Added a live installation SEO test page: `/admin/seo-test.php`.
- Version updated to 1.1.1 Beta.

### Changes from 1.0.8 Beta to 1.1.1 Beta

#### 1.0.8 Beta
- Fixed missing posts in the Posts admin menu.
- Fixed autosave deletion error during publishing.
- Added active theme admin panel visibility in the admin menu.

#### 1.0.9 Beta
- Improved the reporter panel.
- Expanded front-end post submission with categories, images, galleries, video and custom fields.
- Added permission-based custom field visibility for reporter forms.

#### 1.0.10 Beta
- Expanded the SEO Center foundation.
- Added general RSS, category RSS, Google News RSS, IndexNow and index queue.

#### 1.0.11 Beta
- Added compact list/card views for themes, packages and diagnostics.
- Improved mobile admin usability.

#### 1.0.12 Beta
- Moved SEO Center under Settings.
- Merged Performance and Cache into one page.
- Compacted logs and rollback pages.

#### 1.0.13 Beta
- Grouped Users, Roles and Permissions under User Management.
- Added tabs to SEO Center.
- Added safe cleanup tools to Performance/Cache.

#### 1.0.14 Beta
- Added Image SEO.
- Renames uploaded files from the post title.
- Automatically fills alt text when empty.
- Preserves original filenames.
- Connected to admin posts, reporter panel and media uploads.

#### 1.0.15 Beta
- Improved structured data, Open Graph and Twitter cards.
- Added tag, author and image sitemaps and Atom feed.
- Added redirect manager and 404 tracking.
- Added E-E-A-T checks, theme SEO audit and SEO health report.

#### 1.0.15.1 Beta
- Fixed live installation SEO risks.
- Expanded reserved slug protection.
- Prevented unsafe redirect rules.
- Added `/admin/seo-test.php`.
- Included storage/cache/logs/uploads folders safely in the distribution.

#### 1.1.1 Beta
- Consolidated the post-1.0.8 improvements into one major beta release.
- Fixed block editor ↔ classic editor synchronization.
- Updated GitHub release, changelog, security and contribution files.

### Upgrade notes

1. Back up your files.
2. Back up your database.
3. Upload the package over your existing installation.
4. Run `/admin/seo-test.php`.
5. Check `/admin/seo.php`, `/sitemap.xml`, `/feed.xml`, `/atom.xml`, `/google-news.xml` and `/robots.txt`.
6. Edit a post and switch between Block Editor and Classic Editor to confirm content synchronization.
