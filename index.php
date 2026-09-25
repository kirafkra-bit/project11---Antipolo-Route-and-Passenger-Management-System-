<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/web.php';

if (current_user()) {
    web_redirect(ltrim(dashboard_path_for_role(current_user()['role']), '/'));
}

public_layout_start('Smarter city travel');
?>
<div class="landing-page">
    <header class="landing-nav">
        <nav class="landing-links" aria-label="Main navigation">
            <a href="#how-it-works">How it works</a>
            <a href="<?= htmlspecialchars(web_url('signin.php')) ?>">Sign in</a>
            <a class="landing-nav-cta" href="<?= htmlspecialchars(web_url('signup.php')) ?>">Create account</a>
        </nav>
    </header>

    <main class="landing-main">
        <section class="landing-hero">
            <div class="landing-copy">
                <p class="landing-eyebrow">WELCOME TO POLONAV</p>
                <h1>Tara <em>Byahe!</em></h1>
                <p class="landing-lede">Find practical routes, estimate fares, and keep every trip in one calm, clear workspace.</p>
                <div class="landing-actions">
                    <a class="landing-primary" href="<?= htmlspecialchars(web_url('signup.php')) ?>">Get started <span>→</span></a>
                    <a class="landing-secondary" href="<?= htmlspecialchars(web_url('signin.php')) ?>">I already have an account</a>
                </div>
            </div>
        </section>

        <span id="how-it-works" class="landing-footer">PoloNav · Smarter City Travel</span>
    </main>
</div>
<?php public_layout_end(); ?>
