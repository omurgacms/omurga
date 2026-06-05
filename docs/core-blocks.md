# Omurga Core Blocks

Omurga cekirdek bloklari genel amacli CMS ihtiyaclari icindir. Haber, belediye,
hava durumu, doviz, nobetci eczane veya tema/profil ozel bloklari cekirdege
eklenmez; bu tip bloklar tema ya da paket tarafindan kaydedilir.

## Kategoriler

### Icerik

- `latest-content`: Son Icerikler
- `popular-content`: Populer Icerikler
- `featured-content`: One Cikan Icerikler
- `html`: HTML
- `html-text`: HTML / Metin uyum blogu
- `text`: Metin
- `heading`: Baslik
- `button`: Buton
- `list`: Liste

### Medya

- `image`: Gorsel
- `gallery`: Galeri
- `video`: Video
- `file-download`: Dosya Indirme

### Navigasyon

- `logo`: Logo
- `menu`: Menu
- `search`: Arama
- `breadcrumb`: Breadcrumb

### Kullanici

- `auth-box`: Giris / Kayit
- `profile-summary`: Profil Ozeti
- `user-menu`: Kullanici Menusu

### Iletisim

- `contact-info`: Iletisim Bilgileri
- `social-links`: Sosyal Medya Linkleri
- `map`: Harita

### Layout

- `spacer`: Bosluk
- `divider`: Ayirici Cizgi
- `layout-row`: Kolon/Satir Yardimci Blok
- `container`: Container

## Block API Uyumu

Tum cekirdek bloklar `core/blocks/{slug}/block.json` ve `view.php` yapisiyla
gelir. Registry su alanlari normalize eder:

- `id`
- `name`
- `description`
- `category`
- `icon`
- `source: core`
- `allowed_contexts`
- `settings_schema`
- `default_settings`
- `view`

Dosya tabanli bloklarda `render_callback` yerine `view.php` kullanilir. Builder
ve render sistemi bunu Block Registry uzerinden aktif blok olarak okur.

## Context Kurallari

- Header Builder yalniz `header` context alanina izin veren bloklari gosterir.
- Footer Builder yalniz `footer` context alanina izin veren bloklari gosterir.
- Sayfa Tasarimcisi `page` ve `sidebar` uyumlu bloklari gosterir.

Ornekler:

- `logo`: `header`, `footer`
- `latest-content`: `page`, `sidebar`
- `html`: `page`, `header`, `footer`, `sidebar`

## Guvenlik

- HTML blogu varsayilan olarak `omurga_block_safe_html()` ile filtrelenir.
- Script calistirma yalniz `settings.manage` yetkisi olan kullanici tarafindan
  aktif edilen blokta mumkundur.
- Eksik veya bozuk bloklar builder'i cokertmez; eksik blok placeholder olarak
  gorunur ve layout verisi korunur.

## Cekirdekte Olmayacak Bloklar

Su bloklar cekirdek kapsamina girmez:

- Manset, TV Manset, Surmanset
- Gazete vitrini
- Nobetci eczane
- Namaz vakti
- Doviz
- Hava durumu
- Belediye baskani
- Meclis kararlari
- Ihale listesi

Bu ihtiyaclar tema veya paket bloklari olarak gelmelidir.
