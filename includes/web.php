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
                    ['id' => 'announcements', 'href' => 'admin/announcements.php', 'label' => 'Announcements', 'icon' => 'bi-megaphone'],
                    ['id' => 'complaints', 'href' => 'admin/complaints.php', 'label' => 'Complaints', 'icon' => 'bi-megaphone'],
                ],
            ],
            [
                'label' => 'People',
                'items' => [
                    ['id' => 'users', 'href' => 'admin/users.php', 'label' => 'Accounts', 'icon' => 'bi-person-gear'],
                    ['id' => 'passengers', 'href' => 'admin/passengers.php', 'label' => 'Passengers', 'icon' => 'bi-people'],
                    ['id' => 'drivers', 'href' => 'admin/drivers.php', 'label' => 'Drivers', 'icon' => 'bi-person-badge'],
                    ['id' => 'route-requests', 'href' => 'admin/route-requests.php', 'label' => 'Route requests', 'icon' => 'bi-signpost'],
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
            ['label' => 'General', 'items' => [
                ['id' => 'announcements', 'href' => 'driver/announcements.php', 'label' => 'Announcements', 'icon' => 'bi-megaphone', 'badge' => '2'],
                ['id' => 'emergency', 'label' => 'Emergency / SOS', 'icon' => 'bi-exclamation-triangle', 'action' => 'sos'],
            ]],
            ['label' => 'Driver', 'items' => [
                ['id' => 'trips', 'label' => 'Trips', 'icon' => 'bi-signpost-split', 'children' => [
                    ['id' => 'trips-start', 'href' => 'trips/start/', 'label' => 'Start Trip', 'icon' => 'bi-play-circle'],
                    ['id' => 'trips-passengers', 'href' => 'trips/passengers/', 'label' => 'Passengers', 'icon' => 'bi-people'],
                    ['id' => 'trips-finish', 'href' => 'trips/finish/', 'label' => 'Finish Trip', 'icon' => 'bi-flag'],
                    ['id' => 'trips-summary', 'href' => 'trips/summary/', 'label' => 'Trip Summary', 'icon' => 'bi-bar-chart'],
                    ['id' => 'trips-confirm', 'href' => 'trips/confirm/', 'label' => 'Confirm Trip', 'icon' => 'bi-check-circle'],
                ]],
                ['id' => 'my-route', 'href' => 'driver/my-route.php', 'label' => 'My Route', 'icon' => 'bi-signpost-2'],
                ['id' => 'trips-history', 'href' => 'trips/history/', 'label' => 'Trip History', 'icon' => 'bi-clock-history'],
                ['id' => 'earnings', 'href' => 'driver/earnings.php', 'label' => 'Daily Earnings & Fares', 'icon' => 'bi-cash-coin'],
                ['id' => 'incidents', 'href' => 'driver/traffic.php', 'label' => 'Traffic & Incident Reports', 'icon' => 'bi-cone-striped'],
            ]],
            ['label' => 'Profile & Settings', 'items' => [
                ['id' => 'Profile', 'href' => 'driver/config.php', 'label' => 'Profile', 'icon' => 'bi-person'],
                ['id' => 'Settings', 'href' => 'driver/config.php?tab=settings', 'label' => 'Settings', 'icon' => 'bi-gear', 'quick_toggles' => [
                    ['id' => 'voice-guidance', 'label' => 'Voice Guidance'],
                    ['id' => 'offline-maps', 'label' => 'Offline Maps'],
                ]],
                ['id' => 'Help', 'href' => 'driver/help.php', 'label' => 'Help', 'icon' => 'bi-question-circle'],
            ]],
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
                ['id' => 'Settings', 'href' => 'passenger/config.php?tab=settings', 'label' => 'Settings', 'icon' => 'bi-gear'],
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
        <?php if ($user['role'] === 'passenger'): ?>
            <a class="passenger-side-brand" href="<?= htmlspecialchars(web_url('passenger/dashboard.php')) ?>"><span class="passenger-side-mark" aria-hidden="true">P</span><span><strong>PoloNav Passenger</strong><small>Jeepney navigation</small></span></a>
        <?php elseif ($user['role'] === 'driver'): ?>
            <a class="brand driver-sidebar-brand" href="<?= htmlspecialchars(web_url('driver/dashboard.php')) ?>"><span class="brand-mark" aria-hidden="true">P</span><span><strong>PoloNav Cab</strong><small>Jeepney Navigation</small></span></a>
        <?php else: ?>
            <a class="brand" href="<?= htmlspecialchars(web_url(ltrim(dashboard_path_for_role($user['role']), '/'))) ?>"><span class="brand-mark" aria-hidden="true">PN</span><span><strong><?= htmlspecialchars($product) ?></strong><small>Jeepney navigation</small></span></a>
        <?php endif; ?>

        <?php if ($user['role'] === 'driver'): ?>
            <section id="driver-sidebar-card" class="driver-sidebar-card" data-user-id="<?= htmlspecialchars((string) $user['id']) ?>" aria-label="Driver and shift status">
                <div class="driver-sidebar-identity">
                    <span class="avatar" id="driver-sidebar-avatar"><?= htmlspecialchars(initials_from_name($displayName)) ?></span>
                    <span class="driver-sidebar-identity-copy">
                        <strong id="driver-sidebar-name"><?= htmlspecialchars($displayName) ?></strong>
                        <small>Driver</small>
                    </span>
                </div>
                <p class="driver-sidebar-vehicle" id="driver-sidebar-vehicle"><i class="bi bi-truck-front" aria-hidden="true"></i><span>Loading assigned vehicle...</span></p>
                <div class="driver-shift-control">
                    <span class="driver-shift-label">Shift status <small>Device only</small></span>
                    <div class="driver-shift-options" role="group" aria-label="Set local shift status">
                        <button type="button" data-shift-status="On Duty" aria-pressed="false">On Duty</button>
                        <button type="button" data-shift-status="On Break" aria-pressed="false">On Break</button>
                        <button type="button" data-shift-status="Off Duty" aria-pressed="false">Off Duty</button>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <div class="side-user">
                <span class="avatar"><?= htmlspecialchars(initials_from_name($displayName)) ?></span>
                <span>
                    <strong><?= htmlspecialchars($displayName) ?></strong>
                    <small><?= htmlspecialchars($roleName) ?></small>
                </span>
            </div>
        <?php endif; ?>

        <nav class="side-nav<?= $user['role'] === 'driver' ? ' driver-side-nav' : '' ?>">
            <?php foreach ($nav as $group): ?>
                <p class="nav-label"><?= htmlspecialchars($group['label']) ?></p>
                <?php foreach ($group['items'] as $item): ?>
                    <?php if (!empty($item['children'])): ?>
                        <?php $tripNavExpanded = strpos($page, 'trips-') === 0 && $page !== 'trips-history'; ?>
                        <button class="nav-link nav-group-toggle<?= $tripNavExpanded ? ' is-active' : '' ?>"
                                type="button" data-nav-toggle="trips" aria-expanded="<?= $tripNavExpanded ? 'true' : 'false' ?>">
                            <?php if (!empty($item['icon'])): ?><i class="bi <?= htmlspecialchars($item['icon']) ?> nav-icon" aria-hidden="true"></i><?php endif; ?>
                            <?= htmlspecialchars($item['label']) ?><i class="bi bi-chevron-down nav-caret" aria-hidden="true"></i>
                        </button>
                        <div class="nav-submenu" data-nav-submenu="trips"<?= $tripNavExpanded ? '' : ' hidden' ?>>
                            <?php foreach ($item['children'] as $child): ?>
                                <a class="nav-link nav-sublink<?= $active === $child['id'] ? ' is-active' : '' ?>"
                                   href="<?= htmlspecialchars(web_url($child['href'])) ?>"
                                   data-trip-step="<?= htmlspecialchars($child['id']) ?>"
                                   <?= $active === $child['id'] ? 'aria-current="page"' : '' ?>>
                                    <i class="bi <?= htmlspecialchars($child['icon']) ?> nav-icon" aria-hidden="true"></i><?= htmlspecialchars($child['label']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($item['action'])): ?>
                            <button class="nav-link nav-action" type="button" data-sidebar-action="<?= htmlspecialchars($item['action']) ?>">
                                <?php if (!empty($item['icon'])): ?><i class="bi <?= htmlspecialchars($item['icon']) ?> nav-icon" aria-hidden="true"></i><?php endif; ?>
                                <?= htmlspecialchars($item['label']) ?>
                                <?php if (!empty($item['badge'])): ?><span class="nav-badge"><?= htmlspecialchars($item['badge']) ?></span><?php endif; ?>
                            </button>
                        <?php elseif (!empty($item['disabled'])): ?>
                            <span class="nav-link nav-link-disabled" aria-disabled="true" title="Coming soon">
                                <?php if (!empty($item['icon'])): ?><i class="bi <?= htmlspecialchars($item['icon']) ?> nav-icon" aria-hidden="true"></i><?php endif; ?>
                                <?= htmlspecialchars($item['label']) ?><small>Soon</small>
                            </span>
                        <?php else: ?>
                            <a class="nav-link<?= $active === $item['id'] ? ' is-active' : '' ?>"
                               href="<?= htmlspecialchars(web_url($item['href'])) ?>">
                                <?php if (!empty($item['icon'])): ?><i class="bi <?= htmlspecialchars($item['icon']) ?> nav-icon" aria-hidden="true"></i><?php endif; ?>
                                <?= htmlspecialchars($item['label']) ?>
                                <?php if (!empty($item['badge'])): ?><span class="nav-badge"><?= htmlspecialchars($item['badge']) ?></span><?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($item['quick_toggles'])): ?>
                            <div class="driver-sidebar-quick-settings" aria-label="<?= htmlspecialchars($item['label']) ?> quick settings">
                                <p>UI preferences only; navigation integrations are not connected.</p>
                                <?php foreach ($item['quick_toggles'] as $toggle): ?>
                                    <button type="button" role="switch" aria-checked="false" data-driver-preference="<?= htmlspecialchars($toggle['id']) ?>" title="Saved on this device; feature integration is not connected">
                                        <span><?= htmlspecialchars($toggle['label']) ?></span><i aria-hidden="true"></i>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <?php if ($user['role'] === 'passenger'): ?><a class="sidebar-signout" href="<?= htmlspecialchars(web_url('signout.php')) ?>"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out</a><?php endif; ?>
        <?php if ($user['role'] === 'driver'): ?><a class="sidebar-signout" href="<?= htmlspecialchars(web_url('signout.php')) ?>"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Log Out</a><?php endif; ?>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <?php if ($user['role'] === 'passenger'): ?>
                <a class="passenger-brand" href="<?= htmlspecialchars(web_url('passenger/dashboard.php')) ?>"><span class="passenger-brand-mark" aria-hidden="true">P</span><span><strong>PoloNav</strong><small>SMARTER CITY TRAVEL</small></span></a>
                <div class="topbar-actions"><span class="user-pill"><i class="bi bi-person" aria-hidden="true"></i><?= htmlspecialchars($displayName) ?></span><span class="chip passenger-role">Passenger</span></div>
            <?php elseif ($user['role'] === 'driver'): ?>
                <div class="driver-top-page-title"><span><?= htmlspecialchars($title) ?></span></div>
                <div class="topbar-actions driver-top-actions"><i class="bi bi-person" aria-hidden="true"></i><strong id="driver-header-name"><?= htmlspecialchars($displayName) ?></strong><span class="chip">Driver</span></div>
            <?php else: ?>
                <div><p class="eyebrow"><?= htmlspecialchars($product) ?></p><h1><?= htmlspecialchars($title) ?></h1></div>
                <div class="topbar-actions"><span class="chip"><?= htmlspecialchars($roleName) ?></span><a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('signout.php')) ?>">Sign out</a></div>
            <?php endif; ?>
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
