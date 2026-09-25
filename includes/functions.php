<?php
/**
 * Shared helper functions for PoloNav APIs.
 */

function json_response($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_success($data = null, int $statusCode = 200): void
{
    $payload = ['success' => true];
    if ($data !== null) {
        $payload['data'] = $data;
    }
    json_response($payload, $statusCode);
}

function json_error(string $message, int $statusCode = 400, array $extra = []): void
{
    json_response(array_merge([
        'success' => false,
        'error'   => $message,
    ], $extra), $statusCode);
}

/**
 * Reads JSON body, or falls back to form-encoded POST fields.
 */
function request_input(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $raw = file_get_contents('php://input');

    if ($raw !== false && $raw !== '' && stripos($contentType, 'application/json') !== false) {
        $decoded = json_decode($raw, true);
        $cached = is_array($decoded) ? $decoded : [];
        return $cached;
    }

    if (!empty($_POST)) {
        $cached = $_POST;
        return $cached;
    }

    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $cached = $decoded;
            return $cached;
        }

        parse_str($raw, $parsed);
        $cached = is_array($parsed) ? $parsed : [];
        return $cached;
    }

    $cached = [];
    return $cached;
}

function request_method(): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $input = request_input();

    if (isset($input['_method']) && is_string($input['_method'])) {
        $method = strtoupper($input['_method']);
    }

    return $method;
}

function query_param(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

function trim_string($value): string
{
    return is_string($value) ? trim($value) : '';
}

function require_fields(array $input, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($input[$field]) || trim_string((string) $input[$field]) === '') {
            json_error($field . ' is required.', 422);
        }
    }
}

function to_int($value, string $fieldName, bool $allowZero = true): int
{
    if ($value === null || $value === '') {
        json_error($fieldName . ' is required.', 422);
    }

    if (!is_numeric($value) || (string) (int) $value !== (string) (int) round((float) $value)) {
        json_error($fieldName . ' must be a whole number.', 422);
    }

    $number = (int) $value;

    if (!$allowZero && $number === 0) {
        json_error($fieldName . ' must be greater than zero.', 422);
    }

    return $number;
}

function to_non_negative_int($value, string $fieldName): int
{
    $number = to_int($value, $fieldName, true);
    if ($number < 0) {
        json_error($fieldName . ' cannot be negative.', 422);
    }
    return $number;
}

function to_float($value, string $fieldName): float
{
    if ($value === null || $value === '') {
        json_error($fieldName . ' is required.', 422);
    }

    if (!is_numeric($value)) {
        json_error($fieldName . ' must be a number.', 422);
    }

    return (float) $value;
}

function like_search(string $term): string
{
    $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    return '%' . $escaped . '%';
}

/**
 * Turns common MySQL constraint errors into student-friendly messages.
 */
function handle_pdo_exception(PDOException $e, string $fallback): void
{
    error_log('PoloNav SQL error: ' . $e->getMessage());

    $sqlState = $e->getCode();
    $message = $e->getMessage();

    if ($sqlState === '23000') {
        if (stripos($message, 'uq_users_username') !== false) {
            json_error('That username is already taken.', 409);
        }
        if (stripos($message, 'uq_drivers_license_number') !== false) {
            json_error('License number already exists.', 409);
        }
        if (stripos($message, 'uq_jeepney_plate_number') !== false) {
            json_error('Plate number already exists.', 409);
        }
        if (stripos($message, 'fk_jeepney_drivers') !== false || stripos($message, 'trips') !== false) {
            json_error($fallback, 409);
        }
        json_error($fallback, 409);
    }

    json_error($fallback, 500);
}

function record_exists(PDO $pdo, string $table, string $idColumn, int $id): bool
{
    $allowed = [
        'users'      => 'id',
        'passengers' => 'passengers_id',
        'drivers'    => 'id',
        'jeepney'    => 'id',
        'routes'     => 'id',
        'trips'      => 'id',
    ];

    if (!isset($allowed[$table]) || $allowed[$table] !== $idColumn) {
        return false;
    }

    $sql = "SELECT 1 FROM {$table} WHERE {$idColumn} = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    return (bool) $stmt->fetchColumn();
}

function count_where(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}
