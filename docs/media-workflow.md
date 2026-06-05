# Omurga Medya Akışı

Bu güncelleme yazı ekranı, öne çıkan görsel, galeri ve blok editördeki görsel seçimlerini tek medya seçiciye bağlar.

## Eklenenler

- Medya seçici içinde bilgisayardan hızlı yükleme.
- Yüklenen dosyanın otomatik medya kütüphanesine kaydedilmesi.
- Yükleme sonrası seçili alana otomatik ekleme.
- Öne çıkan görsel alanı için medya seçici + hızlı yükleme desteği.
- Klasik editör yazı içi görsel ekleme için medya seçici + hızlı yükleme desteği.
- Galeri alanı için çoklu medya seçimi ve hızlı yükleme desteği.
- Blok editörde görsel bloğu için URL yazma zorunluluğu yerine "Görsel Seç" butonu.
- Medya seçici API tarafında `mime` / `mime_type` kolon uyumluluğu.

## Kullanım

Yazı ekleme veya düzenleme ekranında:

- Öne çıkan görselde **Medyadan seç** butonuna basılır.
- Açılan pencerede bilgisayardan dosya yüklenebilir veya kütüphaneden seçilebilir.
- Yüklenen dosya otomatik kütüphaneye kaydedilir ve ilgili alana yerleşir.

Blok editörde:

- Görsel bloğu eklenir.
- **Görsel Seç** butonu ile aynı medya seçici açılır.
- Dosya yüklenirse veya kütüphaneden seçilirse görsel yolu otomatik blok alanına yazılır.

## Güvenlik

- Yükleme işlemi admin yetkisi ve CSRF kontrolü kullanır.
- İzin verilen dosya türleri: JPG, PNG, WebP, GIF, PDF, MP4.
- Büyük dosya sınırı hızlı yüklemede 64 MB olarak uygulanır.
