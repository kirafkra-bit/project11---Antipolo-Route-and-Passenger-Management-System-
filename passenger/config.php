<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
$isSettings = query_param('tab') === 'settings';
$pageTitle = $isSettings ? 'Settings' : 'Profile';
layout_start($user, ['title' => $pageTitle, 'active' => $pageTitle, 'page' => $isSettings ? 'settings' : 'profile']);
?>
<?php if ($isSettings): ?>
<header class="page-heading"><h2>Settings</h2><p>Manage your passenger account security.</p></header>
<section class="grid two">
    <article class="card">
        <h3>Change password</h3>
        <p class="muted">Choose a password with at least 8 characters.</p>
        <form id="account-password-form" class="stack">
            <div><label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required></div>
            <div><label for="new-password">New password</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="8" required></div>
            <div><label for="confirm-password">Confirm new password</label><input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required></div>
            <p id="password-form-message" class="muted" role="status" hidden></p>
            <button class="btn" type="submit">Update password</button>
        </form>
    </article>
    <article class="card"><h3>Account</h3><p>Signed in as <strong><?= htmlspecialchars($user['username']) ?></strong>.</p><p class="muted">Your account role is Passenger.</p></article>
</section>
<?php else: ?>
<header class="page-heading"><h2>Profile</h2><p>Your passenger account information.</p></header>
<section class="profile-layout"><article class="card profile-card">
    <div class="profile-header">
        <div class="profile-avatar" id="profile-avatar"><?= htmlspecialchars(initials_from_name($user['username'])) ?></div>
        <div class="profile-main"><h3 id="profile-name"><?= htmlspecialchars($user['username']) ?></h3><span class="chip neutral">Passenger</span></div>
        <button class="btn btn-ghost profile-edit" type="button" disabled aria-label="Profile editing is not available yet">Edit</button>
    </div>
    <div class="profile-fields">
        <div class="field-row"><label>Username</label><div class="value-box" id="profile-username"><?= htmlspecialchars($user['username']) ?></div></div>
        <div class="field-row"><label>Full name</label><div class="value-box" id="profile-full-name">Loading...</div></div>
        <div class="field-row"><label>Contact number</label><div class="value-box" id="profile-contact">Loading...</div></div>
        <div class="field-row"><label>Email address</label><div class="value-box">Not provided</div></div>
        <div class="field-row"><label>Home address</label><div class="value-box" id="profile-address">Loading...</div></div>
        <div class="field-row"><label>Passenger type</label><div class="value-box">Regular</div></div>
    </div>
</article></section>
<?php endif; ?>
<?php layout_end($isSettings ? ['account-settings.js'] : ['passenger.js']); ?>
