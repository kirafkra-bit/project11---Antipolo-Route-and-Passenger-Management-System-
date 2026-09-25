<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/web.php';

if (current_user()) {
    web_redirect(ltrim(dashboard_path_for_role(current_user()['role']), '/'));
}

public_layout_start('Sign in');
?>
<div class="auth-page signin-page">
    <a class="auth-home-logo" href="<?= htmlspecialchars(web_url('index.php')) ?>" aria-label="Back to PoloNav landing page">
        <img src="<?= htmlspecialchars(web_url('assets/images/polonav.svg')) ?>" alt="PoloNav">
    </a>
    <div class="auth-showcase">
        <div class="auth-showcase-copy">
            <p class="landing-eyebrow">WELCOME BACK</p>
            <h1>Byahe <em>made easy.</em></h1>
            <p>Sign in to access PoloNav. Your routes, trips, and fare estimates — all in one place.</p>
        </div>
    </div>
    <div class="auth-panel">
        <p class="landing-eyebrow">WELCOME BACK</p>
        <h2>Sign in to PoloNav</h2>
        <p class="muted">Use the account your role was given. You will land on that role’s desk only.</p>
        <p id="auth-error" class="badge badge-off" hidden></p>
        <form id="signin-form" class="stack">
            <div>
                <label for="username">Username</label>
                <input id="username" name="username" required autocomplete="username">
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <button class="btn" type="submit">Enter PoloNav</button>
        </form>
        <p class="auth-foot">Need an account? <a href="<?= htmlspecialchars(web_url('signup.php')) ?>">Register here</a></p>
    </div>
</div>
<?php public_layout_end(['signin.js']); ?>
