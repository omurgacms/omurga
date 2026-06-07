# Tema Framework Desteği

Omurga çekirdeği herhangi bir CSS framework'üne bağlı değildir. Tema geliştiricisi saf CSS, Tailwind, Bootstrap veya başka bir sistemi kullanabilir.

## theme.json

```json
{
  "framework": "tailwind",
  "assets": {
    "css": ["assets/css/style.css"],
    "head_js": [],
    "js": []
  }
}
```

Desteklenen `framework` değerleri:

- `none`
- `custom`
- `tailwind`
- `bootstrap`
- `bulma`
- `foundation`

## OMG Kullanımı

```html
{{ theme_head() }}
<body class="{{ theme_framework_class() }}">
  {{ menu('main') }}
  {{ builder('home_main') }}
  {{ theme_footer() }}
</body>
```

## PHP Kullanımı

```php
echo om_theme_head();
echo om_theme_framework();
echo om_theme_framework_class();
echo om_theme_footer();
```

Not: Omurga Tailwind veya Bootstrap dosyalarını çekirdeğe gömmez. Derlenmiş CSS dosyası tema içinde bulunmalıdır.
