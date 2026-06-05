# Omurga CMS 1.0.2 Beta Hooks

Omurga hook sistemi action ve filter olarak iki temel API sunar.

## Action

```php
omurga_add_action('hook_adi', function ($arg) {
    // islem
}, 10);

omurga_action('hook_adi', $arg);
```

`omurga_do_action()` eski uyumlu ad olarak calismaya devam eder.

## Filter

```php
omurga_add_filter('hook_adi', function ($value) {
    return $value;
}, 10);

$value = omurga_filter('hook_adi', $value);
```

`omurga_apply_filters()` eski uyumlu ad olarak calismaya devam eder.

## Guvenlik

Gecersiz callback sessizce yok sayilir. Callback hata firlatirsa site cokmez; hata loglanir.

## Ornek Hook Noktalari

- `omurga_before_post_save`
- `omurga_after_post_save`
- `omurga_after_post_publish`
- `omurga_before_post_render`
- `omurga_after_post_render`
- `omurga_before_comment_save`
- `omurga_after_comment_save`
- `omurga_before_omg_render`
- `omurga_after_omg_render`
- `omurga_package_loaded`
- `omurga_plugin_loaded`
