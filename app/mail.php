<?php
declare(strict_types=1);
/**
 * Email. On the local dev server mail is written to storage/mail/*.html so it can be previewed.
 * On xneelo it goes out through PHP mail() (xneelo's own mail server) from the shop's mailbox.
 */

function send_mail(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    $from = setting('mail_from') ?: setting('email', 'orders@localhost');
    $shop = setting('shop_name', 'Alora');
    $text = trim(html_entity_decode(strip_tags(preg_replace(['#<br\s*/?>#i', '#</(p|tr|h\d|li)>#i'], ["\n", "\n"], $html)), ENT_QUOTES, 'UTF-8'));
    $text = preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $text));

    if (PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli' || setting('mail_mode') === 'file') {
        $dir = STORAGE . '/mail';
        @mkdir($dir, 0775, true);
        $name = date('Ymd-His') . '-' . substr(md5($to . $subject . microtime()), 0, 6) . '.html';
        file_put_contents("$dir/$name", "<!-- To: " . e($to) . " | Subject: " . e($subject) . " -->\n" . $html);
        return true;
    }

    $b = 'b' . bin2hex(random_bytes(8));
    $headers = [
        'MIME-Version: 1.0',
        'From: ' . mb_encode_mimeheader($shop) . " <$from>",
        'Reply-To: ' . ($replyTo ?: $from),
        "Content-Type: multipart/alternative; boundary=\"$b\"",
    ];
    $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
        . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
        . "--$b--";
    return mail($to, mb_encode_mimeheader($subject), $body, implode("\r\n", $headers), '-f' . $from);
}

/** Render an email template from app/emails with the shared frame. */
function email_html(string $template, array $data): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require __DIR__ . "/emails/$template.php";
    $inner = ob_get_clean();
    ob_start();
    require __DIR__ . '/emails/frame.php';
    return ob_get_clean();
}

function send_order_email(array $order, string $kind, string $note = ''): void
{
    $subjects = [
        'paid' => "Thank you! Your order {$order['number']} is confirmed",
        'packed' => "Your order {$order['number']} is packed",
        'shipped' => "Your order {$order['number']} is on its way",
        'completed' => "Your order {$order['number']} is complete",
        'cancelled' => "Your order {$order['number']} was cancelled",
    ];
    $html = email_html('order', ['order' => $order, 'items' => order_items((int) $order['id']), 'kind' => $kind, 'note' => $note]);
    send_mail($order['email'], $subjects[$kind] ?? "Update on order {$order['number']}", $html, setting('email'));
}

function send_shop_email(array $order, array $lowStock = []): void
{
    $to = setting('orders_to') ?: setting('email');
    if (!$to) {
        return;
    }
    $html = email_html('shop-order', ['order' => $order, 'items' => order_items((int) $order['id']), 'lowStock' => $lowStock]);
    send_mail($to, "New paid order {$order['number']}: " . money((int) $order['total_cents']), $html, $order['email']);
}
