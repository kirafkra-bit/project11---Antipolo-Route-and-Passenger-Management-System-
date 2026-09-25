<?php
/**
 * HTML page helpers for PoloNav (not used by JSON APIs).
 */

function web_base_path(): string
{
    $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $app = rtrim(str_replace('\\', '/', APP_BASE_PATH), '/');

    if ($docRoot !== '' && strpos($app, $docRoot) === 0) {
        $base = substr($app, strlen($docRoot));
        return $base === '' ? '' : $base;
    }

    return '';
}

function web_url(string $path = ''): string
{
    return rtrim(web_base_path() . '/' . ltrim($path, '/'), '/') ?: '/';
}

function web_redirect(string $path): void
{
    header('Location: ' . web_url($path));
    exit;
}

function require_web_login(): array
{
    $user = current_user();
    if ($user === null) {
        web_redirect('signin.php');
    }
    return $user;
}

function require_web_role(array $roles): array
{
    $user = require_web_login();
    if (!in_array($user['role'], $roles, true)) {
        web_redirect(ltrim(dashboard_path_for_role($user['role']), '/'));
    }
    return $user;
}

function role_theme(string $role): string
{
    if ($role === 'admin') {
        return 'theme-ops';
    }
    if ($role === 'driver') {
        return 'theme-cab';
    }
    return 'theme-ride';
}

function role_label(string $role): string
{
    $labels = [
        'admin'     => 'Administrator',
        'driver'    => 'Driver',
        'passenger' => 'Passenger',
    ];
    return $labels[$role] ?? $role;
}

function nav_for_role(string $role): array
{
    if ($role === 'admin') {
        return [
            [
                'label' => 'Console',
                'items' => [
                    ['id' => 'dashboard', 'href' => 'admin/dashboard.php', 'label' => 'Overview', 'icon' => 'bi-speedometer2'],
                    ['id' => 'complaints', 'href' => 'admin/complaints.php', 'label' => 'Complaints', 'icon' => 'bi-megaphone'],
                ],
            ],
            [
                'label' => 'People',
                'items' => [
                    ['id' => 'users', 'href' => 'admin/users.php', 'label' => 'Accounts', 'icon' => 'bi-person-gear'],
                    ['id' => 'passengers', 'href' => 'admin/passengers.php', 'label' => 'Passengers', 'icon' => 'bi-people'],
                    ['id' => 'drivers', 'href' => 'admin/drivers.php', 'label' => 'Drivers', 'icon' => 'bi-person-badge'],
                ],
            ],
            [
                'label' => 'Fleet',
                'items' => [
                    ['id' => 'jeepneys', 'href' => 'admin/jeepneys.php', 'label' => 'Jeepneys', 'icon' => 'bi-truck-front'],
                    ['id' => 'routes', 'href' => 'admin/routes.php', 'label' => 'Routes', 'icon' => 'bi-signpost-2'],
                ],
            ],
            [
                'label' => 'Records',
                'items' => [
                    ['id' => 'trips', 'href' => 'admin/trips.php', 'label' => 'All trips', 'icon' => 'bi-journal-text'],
                ],
            ],
        ];
    }

    if ($role === 'driver') {
        return [
            [
                'label' => 'Cab',
                'items' => [
                    ['id' => 'dashboard', 'href' => 'driver/dashboard.php', 'label' => 'Shift board', 'icon' => 'bi-clock-history'],
                    ['id' => 'jeepney', 'href' => 'driver/jeepney.php', 'label' => 'My jeepney', 'icon' => 'bi-truck-front'],
                    ['id' => 'Assigned Route', 'href' => 'driver/assigned-route.php', 'label' => 'Assigned Route', 'icon' => 'bi-signpost-2'],
                ],  
            ],
            [
                'label' => 'On the road',
                'items' => [
                    ['id' => 'log', 'href' => 'driver/trips.php', 'label' => 'Log a trip', 'icon' => 'bi-geo-alt'],
                    ['id' => 'fare', 'href' => 'driver/fare.php', 'label' => 'Fare check', 'icon' => 'bi-cash-coin'],
                    ['id' => 'Trip History', 'href' => 'driver/trip-history.php', 'label' => 'Trip History', 'icon' => 'bi-clock'],
                ],
            ],
            [
                'label' => 'Profile & Settings',
                'items' => [
                    ['id' => 'Profile', 'href' => 'driver/config.php', 'label' => 'Profile', 'icon' => 'bi-person'],
                    ['id' => 'Settings', 'href' => 'driver/config.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
                    ['id' => 'Help', 'href' => 'driver/help.php', 'label' => 'Help', 'icon' => 'bi-question-circle'],
                ],
            ],
        ];
    }

    return [
        [
            'label' => 'Ride',
            'items' => [
                ['id' => 'dashboard', 'href' => 'passenger/dashboard.php', 'label' => 'Home', 'icon' => 'bi-house'],
                ['id' => 'search', 'href' => 'passenger/search.php', 'label' => 'Find a jeepney', 'icon' => 'bi-search'],
            ],
        ],
        [
            'label' => 'My travel',
            'items' => [
                ['id' => 'trips', 'href' => 'passenger/trips.php', 'label' => 'My trips', 'icon' => 'bi-receipt'],
                ['id' => 'fare', 'href' => 'passenger/fare.php', 'label' => 'Estimate fare', 'icon' => 'bi-cash-coin'],
                ['id' => 'complaints', 'href' => 'passenger/complaints.php', 'label' => 'Complaints & reports', 'icon' => 'bi-megaphone'],
                ['id' => 'favorites', 'href' => 'passenger/favorites.php', 'label' => 'Favorite destinations', 'icon' => 'bi-star'],
                ['id' => 'announcements', 'href' => 'passenger/announcements.php', 'label' => 'Announcements', 'icon' => 'bi-broadcast'],
            ],
        ],
        [
            'label' => 'Profile & Settings',
            'items' => [
                ['id' => 'Profile', 'href' => 'passenger/config.php', 'label' => 'Profile', 'icon' => 'bi-person'],
                ['id' => 'Settings', 'href' => 'passenger/config.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
                ['id' => 'Help', 'href' => 'passenger/help.php', 'label' => 'Help', 'icon' => 'bi-question-circle'],
                
            ],
        ],
    ];
}

function initials_from_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return 'PN';
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }
    return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}

function layout_start(array $user, array $options): void
{
    $title = $options['title'] ?? APP_NAME;
    $active = $options['active'] ?? '';
    $page = $options['page'] ?? $active;
    $scripts = $options['scripts'] ?? [];
    $theme = role_theme($user['role']);
    $nav = nav_for_role($user['role']);
    $csrf = csrf_token();
    $base = web_base_path();
    $displayName = $user['username'];
    $roleName = role_label($user['role']);
    $product = $user['role'] === 'admin'
        ? 'PoloNav Operations'
        : ($user['role'] === 'driver' ? 'PoloNav Cab' : 'PoloNav Passenger');

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en" class="<?= htmlspecialchars($theme) ?>"
      data-base="<?= htmlspecialchars($base) ?>"
      data-csrf="<?= htmlspecialchars($csrf) ?>"
      data-role="<?= htmlspecialchars($user['role']) ?>"
      data-page="<?= htmlspecialchars($page) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> · <?= htmlspecialchars($product) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(web_url('assets/css/polonav.css')) ?>">
</head>
<body class="app-body">
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="<?= htmlspecialchars(web_url(ltrim(dashboard_path_for_role($user['role']), '/'))) ?>">
            <span class="brand-mark" aria-hidden="true">PN</span>
            <span>
                <strong><?= htmlspecialchars($product) ?></strong>
                <small>Jeepney navigation</small>
            </span>
        </a>

        <div class="side-user">
            <span class="avatar"><?= htmlspecialchars(initials_from_name($displayName)) ?></span>
            <span>
                <strong><?= htmlspecialchars($displayName) ?></strong>
                <small><?= htmlspecialchars($roleName) ?></small>
            </span>
        </div>

        <nav class="side-nav">
            <?php foreach ($nav as $group): ?>
                <p class="nav-label"><?= htmlspecialchars($group['label']) ?></p>
                <?php foreach ($group['items'] as $item): ?>
                    <a class="nav-link<?= $active === $item['id'] ? ' is-active' : '' ?>"
                       href="<?= htmlspecialchars(web_url($item['href'])) ?>">
                        <?php if (!empty($item['icon'])): ?><i class="bi <?= htmlspecialchars($item['icon']) ?> nav-icon" aria-hidden="true"></i><?php endif; ?>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <div>
                <p class="eyebrow"><?= htmlspecialchars($product) ?></p>
                <h1><?= htmlspecialchars($title) ?></h1>
            </div>
            <div class="topbar-actions">
                <span class="chip"><?= htmlspecialchars($roleName) ?></span>
                <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('signout.php')) ?>">Sign out</a>
            </div>
        </header>
        <main class="main">
    <?php
}

function layout_end(array $scripts = []): void
{
    ?>
        </main>
    </div>
</div>
<div id="toast" class="toast" hidden></div>
<div id="modal" class="modal" hidden>
    <div class="modal-card">
        <div class="modal-head">
            <h2 id="modal-title">Form</h2>
            <button type="button" class="icon-btn" data-close-modal aria-label="Close">×</button>
        </div>
        <div id="modal-body"></div>
    </div>
</div>
<script src="<?= htmlspecialchars(web_url('assets/js/api.js')) ?>"></script>
<script src="<?= htmlspecialchars(web_url('assets/js/ui.js')) ?>"></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= htmlspecialchars(web_url('assets/js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}

function public_layout_start(string $title): void
{
    $base = web_base_path();
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en" class="theme-public" data-base="<?= htmlspecialchars($base) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> · PoloNav</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(web_url('assets/css/polonav.css')) ?>">
</head>
<body class="public-body">
    <?php
}

function public_layout_end(array $scripts = []): void
{
    ?>
<script src="<?= htmlspecialchars(web_url('assets/js/api.js')) ?>"></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= htmlspecialchars(web_url('assets/js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}