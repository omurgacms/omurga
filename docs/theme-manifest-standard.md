# Omurga Tema Manifest Standardı

Resmi tema manifest dosyası `theme.json` adını taşır.

## Zorunlu alanlar

```json
{
  "name": "Benim Temam",
  "slug": "benim-temam",
  "version": "1.0.0",
  "author": "Geliştirici"
}
```

## Önerilen tam örnek

```json
{
  "name": "Benim Temam",
  "slug": "benim-temam",
  "version": "1.0.0",
  "author": "Geliştirici",
  "description": "Omurga CMS uyumlu tema.",
  "template_engine": "php",
  "supports": ["menus", "comments", "responsive", "builder"],
  "menu_locations": {
    "main": "Ana Menü",
    "mobile": "Mobil Menü",
    "footer": "Footer Menü"
  },
  "regions": {
    "header": "Üst Alan",
    "home_main": "Ana İçerik",
    "sidebar": "Yan Alan",
    "footer": "Alt Alan"
  },
  "settings": {
    "primary_color": {
      "type": "color",
      "label": "Ana Renk",
      "default": "#f97316"
    },
    "show_author": {
      "type": "checkbox",
      "label": "Yazıda yazarı göster",
      "default": "1"
    }
  }
}
```

## Alan açıklamaları

- `template_engine`: `php`, `omg` veya `hybrid` olabilir.
- `supports`: temanın desteklediği özellikleri bildirir.
- `menu_locations`: panelde menü atama konumlarını oluşturur.
- `regions`: builder/blok alanlarını bildirir.
- `settings`: tema ayar paneli alanlarını bildirir.
