# Omurga v1.6 — Sayfa ve Yazı Şablonları

Omurga'da görünümle ilgili alanlar panelde **Tasarım** başlığı altında toplanır.

## Kavramlar

- **Tema:** Sitenin genel görünüm paketidir.
- **Düzen:** Temanın alanlarına blok yerleştirme ekranıdır.
- **Blok:** Düzen veya sayfa içinde kullanılan parçadır.
- **Şablon:** Tek bir sabit sayfa veya yazının/haberin nasıl görüneceğini belirler.

## Şablon dosyaları

Tema içinde şablonlar şu şekilde tanımlanabilir:

```text
themes/benim-temam/
  theme.json
  page.php
  single.php
  templates/
    page-fullwidth.php
    page-sidebar.php
    single-wide.php
    single-video.php
```

## theme.json örneği

```json
{
  "templates": {
    "page": {
      "fullwidth": {"name":"Tam Genişlik Sayfa", "file":"templates/page-fullwidth.php"}
    },
    "single": {
      "wide": {"name":"Geniş Görselli Yazı", "file":"templates/single-wide.php"}
    }
  }
}
```

## Panel kullanımı

1. İçerik düzenleme ekranına gir.
2. Sağ taraftaki **Tasarım Şablonu** kutusundan şablon seç.
3. Kaydet.

Şablon seçimi içerikte saklanır. Tema değişirse yeni temadaki aynı anahtara sahip şablon kullanılabilir; yoksa varsayılan şablona döner.
