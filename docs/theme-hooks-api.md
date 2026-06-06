# Omurga Hook ve Tema API

Omurga temaları `functions.php` içinde action/filter kullanabilir.

## Action

```php
omurga_add_action('hook_adi', function($arg){
    // işlem
});
```

## Filter

```php
omurga_add_filter('hook_adi', function($value){
    return $value;
});
```

## Sık kullanılan hooklar

- `omurga_theme_loaded`
- `omurga_before_post_render`
- `omurga_before_omg_render`
- `omurga_menu_locations`
- `omurga_theme_meta`

## Kısa yardımcılar

- `om_theme_setting()`
- `om_theme_setting_bool()`
- `om_theme_asset()`
- `om_menu()`
- `om_region()`
- `om_body_class()`
