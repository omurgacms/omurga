<?php
if(!defined('OMURGA_INIT')) { http_response_code(403); exit('Forbidden'); }

/*
 * Omurga Mail Bridge
 *
 * Çekirdek SMTP veya şablon sistemi içermez. Mail gönderimi paketlere bırakılır.
 * Mail paketi aktifse `omurga.mail.send` filtresini yakalayıp gönderimi yapar.
 */

function omurga_normalize_mail_recipients($to): array {
    if (is_string($to)) {
        $parts = array_map('trim', preg_split('/[,;]+/', $to) ?: []);
    } elseif (is_array($to)) {
        $parts = $to;
    } else {
        $parts = [];
    }
    $out = [];
    foreach ($parts as $item) {
        if (is_array($item)) {
            $email = trim((string)($item['email'] ?? $item['address'] ?? ''));
            $name = trim((string)($item['name'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[] = $name !== '' ? ['email'=>$email,'name'=>$name] : $email;
            }
        } else {
            $email = trim((string)$item);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $out[] = $email;
        }
    }
    return $out;
}

function omurga_mail_result($to, string $subject, string $body, array $options = []): array {
    $payload = [
        'to' => omurga_normalize_mail_recipients($to),
        'subject' => trim($subject),
        'body' => (string)$body,
        'html' => (bool)($options['html'] ?? true),
        'from' => $options['from'] ?? null,
        'reply_to' => $options['reply_to'] ?? null,
        'cc' => omurga_normalize_mail_recipients($options['cc'] ?? []),
        'bcc' => omurga_normalize_mail_recipients($options['bcc'] ?? []),
        'attachments' => is_array($options['attachments'] ?? null) ? $options['attachments'] : [],
        'headers' => is_array($options['headers'] ?? null) ? $options['headers'] : [],
        'template' => $options['template'] ?? null,
        'context' => is_array($options['context'] ?? null) ? $options['context'] : [],
        'options' => $options,
    ];

    if (empty($payload['to'])) {
        return ['success'=>false,'handled'=>true,'message'=>'Geçerli alıcı e-posta adresi yok.','payload'=>$payload];
    }
    if ($payload['subject'] === '') {
        return ['success'=>false,'handled'=>true,'message'=>'E-posta konusu boş olamaz.','payload'=>$payload];
    }

    if (function_exists('omurga_do_action')) {
        omurga_do_action('omurga.mail.before_send', $payload);
    }

    $default = [
        'success' => false,
        'handled' => false,
        'message' => 'Mail paketi aktif değil veya hiçbir mail gönderici bu isteği işlemedi.',
        'payload' => $payload,
    ];

    $result = function_exists('omurga_apply_filters')
        ? omurga_apply_filters('omurga.mail.send', $default, $payload)
        : $default;

    if ($result === true) {
        $result = ['success'=>true,'handled'=>true,'message'=>'E-posta gönderildi.','payload'=>$payload];
    } elseif ($result === false || $result === null) {
        $result = $default;
    } elseif (!is_array($result)) {
        $result = ['success'=>false,'handled'=>true,'message'=>(string)$result,'payload'=>$payload];
    }

    $result['success'] = !empty($result['success']);
    $result['handled'] = array_key_exists('handled', $result) ? (bool)$result['handled'] : true;
    $result['message'] = (string)($result['message'] ?? ($result['success'] ? 'E-posta gönderildi.' : 'E-posta gönderilemedi.'));
    $result['payload'] = $result['payload'] ?? $payload;

    if (function_exists('omurga_do_action')) {
        omurga_do_action('omurga.mail.after_send', $result, $payload);
    }

    return $result;
}

function om_mail($to, string $subject, string $body, array $options = []): bool {
    $result = omurga_mail_result($to, $subject, $body, $options);
    return !empty($result['success']);
}

function omurga_mail($to, string $subject, string $body, array $options = []): bool {
    return om_mail($to, $subject, $body, $options);
}
