<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$method = request_method();
$path = APP_BASE_PATH . '/storage';
$file = $path . '/announcements.json';
$lockFile = $path . '/announcements.lock';

function with_announcement_store(string $file, string $lockFile, bool $write, ?callable $change = null)
{
    $lock = fopen($lockFile, 'c');
    if ($lock === false) {
        json_error('Announcement storage is unavailable.', 500);
    }
    if (!flock($lock, $write ? LOCK_EX : LOCK_SH)) {
        fclose($lock);
        json_error('Unable to lock announcement storage.', 500);
    }

    try {
        if (!is_file($file)) {
            $items = [];
        } else {
            $contents = file_get_contents($file);
            $items = $contents === false ? null : json_decode($contents, true);
            if (!is_array($items)) {
                json_error('Announcement storage contains invalid data.', 500);
            }
        }

        if ($change !== null) {
            $items = $change($items);
            $encoded = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                json_error('Unable to encode announcements.', 500);
            }
            $temporary = tempnam(dirname($file), 'announcements-');
            if ($temporary === false || file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false || !rename($temporary, $file)) {
                if (is_string($temporary) && is_file($temporary)) {
                    unlink($temporary);
                }
                json_error('Unable to save announcements.', 500);
            }
            $items = array_values($items);
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    return $items;
}

function order_announcements(array $items): array
{
    usort($items, function ($left, $right) {
        return strcmp((string) ($right['posted_at'] ?? ''), (string) ($left['posted_at'] ?? ''));
    });
    return $items;
}

if ($method === 'GET') {
    require_role(['admin', 'driver', 'passenger']);
    json_success(order_announcements(with_announcement_store($file, $lockFile, false)));
}

require_role(['admin']);
$input = request_input();
require_csrf($input);

try {
    if ($method === 'POST') {
        require_fields($input, ['title', 'body']);
        $title = trim_string($input['title']);
        $body = trim_string($input['body']);
        if (strlen($title) > 180 || strlen($body) > 12000) {
            json_error('Announcement title or body is too long.', 422);
        }
        $item = [
            'id' => bin2hex(random_bytes(12)),
            'title' => $title,
            'body' => $body,
            'posted_by' => $user['username'],
            'posted_at' => date(DATE_ATOM),
        ];
        with_announcement_store($file, $lockFile, true, function ($items) use ($item) {
            $items[] = $item;
            return $items;
        });
        json_success($item, 201);
    }

    if ($method === 'PUT' || $method === 'DELETE') {
        require_fields($input, ['id']);
        $id = trim_string($input['id']);
        $updated = null;
        $items = with_announcement_store($file, $lockFile, true, function ($items) use ($input, $method, $id, $user, &$updated) {
            $found = false;
            foreach ($items as $index => $item) {
                if (!isset($item['id']) || !hash_equals((string) $item['id'], $id)) {
                    continue;
                }
                $found = true;
                if (($item['type'] ?? '') === 'external') {
                    json_error('External announcements cannot be edited or deleted.', 422);
                }
                if ($method === 'DELETE') {
                    array_splice($items, $index, 1);
                } else {
                    require_fields($input, ['title', 'body']);
                    $title = trim_string($input['title']);
                    $body = trim_string($input['body']);
                    if (strlen($title) > 180 || strlen($body) > 12000) {
                        json_error('Announcement title or body is too long.', 422);
                    }
                    $items[$index]['title'] = $title;
                    $items[$index]['body'] = $body;
                    $items[$index]['posted_by'] = $user['username'];
                    $updated = $items[$index];
                }
                break;
            }
            if (!$found) {
                json_error('Announcement not found.', 404);
            }
            return $items;
        });
        json_success($method === 'DELETE' ? ['message' => 'Announcement deleted.'] : $updated);
    }

    json_error('Method not allowed.', 405);
} catch (RuntimeException $error) {
    json_error($error->getMessage(), 500);
}
