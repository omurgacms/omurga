# Omurga Revizyon Sistemi

Revizyon sistemi yazı ve sayfalarda yapılan düzenlemelerden önce mevcut içeriğin güvenli bir kopyasını saklar.

## Kapsam

- Yazı revizyonları
- Sayfa revizyonları
- Başlık, slug, içerik, blok içeriği, öne çıkan görsel, SEO alanları ve yayın bilgileri
- Etiketler ve blok meta değerleri
- Geri dönüş öncesi mevcut halin ayrıca revizyon olarak saklanması

## Yönetim Ekranı

Yönetim paneli:

Sistem → Revizyonlar

İçerik düzenleme ekranlarında sağ kutuda revizyon sayısı ve "Revizyonları Gör" bağlantısı bulunur.

## Geri Yükleme

Bir revizyona dönüldüğünde:

1. İçeriğin mevcut hali `before_restore` tipiyle saklanır.
2. Seçilen revizyon verisi içeriğe uygulanır.
3. Aktivite kaydı oluşturulur.
4. Bildirim üretilir.

## Saklama Ayarı

Varsayılan saklama sınırı: 20 revizyon.

Ayar anahtarı:

`content_max_revisions`

Değer 1 ile 200 arasında sınırlandırılır.

## Not

Sayfalar artık sabit içerik modeliyle çalışsa da revizyon sistemi sayfaları da destekler. Sayfaların kategori, etiket ve yorum alanları geri yüklenirken içerik türüne göre korunur.
