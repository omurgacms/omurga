# Omurga CMS 1.1.0 Beta Release Notes / Sürüm Notları

## Türkçe

**Omurga CMS 1.1.0 Beta**, 1.0.8 Beta’dan itibaren eklenen düzeltmeleri, admin sadeleştirmelerini, muhabir paneli geliştirmelerini ve gelişmiş SEO altyapısını tek sürüm altında toparlayan büyük beta güncellemesidir.

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
- Sürüm numarası 1.1.0 Beta olarak güncellendi.

### 1.0.8 Beta’dan 1.1.0 Beta’ya değişiklik özeti

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

#### 1.1.0 Beta
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

**Omurga CMS 1.1.0 Beta** is a major beta update that consolidates the improvements added since 1.0.8 Beta, including post panel fixes, theme admin panel support, reporter front-end publishing, compact admin screens, and an advanced SEO foundation.

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
- Version updated to 1.1.0 Beta.

### Changes from 1.0.8 Beta to 1.1.0 Beta

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

#### 1.1.0 Beta
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
