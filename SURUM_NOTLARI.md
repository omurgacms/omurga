
# Omurga CMS 1.2.0 RC1

## Stabilizasyon ve Release Candidate Hazırlığı

Omurga CMS 1.2.0 RC1 (`1.2.0-rc.1`) yeni özellik sürümü değildir. Yeni özellikler dondurulmuştur. Bu aday sürüm yalnızca stabilizasyon, hata düzeltme, güvenlik, kurulum uyumluluğu, medya, tema/paket, migration, SEO/API ve admin panel kararlılığına odaklanır.

### RC1 kapsamı

- Tema/paket ZIP güvenlik kontrolündeki yanlış pozitifler düzeltildi. Ters slash, tekrarlı slash ve baştaki `./` yolları normalize edilir.
- Tema ve paket ZIP güvenlik kontrolünde `admin/` klasörü yanlış pozitifleri düzeltildi. Tema içi admin panel dosyaları güvenli şekilde `themes/{slug}/admin/...`, paket içi admin panel dosyaları `packages/{slug}/admin/...` altında kabul edilir.
- `__MACOSX/`, `.DS_Store` ve `Thumbs.db` gibi sistem dosyaları güvenlik hatası sayılmaz; uyarı olarak gösterilir ve yok sayılır.
- Analiz raporunda kontrol edilen dosya sayısı, yok sayılan sistem dosyaları, uyarılar, engellenen dosyalar ve kuruluma izin durumu görünür.
- `../`, mutlak yol, Windows drive path, null byte, symlink, `core/`, `install/`, `config.php`, `bootstrap.php` ve `storage/installed.lock` yazma denemeleri açık dosya adıyla engellenmeye devam eder.
- XAMPP altında `http://localhost/omurga/` gibi alt klasör kurulumlarının kontrolü.
- Kurulum gereksinimleri, `config.php` ve `storage/installed.lock` oluşturma akışı.
- Admin giriş, başarısız giriş kayıtları, hesap güvenliği ve hata logları.
- Yazı ekleme, klasik/blok editör geçişi, taslak/yayın/güncelleme akışı.
- Öne çıkan görsel, medya seçici, hızlı yükleme ve medya işleri kuyruğu.
- Tema/paket ZIP analizi, sürüm karşılaştırması, izin onayı ve yedekli kurulum.
- SEO merkezi, sitemap/RSS/robots endpointleri, migration durumu, sağlık kontrolü ve sistem testleri.

### Dondurulan alanlar

- Yeni büyük özellik eklenmez.
- Büyük refactor yapılmaz.
- `bootstrap.php` bu RC aşamasında parçalanmaz.
- Eski URL yapıları kırılmaz.
- Dağıtım paketine `config.php`, `storage/installed.lock`, eski zipler, log ve cache çıktıları eklenmez.

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


Bu sürüm medya işleme kuyruğu, WebP stabilizasyonu ve büyük görsel yüklemelerinde timeout riskini azaltan altyapı güncellemesini içerir.

## 1.1.5-beta - Medya İşleme Kuyruğu ve WebP Stabilizasyonu

- Medya yükleme sonrası ağır görsel işlemleri için `media_jobs` kuyruğu eklendi.
- WebP dönüşümü, thumbnail üretimi, görsel boyut tespiti, alt metin tamamlama ve image sitemap yenileme işleri kuyruk üzerinden işlenebilir hale getirildi.
- Yeni admin ekranı eklendi: `/admin/media-jobs.php`.
- Medya menüsüne **Medya İşleri** bağlantısı eklendi.
- Büyük görsellerde upload sırasında timeout riskini azaltmak için WebP işlemleri varsayılan olarak kuyruğa alınır.
- Hatalı medya işleri panelden tekrar denenebilir veya tek tek işlenebilir.
- Medya ayarları ve sürüm bilgileri 1.1.5 Beta olarak güncellendi.

# Omurga CMS 1.1.4 Beta

## API, Giriş Güvenliği ve Hata Yönetimi Güncellemesi

- API rate limit altyapısı eklendi.
- `/api/v1/...` sürümleme desteği başlatıldı.
- Başarısız admin giriş denemeleri kayıt altına alındı.
- Geçici giriş kilidi güçlendirildi.
- `admin/login-security.php` ekranı eklendi.
- PHP hata/exception logları güçlendirildi.
- `admin/error-logs.php` ekranı eklendi.
- Temel güvenlik başlıkları eklendi.

---

# Omurga CMS 1.1.4 Beta

## Migration ve Performans Stabilizasyonu

Bu sürüm, 1.1.x güvenlik ve stabilizasyon sürecinin ikinci parçasıdır. Veritabanı güncellemelerinin daha güvenli takip edilmesi ve her istekte ağır tablo kontrollerinin tekrar çalışmaması için merkezi migration runner sistemi eklendi.

- Merkezi migration runner eklendi.
- Migration kayıtları için `migrations` tablosu eklendi.
- Uygulandı / bekliyor / hata durumları takip edilir.
- Eski migration fonksiyonları korunarak yeni sisteme bağlandı.
- SEO, medya, özel alan, üyelik ve sistem migrationları kayıt altına alınır.
- `/admin/migrations.php` sayfası eklendi.
- Sistem menüsüne Migration Durumu bağlantısı eklendi.

---


# Omurga CMS 1.1.4 Beta - Güvenlik ve Stabilizasyon Part 1

Bu sürümde ilk güvenlik sertleştirme adımı uygulandı. Çekirdek koruması production ortamında zorunlu aktif hale getirildi, geliştirici modunun korumayı kapatması engellendi, ZIP çıkarma güvenliği güçlendirildi ve admin oturum güvenliği iyileştirildi.


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
