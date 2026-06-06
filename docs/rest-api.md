# Omurga REST API

Omurga REST API, harici uygulamaların içerik, sayfa, kategori, etiket ve medya verilerine erişmesi için eklenmiştir.

## Temel endpointler

```text
GET /api
GET /api/status
GET /api/posts
GET /api/posts/{id|slug}
GET /api/pages
GET /api/pages/{id|slug}
GET /api/categories
GET /api/tags
GET /api/media
```

Yayınlanmış yazılar, sayfalar, kategoriler ve etiketler herkese açık okunabilir. Medya listesi ve yazma işlemleri API anahtarı ister.

## API anahtarı

Panelden gidin:

```text
Admin > Sistem > REST API
```

Yeni anahtar oluşturun ve istekte gönderin:

```http
Authorization: Bearer omg_xxxxxxxxx
```

## Yazı oluşturma

```http
POST /api/posts
Content-Type: application/json
Authorization: Bearer omg_xxxxxxxxx
```

```json
{
  "title": "Örnek Haber",
  "content": "İçerik metni",
  "status": "draft",
  "category_id": 1,
  "tags": ["gündem", "bitlis"]
}
```

## Güvenlik

- API panelden kapatılabilir.
- Anahtarlar hash olarak saklanır.
- Yazma işlemleri için `write` veya `*` yetkisi gerekir.
- CORS varsayılan olarak kapalıdır.
