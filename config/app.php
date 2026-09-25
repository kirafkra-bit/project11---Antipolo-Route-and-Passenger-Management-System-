<?php
/**
 * Application settings.
 *
 * Maps (optional, for a later frontend):
 * Put your Google Maps JavaScript API key in GOOGLE_MAPS_API_KEY.
 * Leave it empty if you are not using maps yet.
 * The backend never needs to send this key to public JSON unless
 * a map page is built later and you choose to expose a restricted key.
 */

define('APP_NAME', 'PoloNav');
define('APP_BASE_PATH', dirname(__DIR__));

define('GOOGLE_MAPS_API_KEY', '');

define('PASSWORD_MIN_LENGTH', 8);
