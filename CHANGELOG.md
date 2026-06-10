# Changelog / Değişiklik Günlüğü

## 1.1.0 Beta - Major SEO, Admin and Editor Synchronization Release

### Türkçe

- 1.0.8 Beta’dan 1.0.15.1 Beta’ya kadar yapılan yazı paneli, tema paneli, muhabir paneli, SEO Merkezi, görsel SEO, admin sadeleştirme ve canlı kurulum düzeltmeleri 1.1.0 Beta altında toparlandı.
- Blok editörde eklenen içeriklerin Klasik Editör’e geçince görünmemesi düzeltildi.
- Editör geçişinde blok içerikleri HTML’e çevrilerek klasik editöre aktarılır.
- Klasik editörden blok editöre geçişte içerik temel bloklara dönüştürülür.
- Kaydetme sırasında aktif editör tipine göre hem `content` hem `content_blocks` güvenli şekilde senkronize edilir.
- GitHub için release notları, changelog, güvenlik politikası ve issue/PR şablonları güncellendi.

### English

- Consolidated all post panel, theme panel, reporter panel, SEO Center, image SEO, admin cleanup and live installation fixes from 1.0.8 Beta through 1.0.15.1 Beta into the 1.1.0 Beta release.
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
