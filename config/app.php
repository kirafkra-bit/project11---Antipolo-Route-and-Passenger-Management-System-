<?php
/**
 * Application settings. Server environment values take precedence over the
 * project .env file. Keep .env outside version control and block HTTP access.
 */

define('APP_NAME', 'PoloNav');
define('APP_BASE_PATH', dirname(__DIR__));

function app_env_value(string $name): string
{
    $serverValue = getenv($name);
    if ($serverValue !== false && $serverValue !== '') {
        return $serverValue;
    }

    $envPath = APP_BASE_PATH . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($envPath)) {
        return '';
    }

    $contents = file_get_contents($envPath);
    if ($contents === false) {
        throw new RuntimeException('Unable to read the project .env file.');
    }

    foreach (preg_split('/\r\n|\n|\r/', $contents) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#'
            || !preg_match('/^' . preg_quote($name, '/') . '\s*=\s*(.*)$/', $line, $matches)) {
            continue;
        }

        $value = trim($matches[1]);
        if (strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"')
                || ($value[0] === '\'' && substr($value, -1) === '\''))) {
            return substr($value, 1, -1);
        }

        return preg_replace('/\s+#.*$/', '', $value) ?? $value;
    }

    return '';
}

define('GOOGLE_MAPS_API_KEY', '');
define('TOMTOM_TRAFFIC_API_KEY', app_env_value('TOMTOM_TRAFFIC_API_KEY'));

define('PASSWORD_MIN_LENGTH', 8);
