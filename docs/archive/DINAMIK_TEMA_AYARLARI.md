# Omurga v1.6.5 — Dinamik Tema Ayarları

Bu sürümde **Tema Ayarları** ekranı aktif temanın `theme.json` dosyasındaki `settings` alanından otomatik oluşturulur.

## Mantık

- Ayar tanımı: `themes/{tema-slug}/theme.json`
- Kullanıcının seçtiği değerler: veritabanında `theme_settings_{tema-slug}` anahtarı
- Tema dosyasına kullanıcı ayarı yazılmaz. Tema güncellense bile seçilen değerler korunur.

## Örnek settings

```json
{
  "settings": {
    "header_type": {
      "type": "select",
      "label": "Header Tipi",
      "default": "classic",
      "options": {
        "classic": "Klasik Header",
        "centered": "Ortalanmış Logo",
        "news": "Haber Sitesi Header"
      }
    },
    "primary_color": {
      "type": "color",
      "label": "Ana Renk",
      "default": "#f97316"
    }
  }
}
```

## Temada kullanımı

```php
$headerType = theme_setting('header_type', 'classic');
$primaryColor = theme_setting('primary_color', '#f97316');
$showTopbar = theme_setting_bool('show_topbar', true);
```

## Header/Footer Düzeni boşa çıkmaz

Tema Ayarları header/footer **iskeletini** seçtirir.

Header / Footer Düzeni ise o iskeletin içindeki blokları ve içerikleri yönetir.

Örnek:

- Tema Ayarları: `Header Tipi = Haber Sitesi Header`
- Header / Footer Düzeni: `Logo`, `Menü`, `Son Dakika`, `Reklam`, `Giriş/Kayıt` gibi bloklar

Bu iki sistem birlikte çalışır.
