# Omurga Builder API

Omurga Builder API, tema ve paket geliştiricilerinin çekirdek dosyalara dokunmadan Sayfa Tasarımcısı, Header/Footer düzenleri ve blok bölgeleriyle çalışmasını sağlar.

## Temel kavramlar

- **Bölge (region):** Blokların yerleştiği alan. Örnek: `header`, `home_main`, `sidebar`, `footer`.
- **Blok:** Builder içinde seçilebilen içerik parçası.
- **Ayar şeması:** Blok ayar panelinde gösterilecek alanlar.
- **Şablon:** Hazır blok yerleşimi.

## Builder bölgesi ekleme

```php
omurga_register_builder_region('hero_area', 'Hero Alanı', 'home', [
    'position' => 15,
    'description' => 'Ana sayfa üst hero bölümü',
]);
```

## Builder bloğu ekleme

```php
omurga_register_builder_block('duyuru-kutusu', [
    'name' => 'Duyuru Kutusu',
    'description' => 'Kısa duyuru metni gösterir.',
    'category' => 'content',
    'allowed_contexts' => ['home', 'page', 'post'],
    'settings_schema' => [
        'title' => omurga_builder_field('text', 'Başlık', 'Duyuru'),
        'text' => omurga_builder_field('textarea', 'Metin', ''),
    ],
    'render_callback' => function(array $block, array $context = []) {
        $settings = $block['settings'] ?? [];
        return '<section class="omg-announcement"><h3>'.e($settings['title'] ?? '').'</h3><p>'.e($settings['text'] ?? '').'</p></section>';
    },
]);
```

## Alan tipleri

```php
omurga_builder_field('text', 'Başlık', 'Varsayılan');
omurga_builder_field('textarea', 'Açıklama', '');
omurga_builder_field('number', 'Limit', 6, ['min' => 1, 'max' => 20]);
omurga_builder_field('select', 'Görünüm', 'grid', [
    'options' => ['grid' => 'Izgara', 'list' => 'Liste']
]);
omurga_builder_field('checkbox', 'Mobilde gizle', false);
omurga_builder_field('color', 'Renk', '#f97316');
omurga_builder_field('image', 'Görsel', '');
```

## Blok ekleme / güncelleme / silme

```php
$block = omurga_builder_add_block('home_main', 'duyuru-kutusu', [
    'title' => 'Yeni duyuru',
    'text' => 'Omurga Builder API hazır.',
]);

omurga_builder_update_block('home_main', $block['id'], [
    'settings' => ['title' => 'Güncel duyuru'],
]);

omurga_builder_remove_block('home_main', $block['id']);
```

## Hazır şablon kaydetme

```php
omurga_register_builder_template('kurumsal-anasayfa', 'Kurumsal Ana Sayfa', [
    'home_main' => [
        omurga_builder_create_block('heading', ['text' => 'Hoş geldiniz'], ['width' => '100', 'sort' => 10]),
        omurga_builder_create_block('latest-content', ['limit' => 6], ['width' => '100', 'sort' => 20]),
    ],
]);
```

## Tema içinde bölge render etme

```php
<?= omurga_builder_render_region('home_main') ?>
```

veya mevcut kısa kullanım:

```php
<?= omurga_render_region('home_main') ?>
```

## Omurga sınıfı ile kullanım

```php
Omurga::addBuilderRegion('homepage_after_slider', 'Slider Altı', 'home');
Omurga::addBuilderBlock('my-card', [...]);
Omurga::addBuilderTemplate('landing', 'Landing Sayfası', [...]);
```

## Güvenlik notları

- Blok çıktısı üretirken kullanıcıdan gelen değerleri `e()` ile kaçırın.
- HTML kabul eden alanlarda yalnızca güvenilir kullanıcıya izin verin.
- Builder API çekirdeği değiştirmez; düzen verileri mevcut layout sistemi üzerinden saklanır.
