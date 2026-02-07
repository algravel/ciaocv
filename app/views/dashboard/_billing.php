<!-- Forfait actuel -->
<div class="card card--featured mb-6">
    <div class="flex-between mb-6">
        <h2 class="card-title mb-0">Mon forfait</h2>
        <span class="event-badge event-badge--active">Actif</span>
    </div>
    <div class="flex-center gap-5">
        <div class="plan-icon">
            <i class="fa-solid fa-gem"></i>
        </div>
        <div>
            <div class="plan-name">Découverte <span class="plan-suffix">— Gratuit</span></div>
            <div class="subtitle-muted">1 affichage actif &middot; 5 candidats max &middot; Questions standards</div>
        </div>
    </div>
</div>

<!-- Changer de forfait -->
<div class="card mb-6">
    <h2 class="card-title mb-5">Changer de forfait</h2>
    <div class="pricing-grid">
        <?php
        $plans = [
            ['name' => 'À la carte', 'price' => '49$', 'sub' => 'paiement unique', 'features' => ['1 affichage', 'Accès 30 jours', 'Candidats illimités', 'Questions personnalisées'], 'cta' => 'Acheter', 'popular' => false],
            ['name' => 'Pro', 'price' => '99$', 'sub' => 'Facturé annuellement', 'priceSuffix' => '/mois', 'features' => ['5 affichages simultanés', '40 candidats / affichage', 'Outils collaboratifs', 'Marque employeur'], 'cta' => 'Passer au Pro', 'popular' => true],
            ['name' => 'Expert', 'price' => '169$', 'sub' => 'Facturé annuellement', 'priceSuffix' => '/mois', 'features' => ['10 affichages simultanés', '60 candidats / affichage', 'Support prioritaire', 'Accès API & Webhooks'], 'cta' => 'Passer à Expert', 'popular' => false],
        ];
        foreach ($plans as $plan): ?>
        <div class="pricing-card <?= $plan['popular'] ? 'pricing-card--popular' : '' ?>">
            <?php if ($plan['popular']): ?><span class="pricing-badge">Populaire</span><?php endif; ?>
            <div class="pricing-name"><?= e($plan['name']) ?></div>
            <div class="pricing-price"><?= e($plan['price']) ?><?php if (!empty($plan['priceSuffix'])): ?><span class="pricing-suffix"><?= e($plan['priceSuffix']) ?></span><?php endif; ?></div>
            <div class="pricing-sub"><?= e($plan['sub']) ?></div>
            <ul class="pricing-features">
                <?php foreach ($plan['features'] as $f): ?>
                <li><i class="fa-solid fa-check pricing-check"></i> <?= e($f) ?></li>
                <?php endforeach; ?>
            </ul>
            <button class="btn btn-primary btn--full"><?= e($plan['cta']) ?></button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Historique des factures -->
<div class="card">
    <h2 class="card-title mb-5">Historique des factures</h2>
    <table class="data-table">
        <thead><tr><th>Date</th><th>Description</th><th>Montant</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            <tr>
                <td class="cell-muted">6 fév 2026</td>
                <td><strong>Forfait Découverte</strong> — Activation</td>
                <td class="fw-semibold">0,00 $</td>
                <td><span class="event-badge event-badge--gratuit">Gratuit</span></td>
                <td><a href="#" class="download-link"><i class="fa-solid fa-download"></i></a></td>
            </tr>
        </tbody>
    </table>
    <div class="invoice-empty">
        <i class="fa-regular fa-file-lines"></i>
        Aucune autre facture pour le moment.
    </div>
</div>
