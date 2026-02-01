<?php
/**
 * Header réutilisable pour l'onboarding avec barre de progression
 * 
 * Variables attendues:
 * - $currentStep (int) : étape actuelle (1-9)
 * - $stepTitle (string) : titre de l'étape
 */

$totalSteps = 9;
$progress = ($currentStep / $totalSteps) * 100;
?>
<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1e40af;
        --bg: #111827;
        --card-bg: #1f2937;
        --text: #f9fafb;
        --text-light: #9ca3af;
        --border: #374151;
        --success: #22c55e;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }

    body {
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
    }

    .onboarding-container {
        max-width: 480px;
        margin: 0 auto;
        padding: 1.5rem;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .onboarding-header {
        margin-bottom: 1.5rem;
    }

    .header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .logo {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--primary);
        text-decoration: none;
    }

    .step-indicator {
        font-size: 0.85rem;
        color: var(--text-light);
    }

    .progress-bar {
        height: 4px;
        background: var(--border);
        border-radius: 2px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: var(--primary);
        border-radius: 2px;
        transition: width 0.3s ease;
    }

    .step-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 1.5rem 0 0.5rem;
    }

    .step-subtitle {
        color: var(--text-light);
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
    }

    .onboarding-content {
        flex: 1;
    }

    .onboarding-footer {
        margin-top: auto;
        padding-top: 1.5rem;
    }

    .btn {
        width: 100%;
        padding: 1rem 1.5rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn:hover { background: var(--primary-dark); }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .btn-secondary {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--text-light);
        margin-top: 0.75rem;
    }

    .btn-secondary:hover {
        background: var(--card-bg);
        color: var(--text);
    }

    .btn-skip {
        background: transparent;
        border: none;
        color: var(--text-light);
        font-size: 0.9rem;
        margin-top: 1rem;
    }

    .btn-skip:hover { color: var(--text); }

    /* Cards sélectionnables */
    .option-cards {
        display: grid;
        gap: 0.75rem;
    }

    .option-card {
        background: var(--card-bg);
        border: 2px solid var(--border);
        border-radius: 1rem;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .option-card:hover {
        border-color: var(--primary);
    }

    .option-card.selected {
        border-color: var(--primary);
        background: rgba(37, 99, 235, 0.1);
    }

    .option-card input[type="radio"],
    .option-card input[type="checkbox"] {
        display: none;
    }

    .option-icon {
        font-size: 1.5rem;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(37, 99, 235, 0.2);
        border-radius: 0.75rem;
    }

    .option-card.selected .option-icon {
        background: var(--primary);
    }

    .option-text {
        flex: 1;
    }

    .option-title {
        font-weight: 600;
        margin-bottom: 0.2rem;
    }

    .option-desc {
        font-size: 0.85rem;
        color: var(--text-light);
    }

    /* Tags cliquables */
    .tags-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .tag {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 2rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .tag:hover {
        border-color: var(--primary);
    }

    .tag.selected {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .tag input[type="checkbox"] {
        display: none;
    }

    /* Form inputs */
    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
        color: var(--text-light);
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        background: var(--card-bg);
        color: var(--text);
        font-size: 1rem;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: var(--primary);
    }

    .form-group small {
        display: block;
        margin-top: 0.25rem;
        color: var(--text-light);
        font-size: 0.8rem;
    }

    /* Messages */
    .error {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .success {
        background: rgba(34, 197, 94, 0.2);
        color: #4ade80;
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    /* OAuth buttons (disabled) */
    .oauth-divider {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
        color: var(--text-light);
        font-size: 0.85rem;
    }

    .oauth-divider::before,
    .oauth-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    .oauth-divider span {
        padding: 0 1rem;
    }

    .oauth-btn {
        width: 100%;
        padding: 0.875rem;
        border: 1px solid var(--border);
        border-radius: 0.75rem;
        background: var(--card-bg);
        color: var(--text-light);
        font-size: 0.95rem;
        cursor: not-allowed;
        opacity: 0.5;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .oauth-btn svg {
        width: 20px;
        height: 20px;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .onboarding-container {
            padding: 1rem;
        }
    }
</style>

<header class="onboarding-header">
    <div class="header-top">
        <a href="../index.php" class="logo">CiaoCV</a>
        <span class="step-indicator"><?= $currentStep ?> / <?= $totalSteps ?></span>
    </div>
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?= $progress ?>%"></div>
    </div>
</header>
