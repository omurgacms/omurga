# Omurga Forms v2

Omurga Forms v2, çekirdekte basit form oluşturma ve başvuru yönetimi sağlar.

## Admin ekranı

`/admin/forms.php` iki bölümden oluşur:

- **Başvurular:** Ön yüzden gelen form kayıtlarını listeler, arar, filtreler, durum günceller ve siler.
- **Form Oluşturucu:** Yeni form oluşturur, alanları düzenler ve kısa kod üretir.

## Form kısa kodu

Bir formu sayfa veya yazı içine eklemek için:

```text
[form id="1"]
```

veya slug ile:

```text
[form slug="iletisim-formu"]
```

## Form bloğu

Sayfa Tasarımcısı içinde çekirdek **Form** bloğu kullanılabilir. Blok ayarına form ID veya slug girilir.

## Alan tipleri

- text
- email
- tel
- textarea
- select
- checkbox
- number
- url

## Başvurular

Gönderilen kayıtlar `forms` tablosuna düşer. Dinamik alanlar `meta.fields` içinde saklanır; temel alanlar ayrıca `name`, `phone`, `email`, `message` kolonlarına da yazılır.
