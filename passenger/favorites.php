<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/web.php';
$user = require_web_role(['passenger']);
layout_start($user, ['title' => 'Favorite destinations', 'active' => 'favorites', 'page' => 'favorites']);
?>
<header class="page-heading"><h2>Favorite destinations</h2><p>Keep the places you visit often close at hand.</p></header>
<section class="card favorites-panel">
    <div class="section-heading"><div><h3>Your saved places</h3><p class="muted">Favorites are saved on this device.</p></div>
        <a class="btn btn-ghost" href="<?= htmlspecialchars(web_url('passenger/fare.php')) ?>">Estimate a fare</a></div>
    <form id="favorite-form" class="favorite-form">
        <label class="search-input-wrap" for="favorite-name"><i class="bi bi-geo-alt" aria-hidden="true"></i><input id="favorite-name" name="destination" maxlength="100" placeholder="Add a destination" required></label>
        <button class="btn" type="submit">Save destination</button>
    </form>
    <div id="favorite-list" class="favorite-list"><p class="empty">No favorite destinations saved yet.</p></div>
</section>
<?php layout_end(['passenger.js']); ?>