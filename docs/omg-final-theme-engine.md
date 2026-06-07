# OMG Final Theme Engine

Omurga CMS 1.0.7.2 Beta ile OMG, tema geliştirmenin resmi ve ana şablon motorudur. PHP çalıştırmaz; varsayılan `{{ }}` çıktıları escape edilir, `{!! !!}` çıktıları güvenli HTML temizliğinden geçer.

## Temel değişkenler

```omg
{{ site.name }}
{{ site.description }}
{{ site.url }}
{{ theme.url }}
{{ post.title }}
{{ post.excerpt }}
{!! post.content !!}
```

## Helperlar

```omg
{{ menu('main') }}
{{ builder('home_main') }}
{{ region('sidebar') }}
{{ setting('logo') }}
{{ theme_setting('primary_color') }}
{{ hook('head') }}
{{ ad('header') }}
{{ block('latest-content', limit=6) }}
{{ image('post.image', 'medium') }}
{{ breadcrumb() }}
{{ pagination(total=10, current=1) }}
{{ related_posts(limit=4) }}
{{ author_box() }}
{{ reading_time() }}
{{ share_buttons() }}
{{ comments_count() }}
{{ canonical() }}
{{ body_class() }}
```

## İçerik sorguları

```omg
{{ latest_posts(10) }}
{{ popular_posts(5) }}
{{ featured_posts(5) }}
{{ category_posts('gundem', limit=8) }}
{{ tag_posts('spor', limit=6) }}
```

## Döngüler

```omg
@posts(category='gundem', limit=6)
  <article>
    <h2>{{ post.title }}</h2>
    {{ image('post.image', 'medium') }}
  </article>
@endposts

@related_posts(limit=4)
  <a href="{{ post.url }}">{{ post.title }}</a>
@endrelated_posts
```

## Blade benzeri koşullar

```omg
@if(post.image)
  {{ image('post.image') }}
@elseif(post.video_url)
  {!! post.video !!}
@else
  <div>Görsel yok</div>
@endif
```

Karşılaştırma ve mantıksal ifadeler desteklenir:

```omg
@if(loop.number == 1) ... @endif
@if(post.comments_count > 0 and post.comments_enabled) ... @endif
```

## Foreach / Forelse

```omg
@foreach(posts as post)
  <h2>{{ post.title }}</h2>
@endforeach

@forelse(posts as post)
  <h2>{{ post.title }}</h2>
@empty
  <p>İçerik bulunamadı.</p>
@endforelse
```

## Include ve veri geçirme

```omg
@include('header')
@include('components/card', title='Öne çıkan')
```

## Component ve slot

Tema içinde `components/panel.omg`:

```omg
<section class="panel">
  <h2>{{ title }}</h2>
  <div>{!! slot !!}</div>
</section>
```

Kullanım:

```omg
@component('panel')
  @slot('title') Gündem @endslot
  @latest_posts(5)
    <a href="{{ post.url }}">{{ post.title }}</a>
  @endlatest_posts
@endcomponent
```

## Güvenlik

- OMG içinde PHP çalışmaz.
- `<?php ?>` ve `@php` blokları temizlenir.
- Include/component sadece aktif tema klasörü içinde çalışır.
- `{{ }}` çıktıları HTML escape edilir.
- `{!! !!}` çıktıları script ve inline event temizliğinden geçer.
