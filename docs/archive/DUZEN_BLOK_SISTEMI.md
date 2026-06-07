# Omurga Düzen + Blok Sistemi

Omurga v1.5 ile Blogger'daki **Düzen + Gadget** mantığının Omurga karşılığı eklendi.

## Kavramlar

- **Tema:** Sitenin genel görünüm paketi.
- **Düzen:** Aktif temanın alanlarına blok yerleştirme ekranı.
- **Blok:** Düzen alanına veya sayfa içine eklenen parça.
- **Varsayılan Blok:** Omurga çekirdeğiyle gelen, her temada çalışan blok.
- **Tema Bloğu:** Sadece aktif temanın sağladığı özel blok.

## Tema alanları

Tema, `theme.json` içinde alanlarını bildirir:

```json
{
  "regions": {
    "home_top": "Ana Sayfa Üstü",
    "home_main": "Ana İçerik",
    "sidebar": "Yan Alan",
    "footer": "Alt Alan"
  }
}
```

Panelde bu alanlar **Görünüm > Düzen** ekranında görünür.

## Tema bloğu oluşturma

Bir blok için tema içine klasör açılır:

```text
themes/benim-temam/blocks/son-haberler/
  block.json
  view.php
```

`block.json` örneği:

```json
{
  "name": "Son Haberler",
  "slug": "son-haberler",
  "usage": ["home_main", "sidebar", "page"],
  "settings": {
    "title": {"type": "text", "label": "Başlık", "default": "Son Haberler"},
    "limit": {"type": "number", "label": "Haber Sayısı", "default": 6}
  }
}
```

`view.php` içinde `$settings`, `$block`, `$posts`, `$context` değişkenleri kullanılabilir.

## Sayfa içinde blok kullanma

Editörde kısa kod ile blok çağrılabilir:

```text
[blok slug="son-haberler" limit="5"]
[blok slug="kategori-haberleri" category_id="2" limit="6"]
```
