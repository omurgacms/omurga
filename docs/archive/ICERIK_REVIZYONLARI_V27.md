# Omurga v2.7 — İçerik Revizyonları + Geri Alma Sistemi

Bu sürüm Omurga çekirdeğine içerik revizyon sistemi ekler.

## Amaç

Haber, sayfa veya diğer içerikler düzenlenirken eski hal kaybolmaz. Editör gerektiğinde eski sürümü görüntüleyebilir ve tek tıkla geri dönebilir.

## Eklenenler

- `post_revisions` tablosu
- İçerik güncellemeden önce otomatik revizyon alma
- Revizyon listesi
- Revizyon detay ekranı
- Eski sürüme geri dönme
- Geri dönmeden önce mevcut hali ayrıca revizyon olarak saklama
- Değişen alanları gösterme
- Kullanıcı / tarih bilgisi
- Maksimum revizyon limiti

## Varsayılan limit

Her içerik için varsayılan maksimum revizyon sayısı: `20`

Ayar anahtarı:

```text
content_max_revisions
```

Limit aşılırsa en eski revizyonlar otomatik silinir.

## Panel ekranları

```text
Sistem > Revizyonlar
```

İçerik düzenleme ekranında da sağ tarafta `Revizyonlar` kutusu görünür.

## Çekirdeğe eklenmeyenler

Aşağıdaki alanlar çekirdeğe sabitlenmedi:

- Manşet
- Mobil manşet
- Sürmanşet
- Son dakika

Bu alanlar tema/blok meta sistemiyle gelmeye devam eder.
