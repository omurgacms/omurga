# Omurga İçerik Çekirdeği

Bu sürümde içerik yönetimi genel CMS mantığına yaklaştırıldı.

## İçerikler

- Yazılar: haber, blog, makale ve duyuru gibi akış içerikleri.
- Sayfalar: hakkımızda, iletişim, KVKK, künye gibi sabit içerikler.
- Kategoriler ve etiketler yalnızca yazı akışı içindir.

## Kalıcı Bağlantılar

Sayfalar kökten çalışır:

```text
/hakkimizda
/iletisim
```

Yazılar seçilen tabanla çalışır:

```text
/yazi/ornek-yazi
/haber/ornek-yazi
/blog/ornek-yazi
```

Ayarlar > Kalıcı Bağlantılar ekranından yazı tabanı değiştirilebilir.

## Menü Sistemi

Görünüm > Menü Yönetimi ekranı; sayfa, kategori, etiket ve özel bağlantı eklemeyi destekler.

## SEO Temeli

Ayarlar > SEO ekranı üzerinden genel başlık formatı, meta açıklama, canonical, Open Graph, Twitter kartları, robots ve sitemap ayarları yönetilir.

## Yorum Yönetimi

Yorumlar ayrı yönetilir: bekleyen, onaylı, spam, çöp ve cevaplama işlemleri vardır. Sayfalarda yorum ve dinamik akış kapalıdır.

## Widget / Sidebar

Görünüm > Widget / Sidebar ekranı, tema alanlarına blok eklemeyi sağlar. Tema içinde:

```php
omurga_render_sidebar('sidebar');
```

ile çağrılır.
