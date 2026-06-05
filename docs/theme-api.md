# Omurga CMS 1.0.2 Beta Theme API

Omurga CMS 1.0.2 Beta resmi tema standardi `.omg` template sistemidir. Her tema klasorunde `theme.json` zorunludur.

## Zorunlu Yapi

```text
themes/tema-slug/
  theme.json
  home.omg
  single.omg
  page.omg
  category.omg
  header.omg
  footer.omg
  components/
    post-card.omg
  assets/
```

## theme.json

```json
{
  "name": "Tema Adi",
  "slug": "tema-slug",
  "version": "1.0.0",
  "engine": "omg",
  "description": "Omurga CMS uyumlu tema.",
  "regions": {
    "home_main": "Ana Icerik",
    "sidebar": "Yan Alan",
    "post_inside": "Yazi Detay Ici"
  },
  "menu_locations": {
    "main": "Ana Menu",
    "footer": "Footer Menu"
  },
  "settings": {}
}
```

## Dogrulama

Tema etkinlestirilirken `theme.json` ve zorunlu `.omg` dosyalari kontrol edilir. Uyumsuz aktif tema varsa site hata vermek yerine uyumlu varsayilan temaya duser ve admin panelinde uyari gosterir.

## Eski Temalar

PHP tabanli temalar geriye uyumluluk modunda calisabilir. `.tpl` temalar resmi format degildir ve aktif tema seceneklerinden gizlenir.
