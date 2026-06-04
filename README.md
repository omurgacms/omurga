# Omurga CMS

<p align="center">
  <strong>Modern CMS for news, municipality and corporate websites.</strong><br>
  <strong>Haber, belediye ve kurumsal web siteleri için modern içerik yönetim sistemi.</strong>
</p>

<p align="center">
  <a href="https://omurgacms.com">Website</a> ·
  <a href="#english">English</a> ·
  <a href="#türkçe">Türkçe</a> ·
  <a href="./ROADMAP.md">Roadmap</a> ·
  <a href="./CONTRIBUTING.md">Contributing</a>
</p>

<p align="center">
  <img alt="Version" src="https://img.shields.io/badge/version-1.0%20Beta-blue">
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green">
  <img alt="Status" src="https://img.shields.io/badge/status-beta-orange">
</p>

---

## English

**Omurga CMS 1.0 Beta** is an early beta release of a simple, extensible content management system designed for news, municipality, corporate and community websites.

> Status: **Beta**  
> Website: **https://omurgacms.com**

### Highlights

| Area | Status |
|---|---|
| OMG template themes | Ready |
| PHP themes | Ready |
| Block system | Ready |
| Package foundation | Ready |
| Hook foundation | Ready |
| Media library | In progress |
| Form builder | Beta |
| Theme safety system | Ready |

### Default themes

Omurga CMS 1.0 Beta ships with two simple default themes:

#### Omurga Kolay

- OMG-based theme.
- A simple starter theme for users who want to build themes without PHP.
- Uses files such as `home.omg`, `single.omg`, `page.omg`, `category.omg`.

#### Omurga Sabit

- PHP-based theme.
- A simple starter theme for classic PHP theme development.
- Uses files such as `home.php`, `single.php`, `page.php`, `category.php`.

For safety, Omurga keeps at least two themes installed. Active themes and system themes cannot be deleted from the admin panel.

### Installation

1. Upload the files to your server.
2. Open `/install/` in your browser.
3. Enter your database information.
4. Create the site and administrator account.
5. Log in to `/admin/`.

After installation, the site is not empty. Omurga creates one sample post and one sample comment.

### Theme structure

OMG theme:

```text
themes/theme-name/
├─ theme.json
├─ home.omg
├─ single.omg
├─ page.omg
├─ category.omg
├─ header.omg
├─ footer.omg
└─ assets/
```

PHP theme:

```text
themes/theme-name/
├─ theme.json
├─ home.php
├─ single.php
├─ page.php
├─ category.php
├─ header.php
├─ footer.php
└─ assets/
```

### Documentation

Developer documentation is available in the `docs/` directory:

- `docs/theme-api.md`
- `docs/package-api.md`
- `docs/block-api.md`
- `docs/hooks.md`
- `docs/omg-template.md`
- `docs/media-library.md`

### License

Omurga CMS is released under the MIT License. See `LICENSE`.

---

## Türkçe

**Omurga CMS 1.0 Beta**, haber, belediye, kurumsal ve topluluk web siteleri için geliştirilen sade ve genişletilebilir bir içerik yönetim sisteminin ilk beta sürümüdür.

> Durum: **Beta**  
> Proje sitesi: **https://omurgacms.com**

### Öne çıkanlar

| Alan | Durum |
|---|---|
| OMG tema motoru | Hazır |
| PHP tema desteği | Hazır |
| Blok sistemi | Hazır |
| Paket altyapısı | Hazır |
| Hook altyapısı | Hazır |
| Medya kütüphanesi | Geliştiriliyor |
| Form oluşturucu | Beta |
| Tema güvenlik sistemi | Hazır |

### Varsayılan temalar

Omurga CMS 1.0 Beta iki sade sistem temasıyla gelir:

#### Omurga Kolay

- OMG tabanlıdır.
- PHP bilmeden tema geliştirmek isteyenler için basit başlangıç temasıdır.
- `home.omg`, `single.omg`, `page.omg`, `category.omg` dosyalarıyla çalışır.

#### Omurga Sabit

- PHP tabanlıdır.
- Klasik PHP tema geliştirmek isteyenler için basit başlangıç temasıdır.
- `home.php`, `single.php`, `page.php`, `category.php` dosyalarıyla çalışır.

Güvenlik için sistemde en az iki tema kalır. Aktif tema ve sistem temaları panelden silinemez.

### Kurulum

1. Dosyaları sunucuya yükleyin.
2. Tarayıcıdan `/install/` adresini açın.
3. Veritabanı bilgilerini girin.
4. Site adı ve yönetici hesabını oluşturun.
5. `/admin/` paneline giriş yapın.

Kurulum sonunda site boş kalmaz. Omurga hakkında 1 örnek yazı ve 1 örnek yorum otomatik eklenir.

### Tema yapısı

OMG tema:

```text
themes/tema-adi/
├─ theme.json
├─ home.omg
├─ single.omg
├─ page.omg
├─ category.omg
├─ header.omg
├─ footer.omg
└─ assets/
```

PHP tema:

```text
themes/tema-adi/
├─ theme.json
├─ home.php
├─ single.php
├─ page.php
├─ category.php
├─ header.php
├─ footer.php
└─ assets/
```

### Belgeler

Geliştirici belgeleri `docs/` klasöründedir:

- `docs/theme-api.md`
- `docs/package-api.md`
- `docs/block-api.md`
- `docs/hooks.md`
- `docs/omg-template.md`
- `docs/media-library.md`

### Lisans

Omurga CMS MIT lisansı ile yayınlanır. Ayrıntılar için `LICENSE` dosyasına bakın.
