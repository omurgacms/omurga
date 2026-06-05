# Omurga Aktivite Kayıtları

Bu modül panelde yapılan önemli işlemleri kayıt altına alır.

## Kayıtlanan alanlar

- İçerik işlemleri
- Kullanıcı ve rol işlemleri
- Tema işlemleri
- Paket işlemleri
- Sistem, güvenlik, yedekleme ve rollback işlemleri
- Başarısız giriş ve riskli hareketler

## Yönetim ekranı

`admin/logs.php` ekranı artık **Aktivite Kayıtları** olarak çalışır.

Özellikler:

- Modüle göre filtreleme
- Seviyeye göre filtreleme
- Tarih aralığı
- Kullanıcı / IP / açıklama arama
- CSV dışa aktarım
- JSON dışa aktarım
- 30 / 90 / 180 / 365 gün veya sınırsız saklama politikası

## API

```php
log_activity(
    'post.create',
    'Yazı oluşturuldu: Haber başlığı',
    null,
    'content',
    'post',
    12,
    ['level' => 'success']
);
```

`module` boş bırakılırsa işlem adına göre otomatik belirlenir.

## Güvenlik

Tüm kayıtları temizleme işlemi sadece Süper Yönetici tarafından yapılabilir.
