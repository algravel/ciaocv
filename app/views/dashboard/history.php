<div class="content-section active">
    <div class="page-header">
        <h1 class="page-title" data-i18n="history_title">Historique des événements</h1>
        <div class="header-actions">
            <a href="/tableau-de-bord" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Retour</span>
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title" data-i18n="events_title">Journal complet</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th data-i18n="th_date">Date</th>
                    <th data-i18n="th_user">Utilisateur</th>
                    <th data-i18n="th_action">Action</th>
                    <th data-i18n="th_details">Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Use strict types for variables to avoid warnings
                $evts = $events ?? [];
                $badgeMap = [
                    'creation' => 'event-badge--creation',
                    'modification' => 'event-badge--modification',
                    'suppression' => 'event-badge--suppression',
                    'update' => 'event-badge--modification',
                    'create' => 'event-badge--creation',
                    'delete' => 'event-badge--suppression',
                    'evaluation' => 'event-badge--evaluation',
                    'invitation' => 'event-badge--invitation'
                ];
                // French months
                $moisFr = ['Jan' => 'janv', 'Feb' => 'fév', 'Mar' => 'mars', 'Apr' => 'avr', 'May' => 'mai', 'Jun' => 'juin', 'Jul' => 'juil', 'Aug' => 'août', 'Sep' => 'sept', 'Oct' => 'oct', 'Nov' => 'nov', 'Dec' => 'déc'];

                if (empty($evts)): ?>
                    <tr>
                        <td colspan="4" class="cell-muted" data-i18n="events_empty">Aucun événement enregistré.</td>
                    </tr>
                <?php else:
                    foreach ($evts as $ev):
                        // Safely handle date parsing
                        $ts = strtotime($ev['created_at']);
                        if ($ts === false)
                            $ts = time();
                        $d = date('j M Y, H:i', $ts);

                        // Translate month
                        $createdFormatted = preg_replace_callback('/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/', function ($m) use ($moisFr) {
                            return $moisFr[$m[1]] ?? $m[1]; }, $d);

                        // Determine badge class
                        $type = strtolower($ev['action_type'] ?? 'modification');
                        $badgeClass = $badgeMap[$type] ?? 'event-badge--modification';

                        // Safely handle details
                        $details = $ev['details'] ?? '';
                        $userName = $ev['user_name'] ?? 'Inconnu'; // Handle potentially missing user_name if query changed
                        ?>
                        <tr>
                            <td class="cell-date">
                                <?= htmlspecialchars($createdFormatted) ?>
                            </td>
                            <td><strong>
                                    <?= htmlspecialchars($userName) ?>
                                </strong></td>
                            <td><span class="event-badge <?= htmlspecialchars($badgeClass) ?>">
                                    <?= htmlspecialchars(ucfirst($type)) ?>
                                </span></td>
                            <td class="cell-muted">
                                <?= htmlspecialchars($details) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>