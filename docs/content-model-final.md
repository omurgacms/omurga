# Omurga İçerik Modeli

Bu sürümde içerik modeli genel CMS mantığına yaklaştırıldı.

## Yazılar

Yazılar dinamik içeriktir. Haber, blog, makale ve duyuru gibi akışa giren içerikler burada tutulur.

- Kategori kullanır.
- Etiket kullanır.
- Yorum kullanabilir.
- Son içerikler, popüler içerikler ve öne çıkan içerikler gibi bloklarda listelenir.
- URL yapısı içerik tabanına göre çalışır: `/yazi/yazi-adi`.

## Sayfalar

Sayfalar sabit içeriktir. Hakkımızda, İletişim, KVKK, Künye ve Reklam gibi sayfalar burada tutulur.

- Kategori kullanmaz.
- Etiket kullanmaz.
- Yorum kullanmaz.
- Son içerikler / popüler içerikler / öne çıkan içerikler bloklarına girmez.
- Kök URL ile çalışır: `/hakkimizda`.
- Menüden veya doğrudan linkle ulaşılır.

## Blok Kullanımı

Dinamik içerik blokları sabit sayfa bağlamından çıkarıldı:

- Son İçerikler
- Popüler İçerikler
- Öne Çıkan İçerikler
- Yorumlar

Bu bloklar yazı, anasayfa, sidebar veya footer gibi dinamik bağlamlarda kullanılabilir.
