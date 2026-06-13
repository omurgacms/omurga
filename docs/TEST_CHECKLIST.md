# Omurga CMS Test Checklist / Test Kontrol Listesi

## Türkçe

### Kurulum ve Güncelleme
- [ ] Temiz kurulum tamamlanıyor.
- [ ] Var olan kurulum 1.1.8-beta paketine güncelleniyor.
- [ ] `/admin/health-check.php` genel sağlık kontrolü çalışıyor.
- [ ] `/admin/system-tests.php` testleri başarılı çalıştırıyor.
- [ ] `/admin/migrations.php` tüm migrationları applied gösteriyor.

### Admin ve İçerik
- [ ] Admin giriş/çıkış çalışıyor.
- [ ] Yeni yazı taslak olarak kaydediliyor.
- [ ] Yazı yayınlama çalışıyor.
- [ ] Blok editör ↔ klasik editör senkronizasyonu çalışıyor.
- [ ] Kategori, etiket ve sayfa yönetimi açılıyor.

### Medya
- [ ] Medyadan seç penceresi açılıyor.
- [ ] Bilgisayardan yükle ile görsel medya kütüphanesine düşüyor.
- [ ] Medya işleri kuyruğu WebP/thumbnail görevlerini gösteriyor.
- [ ] Öne çıkan görsel seçme/kaldırma çalışıyor.

### Tema ve Paket
- [ ] Tema ZIP analiz ekranı geliyor.
- [ ] Paket ZIP analiz ekranı geliyor.
- [ ] Yeni sürüm / aynı sürüm / eski sürüm uyarıları görünüyor.
- [ ] Kurulum öncesi rapor ve izinler gösteriliyor.

### SEO ve API
- [ ] `/sitemap.xml` açılıyor.
- [ ] `/feed.xml` açılıyor.
- [ ] `/atom.xml` açılıyor.
- [ ] `/google-news.xml` açılıyor.
- [ ] `/robots.txt` açılıyor.
- [ ] `/api/v1/status` JSON cevap döndürüyor.

## English

### Installation and Upgrade
- [ ] Fresh installation completes successfully.
- [ ] Existing installation upgrades to 1.1.8-beta.
- [ ] `/admin/health-check.php` works.
- [ ] `/admin/system-tests.php` runs successfully.
- [ ] `/admin/migrations.php` shows migrations as applied.

### Admin and Content
- [ ] Admin login/logout works.
- [ ] New post can be saved as draft.
- [ ] Publishing works.
- [ ] Block editor ↔ classic editor synchronization works.

### Media
- [ ] Media picker opens.
- [ ] Local upload adds files to the media library.
- [ ] Media jobs show WebP/thumbnail tasks.
- [ ] Featured image select/remove works.

### Theme and Package
- [ ] Theme ZIP analysis screen appears.
- [ ] Package ZIP analysis screen appears.
- [ ] New/same/older version warnings are shown.

### SEO and API
- [ ] `/sitemap.xml`, `/feed.xml`, `/atom.xml`, `/google-news.xml`, `/robots.txt` work.
- [ ] `/api/v1/status` returns JSON.
