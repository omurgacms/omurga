# Omurga OMG Template Engine

`.omg`, Omurga'nin resmi HTML tabanli tema sablon formatidir. Tema
gelistiricileri PHP yazmadan guvenli tema dosyalari olusturabilir.

## Standart Dosyalar

Resmi OMG tema klasoru su dosyalari destekler:

- `home.omg`
- `single.omg`
- `page.omg`
- `category.omg`
- `header.omg`
- `footer.omg`
- `components/*.omg`

`theme.json` icinde `template_engine` veya `engine` degeri `omg` olmalidir.

## Degisken Ciktisi

`{{ }}` varsayilan olarak HTML escape yapar:

```html
{{ site.name }}
{{ site.url }}
{{ page.title }}
{{ post.title }}
{{ post.excerpt }}
{{ post.image }}
{{ post.url }}
{{ category.name }}
```

Eksik degisken bos string dondurur.

HTML gereken alanlarda raw cikti kullanilir:

```html
{!! post.content !!}
```

Raw cikti script, inline event ve `javascript:` risklerine karsi temizlenir.

## Kosul

```html
@if(post.image)
  <img src="{{ post.image }}" alt="{{ post.title }}">
@endif
```

Basit esitlik kontrolleri:

```html
@if(post.comments_enabled == "1")
  <omg:content />
@endif
```

## Dongu

```html
@foreach(posts as post)
  @include('components/post-card')
@endforeach
```

Dongu icinde `loop.index`, `loop.number` ve `loop.first` kullanilabilir.

## Include

Include sadece aktif tema klasoru icinden calisir:

```html
@include('header')
@include('footer')
@include('components/post-card')
```

`.omg` uzantisi yazilmazsa otomatik eklenir. `../`, mutlak yol veya tema
disina cikis denemeleri bos dondurulur ve loglanir. Var olmayan include siteyi
cokertmez.

## Builder Alani

Sayfa Tasarimcisi ciktisi:

```html
<omg:content />
```

Detay veya sabit sayfada `post.content` varsa guvenli HTML olarak basilir.
Aksi halde `content_region` degeri kullanilir; yoksa `home_main` render edilir.

## Asset ve URL Helperlari

Tema assetleri:

```html
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
<script src="{{ asset('js/theme.js') }}" defer></script>
```

`asset('css/style.css')`, aktif tema icinde `assets/css/style.css` yoluna
donusur. `../` ve mutlak yol denemeleri bos dondurulur.

Site URL:

```html
<a href="{{ url('/') }}">Ana Sayfa</a>
<a href="{{ url('/kategori/gundem') }}">Gundem</a>
```

## Guvenlik

- `.omg` icinde PHP calistirilmaz.
- `<?php` ve `<?=` gibi PHP etiketleri render oncesi temizlenir ve loglanir.
- `eval` kullanilmaz.
- `{{ }}` ciktilari escape edilir.
- `{!! !!}` yalnizca HTML gereken alanlar icindir.
- Include sadece aktif tema klasoru icinden calisir.
- OMG cache `storage/cache/templates` altinda tutulur.
- Hata on yuzde beyaz ekran yerine log/fallback ile ele alinir.

## Basit Sayfa Sablonu

```html
@include('header')
<main class="om-container">
  <article>
    <h1>{{ page.title }}</h1>
    {!! page.content !!}
  </article>
</main>
@include('footer')
```

## Haber/Yazi Detay Sablonu

```html
@include('header')
<main class="om-container om-single">
  <article>
    <h1>{{ post.title }}</h1>
    @if(post.image)
      <img src="{{ post.image }}" alt="{{ post.title }}">
    @endif
    <p>{{ post.excerpt }}</p>
    {!! post.content !!}
    <omg:content />
  </article>
</main>
@include('footer')
```

## Kategori Liste Sablonu

```html
@include('header')
<main class="om-container">
  <h1>{{ category.name }}</h1>
  @if(category.description)
    <p>{{ category.description }}</p>
  @endif
  <section class="om-grid">
    @foreach(posts as post)
      @include('components/post-card')
    @endforeach
  </section>
</main>
@include('footer')
```

## Post Kart Component

`components/post-card.omg`:

```html
<article class="om-card">
  @if(post.image)
    <a href="{{ post.url }}"><img src="{{ post.image }}" alt="{{ post.title }}"></a>
  @endif
  <h2><a href="{{ post.url }}">{{ post.title }}</a></h2>
  <p>{{ post.excerpt }}</p>
</article>
```

## Header ve Footer

`header.omg`:

```html
<!doctype html>
<html lang="{{ site.language }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ title }}</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<header>
  <a href="{{ url('/') }}">{{ site.name }}</a>
</header>
```

`footer.omg`:

```html
<footer>
  <p>{{ site.name }}</p>
</footer>
<script src="{{ asset('js/theme.js') }}" defer></script>
</body>
</html>
```
