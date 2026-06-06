# Omurga CMS 1.0.2.2 Beta Block API

Omurga bloklari artik tek merkezi registry uzerinden calisir. Cekirdek,
aktif tema ve aktif paketler ayni tanim standardini kullanir.

## Kaynaklar

- Cekirdek bloklari: `core/blocks/`
- Tema bloklari: `themes/tema-adi/blocks/`
- Paket bloklari: `packages/paket-adi/blocks/`
- Ozel bloklar: `storage/blocks/`

Tema veya paket ayni `id` ile blok kaydederse sistem hata vermez, registry
uyarisi olusturur. Son kayit aktif tanim olarak kullanilir.

## Standart Alanlar

Her blok su alanlari destekler:

- `id`: benzersiz blok id degeri.
- `name`: panelde gorunen ad.
- `description`: kisa aciklama.
- `category`: `content`, `media`, `layout`, `navigation`, `news`, `service`, `user`, `custom`.
- `icon`: panel ikonu icin kisa ad.
- `source`: `core`, `theme`, `package`, `custom`, `registered`.
- `allowed_contexts`: `page`, `header`, `footer`, `sidebar`, `any`.
- `settings_schema`: ayar alanlari.
- `default_settings`: varsayilan ayarlar.
- `render_callback`: programatik render fonksiyonu.
- `preview_callback`: opsiyonel builder onizleme fonksiyonu.

Geriye uyumluluk icin eski `slug`, `settings` ve `usage` alanlari okunmaya
devam eder. Yeni bloklarda `id`, `settings_schema` ve `allowed_contexts`
tercih edilmelidir.

## Dosya Tabanli Blok

```text
blocks/html-metin/
  block.json
  view.omg
```

`block.json` ornegi:

```json
{
  "id": "html-metin",
  "name": "HTML Metin",
  "description": "Baslik ve guvenli metin alani gosterir.",
  "category": "content",
  "icon": "type",
  "allowed_contexts": ["page", "header", "footer", "sidebar"],
  "settings_schema": {
    "title": {"type": "text", "label": "Baslik", "default": ""},
    "text": {"type": "textarea", "label": "Metin", "default": ""}
  },
  "view": "view.omg"
}
```

`view.omg` ornegi:

```html
<section class="omg-core-block">
  @if(settings.title)
    <h2>{{ settings.title }}</h2>
  @endif
  <div>{{ settings.text }}</div>
</section>
```

## Programatik Kayit

```php
omurga_register_block([
    'id' => 'ornek-blok',
    'name' => 'Ornek Blok',
    'description' => 'Paket veya tema icinden kayit edilen blok.',
    'category' => 'custom',
    'icon' => 'square',
    'source' => 'package',
    'allowed_contexts' => ['page', 'sidebar'],
    'settings_schema' => [
        'title' => ['type' => 'text', 'label' => 'Baslik', 'default' => 'Merhaba'],
    ],
    'render_callback' => function (array $block, array $context): string {
        $title = $block['settings']['title'] ?? '';
        return '<section class="omg-core-block"><h2>'.e($title).'</h2></section>';
    },
]);
```

`render_callback` olmayan ve `view` dosyasi bulunmayan programatik bloklar
pasif sayilir. Bozuk blok tanimi builder ekranini cokertmez.

## Builder Kullanimi

Sayfa Tasarimcisi, Header Builder ve Footer Builder blok listesini
`omurga_available_blocks($region)` uzerinden alir.

```php
$pageBlocks = omurga_available_blocks('home_main');
$headerBlocks = omurga_available_blocks('header');
$footerBlocks = omurga_available_blocks('footer');
```

Bolge bazli filtreleme eski `usage` degerlerini ve yeni `allowed_contexts`
degerlerini birlikte dikkate alir.

## Ornekler

### Son Icerikler

```php
omurga_register_block([
    'id' => 'latest-content',
    'name' => 'Son Icerikler',
    'description' => 'Son yayinlanan icerikleri listeler.',
    'category' => 'content',
    'icon' => 'newspaper',
    'source' => 'package',
    'allowed_contexts' => ['page', 'sidebar'],
    'settings_schema' => [
        'title' => ['type' => 'text', 'label' => 'Baslik', 'default' => 'Son Icerikler'],
        'limit' => ['type' => 'number', 'label' => 'Limit', 'default' => 5],
    ],
    'render_callback' => function (array $block, array $context): string {
        $settings = $block['settings'] ?? [];
        $limit = max(1, min(20, (int)($settings['limit'] ?? 5)));
        $posts = function_exists('omurga_latest_posts') ? omurga_latest_posts($limit) : [];
        if (!$posts) return '';
        $html = '<section class="omg-core-block"><h2>'.e($settings['title'] ?? 'Son Icerikler').'</h2><ul>';
        foreach ($posts as $post) {
            $html .= '<li><a href="'.e($post['url'] ?? '#').'">'.e($post['title'] ?? '').'</a></li>';
        }
        return $html.'</ul></section>';
    },
]);
```

### HTML Metin

```php
omurga_register_block([
    'id' => 'html-text',
    'name' => 'HTML Metin',
    'category' => 'content',
    'icon' => 'type',
    'allowed_contexts' => ['page', 'header', 'footer', 'sidebar'],
    'settings_schema' => [
        'title' => ['type' => 'text', 'label' => 'Baslik', 'default' => ''],
        'html' => ['type' => 'textarea', 'label' => 'Guvenli HTML', 'default' => ''],
    ],
    'render_callback' => function (array $block): string {
        $settings = $block['settings'] ?? [];
        return '<section class="omg-core-block"><h2>'.e($settings['title'] ?? '').'</h2>'
            .omurga_block_safe_html((string)($settings['html'] ?? '')).'</section>';
    },
]);
```

### Gorsel

```php
omurga_register_block([
    'id' => 'image',
    'name' => 'Gorsel',
    'category' => 'media',
    'icon' => 'image',
    'allowed_contexts' => ['page', 'header', 'footer', 'sidebar'],
    'settings_schema' => [
        'image' => ['type' => 'image', 'label' => 'Gorsel', 'default' => ''],
        'alt' => ['type' => 'text', 'label' => 'Alt metin', 'default' => ''],
        'link' => ['type' => 'url', 'label' => 'Link', 'default' => ''],
    ],
    'render_callback' => function (array $block): string {
        $s = $block['settings'] ?? [];
        if (empty($s['image'])) return '';
        $img = '<img src="'.e($s['image']).'" alt="'.e($s['alt'] ?? '').'">';
        if (!empty($s['link'])) $img = '<a href="'.e($s['link']).'">'.$img.'</a>';
        return '<figure class="omg-core-block">'.$img.'</figure>';
    },
]);
```

### Buton

```php
omurga_register_block([
    'id' => 'button',
    'name' => 'Buton',
    'category' => 'navigation',
    'icon' => 'mouse-pointer-click',
    'allowed_contexts' => ['page', 'header', 'footer', 'sidebar'],
    'settings_schema' => [
        'text' => ['type' => 'text', 'label' => 'Metin', 'default' => 'Detay'],
        'link' => ['type' => 'url', 'label' => 'Link', 'default' => '#'],
        'new_tab' => ['type' => 'checkbox', 'label' => 'Yeni sekme', 'default' => '0'],
    ],
    'render_callback' => function (array $block): string {
        $s = $block['settings'] ?? [];
        $target = !empty($s['new_tab']) ? ' target="_blank" rel="noopener"' : '';
        return '<a class="omg-button" href="'.e($s['link'] ?? '#').'"'.$target.'>'.e($s['text'] ?? 'Detay').'</a>';
    },
]);
```
