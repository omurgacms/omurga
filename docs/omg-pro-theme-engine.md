# OMG Pro Theme Engine

Omurga CMS 1.0.7 Beta ile OMG tema motoru, haber ve kurumsal temalar için daha güçlü kısa etiketler ve içerik sorguları destekler.

## Temel kısa etiketler

```html
{{ menu('main') }}
{{ menu('footer') }}
{{ builder('home_main') }}
{{ region('sidebar') }}
{{ setting('logo') }}
{{ theme_setting('primary_color') }}
{{ hook('head') }}
{{ ad('header') }}
{{ block('latest-content', limit=6) }}
{{ image('post.image', 'medium') }}
{{ t('read_more', 'Devamını oku') }}
```

## İçerik listeleri

```html
{{ latest_posts(10) }}
{{ popular_posts(5) }}
{{ featured_posts(5) }}
{{ category_posts('gundem', limit=8) }}
{{ tag_posts('spor', limit=6) }}
{{ posts(category='gundem', limit=6, class='home-grid') }}
```

## Döngü sistemi

```html
@posts(category='gundem', limit=6)
  <article>
    {{ image('post.image', 'thumb') }}
    <h3><a href="{{ post.url }}">{{ post.title }}</a></h3>
    <p>{{ post.excerpt }}</p>
  </article>
@endposts

@latest_posts(5)
  <a href="{{ post.url }}">{{ post.title }}</a>
@endlatest_posts
```

Desteklenen döngüler:

- `@posts(...) ... @endposts`
- `@latest_posts(...) ... @endlatest_posts`
- `@popular_posts(...) ... @endpopular_posts`
- `@featured_posts(...) ... @endfeatured_posts`
- `@category_posts(...) ... @endcategory_posts`
- `@tag_posts(...) ... @endtag_posts`

## Güvenlik

- `.omg` içinde PHP çalışmaz.
- `{{ }}` çıktıları varsayılan olarak escape edilir.
- HTML üreten güvenli yardımcılar sadece izinli helper listesiyle çalışır.
- `@include` sadece aktif tema klasörü içinden dosya çağırır.

## Amaç

Tema geliştiricisi mümkün olduğunca PHP yazmadan; menü, builder alanı, reklam, içerik listesi ve görsel çıktısı oluşturabilsin.
