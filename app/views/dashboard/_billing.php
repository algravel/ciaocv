<!-- Forfait actuel -->
<?php
$planName = $planName ?? 'Découverte';
$kpiForfaitLimit = $kpiForfaitLimit ?? 5;
$kpiForfaitUsed = $kpiForfaitUsed ?? 0;
$planSub = ($kpiForfaitLimit >= 9999) ? 'Illimité' : (($kpiForfaitLimit <= 5) ? 'Gratuit' : $kpiForfaitLimit . ' entrevues');
?>
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
            <div class="plan-name"><?= e($planName) ?> <span class="plan-suffix">— <?= e($planSub) ?></span></div>
            <div class="subtitle-muted"><?= $kpiForfaitLimit >= 9999 ? 'Entrevues illimitées' : $kpiForfaitUsed . ' / ' . $kpiForfaitLimit . ' entrevues utilisées' ?> &middot; Questions personnalisées</div>
        </div>
    </div>
</div>

<!-- Changer de forfait -->
<div class="card mb-6">
    <h2 class="card-title mb-5">Changer de forfait</h2>
    <div class="pricing-grid">
        <?php
        $plans = $billingPlans ?? [];
        foreach ($plans as $plan): ?>
        <div class="pricing-card <?= $plan['popular'] ? 'pricing-card--popular' : '' ?>">
            <?php if ($plan['popular']): ?><span class="pricing-badge">Populaire</span><?php endif; ?>
            <div class="pricing-name"><?= e($plan['name']) ?></div>
            <div class="pricing-price"><?= e($plan['price']) ?><?php if (!empty($plan['priceSuffix'])): ?><span class="pricing-suffix"><?= e($plan['priceSuffix']) ?></span><?php endif; ?></div>
            <div class="pricing-sub"><?= e($plan['sub']) ?></div>
            <ul class="pricing-features">
                <?php foreach ($plan['features'] as $f): ?>
                <li><i class="fa-solid fa-check pricing-check"></i> <?php
                    if (is_array($f) && isset($f['i18n'], $f['text'])) {
                        echo '<span data-i18n="' . e($f['i18n']) . '">' . e($f['text']) . '</span>';
                    } else {
                        echo e($f);
                    }
                ?></li>
                <?php endforeach; ?>
            </ul>
            <button class="btn btn-primary btn--full"><?= e($plan['cta']) ?></button>
        </div>
        <?php endforeach; ?>
        <?php if (empty($plans)): ?>
        <p class="text-muted">Aucun forfait disponible. Contactez-nous pour plus d'informations.</p>
        <?php endif; ?>
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
