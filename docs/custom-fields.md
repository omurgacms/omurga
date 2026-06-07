# Omurga Özel Alanlar Sistemi

Omurga CMS 1.0.7.3 Beta ile yazı ve sayfalara ek bilgi alanları tanımlanabilir.

## Yönetim

Panel yolu:

```text
İçerik > Özel Alanlar
```

Alan grupları yazılarda, sayfalarda veya belirli kategorilerde gösterilebilir.

## Desteklenen alan tipleri

- Metin
- Uzun metin
- Sayı
- URL
- E-posta
- Telefon
- Tarih
- Saat
- Renk
- Dosya
- Görsel
- Galeri
- Video URL
- Harita / Konum
- Aç/Kapat
- Seçim Kutusu
- Çoklu Seçim

## OMG Kullanımı

```html
{{ field('kaynak') }}
{{ field('muhabir') }}
{{ field('video_url') }}
{{ field('etkinlik_tarihi') }}
```

Koşullu kullanım:

```html
@if(field('video_url'))
  <div class="video">{{ field('video_url') }}</div>
@endif
```

## REST API

Yazı ve sayfa çıktılarında `fields` anahtarı bulunur.

```json
{
  "fields": {
    "kaynak": "Omurga Haber",
    "video_url": "https://..."
  }
}
```
