<div class="content-section active">
    <div class="page-header">
        <h1 class="page-title">Debug</h1>
        <a href="<?= GESTION_BASE_PATH ?>/debug" class="btn btn-primary">
            <i class="fa-solid fa-arrows-rotate"></i> Rafraîchir
        </a>
    </div>
    <div class="card">
        <?php if ($result['ok']): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
            <strong>Connexion :</strong> <?= e($result['connection']) ?>
        </div>
        <?php if (!empty($result['encryption_key'])): ?>
        <div style="background: #eff6ff; color: #1e40af; padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
            <strong>Clé de chiffrement :</strong> <?= e($result['encryption_key']) ?>
        </div>
        <?php endif; ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Présente</th>
                    <th style="text-align: right;">Lignes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['tables'] as $table => $info): ?>
                <tr>
                    <td><code><?= e($table) ?></code></td>
                    <td>
                        <?php if ($info['exists']): ?>
                        <span style="color: #059669;">✓ Oui</span>
                        <?php else: ?>
                        <span style="color: #dc2626;">✗ Non</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;"><?= e((string) $info['count']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 12px;">
            <strong>Erreur :</strong> <?= e($result['error']) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
