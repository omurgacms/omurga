# Omurga Tema Geliştirici Rehberi

Omurga v4 resmi tema şablon formatı `.omg` dosyalarıdır. PHP dosyaları yalnızca ileri seviye/fallback modunda kullanılabilir.

Önerilen yapı:

```text
themes/tema-adi/
  theme.json
  home.omg
  single.omg
  page.omg
  category.omg
  header.omg
  footer.omg
  components/
  blocks/
  assets/
```

`theme.json` içinde `template_engine`: `omg` kullanılmalıdır.
