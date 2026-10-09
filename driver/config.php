<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['driver']);
$isSettings = query_param('tab') === 'settings';
$pageTitle = $isSettings ? 'Settings' : 'Profile';
layout_start($user, ['title' => $pageTitle, 'active' => $pageTitle, 'page' => $isSettings ? 'settings' : 'profile']);
?>
<?php if ($isSettings): ?>
<header class="page-heading"><h2>Settings</h2><p>Manage your preferences and notifications.</p></header>
<section id="driver-settings" class="driver-settings" data-user-id="<?= htmlspecialchars((string) $user['id']) ?>">
    <article class="card driver-settings-card">
        <h3>Notifications</h3>
        <label class="driver-setting-row">
            <span><strong>Trip reminders</strong><small>Receive reminders before your shift starts</small></span>
            <input type="checkbox" data-setting="tripReminders" checked>
        </label>
        <label class="driver-setting-row">
            <span><strong>Passenger alerts</strong><small>Get notified when a passenger books on your route</small></span>
            <input type="checkbox" data-setting="passengerAlerts" checked>
        </label>
        <label class="driver-setting-row">
            <span><strong>System announcements</strong><small>Updates from PoloNav operations team</small></span>
            <input type="checkbox" data-setting="systemAnnouncements">
        </label>
    </article>
    <article class="card driver-settings-card">
        <h3>Display</h3>
        <label class="driver-setting-row">
            <span><strong>Compact mode</strong><small>Reduce spacing for more content on screen</small></span>
            <input type="checkbox" data-setting="compactMode">
        </label>
        <label class="driver-language-setting" for="driver-language">
            <span>Language</span>
            <select id="driver-language" data-setting="language">
                <option value="en">English</option>
                <option value="fil">Filipino</option>
            </select>
        </label>
    </article>
    <article class="card driver-settings-card">
        <h3>Privacy</h3>
        <label class="driver-setting-row">
            <span><strong>Share contact with passengers</strong><small>Allow passengers to see your contact number</small></span>
            <input type="checkbox" data-setting="shareContact" checked>
        </label>
    </article>
</section>
<?php else: ?>
<header class="page-heading"><h2>Profile</h2><p>Your driver account and verification information.</p></header>
<section class="grid two">
    <article class="card">
        <div class="profile-header">
            <div class="profile-avatar" id="driver-profile-avatar"><?= htmlspecialchars(initials_from_name($user['username'])) ?></div>
            <div class="profile-main"><h3 id="driver-profile-name"><?= htmlspecialchars($user['username']) ?></h3><span class="chip neutral">Driver</span></div>
        </div>
        <div class="profile-fields">
            <div class="field-row"><label>Full name</label><div class="value-box" id="driver-profile-full-name">Loading...</div></div>
            <div class="field-row"><label>Username</label><div class="value-box" id="driver-profile-username"><?= htmlspecialchars($user['username']) ?></div></div>
            <div class="field-row"><label>Contact number</label><div class="value-box" id="driver-profile-contact">Loading...</div></div>
            <div class="field-row"><label>License number</label><div class="value-box" id="driver-profile-license">Loading...</div></div>
            <div class="field-row"><label>Account status</label><div class="value-box" id="driver-profile-status">Loading...</div></div>
        </div>
    </article>
    <article class="card">
        <h3>Verification documents</h3>
        <p class="muted">Your submitted documents are reviewed by an administrator. Contact the operations team if you need to update them.</p>
        <p class="muted">Profile editing is not available yet.</p>
    </article>
    <article class="card driver-password-card">
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
</section>
<?php endif; ?>
<?php layout_end($isSettings ? ['driver.js', 'driver-sidebar.js'] : ['driver.js', 'account-settings.js', 'driver-sidebar.js']); ?>
