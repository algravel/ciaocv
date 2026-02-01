<?php
/**
 * Étape 3 - Tes compétences principales
 * Tags cliquables + niveau
 */
session_start();
require_once __DIR__ . '/../db.php';

$requiredStep = 3;
require_once __DIR__ . '/../includes/onboarding-check.php';

$currentStep = 3;
$stepTitle = "Compétences";
$error = null;

// Compétences prédéfinies
$defaultSkills = [
    'Service client', 'Vente', 'Administration', 'Manutention', 
    'Cuisine', 'IT / Informatique', 'Santé', 'Construction',
    'Marketing', 'Comptabilité', 'Logistique', 'Mécanique',
    'Design', 'Communication', 'Gestion', 'Ressources humaines'
];

// Niveaux
$levels = [
    'beginner' => 'Débutant',
    'intermediate' => 'Intermédiaire', 
    'advanced' => 'Avancé'
];

// Charger les compétences actuelles
$currentSkills = [];
if ($db) {
    $stmt = $db->prepare('SELECT skill_name, skill_level FROM user_skills WHERE user_id = ?');
    $stmt->execute([$userId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentSkills[$row['skill_name']] = $row['skill_level'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSkills = $_POST['skills'] ?? [];
    $skillLevels = $_POST['skill_level'] ?? [];

    if (empty($selectedSkills)) {
        $error = 'Sélectionne au moins une compétence.';
    } else {
        try {
            // Supprimer les anciennes compétences
            $db->prepare('DELETE FROM user_skills WHERE user_id = ?')->execute([$userId]);
            
            // Insérer les nouvelles
            $stmt = $db->prepare('INSERT INTO user_skills (user_id, skill_name, skill_level) VALUES (?, ?, ?)');
            foreach ($selectedSkills as $skill) {
                $level = $skillLevels[$skill] ?? 'intermediate';
                $stmt->execute([$userId, $skill, $level]);
            }
            
            // Mettre à jour l'étape
            $db->prepare('UPDATE users SET onboarding_step = 4 WHERE id = ?')->execute([$userId]);
            
            header('Location: step4-personality.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Erreur. Réessayez.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Compétences - CiaoCV</title>
    <link rel="icon" href="data:,">
    <style>
        .skill-selected-list {
            margin-top: 1.5rem;
            display: none;
        }
        .skill-selected-list.has-items {
            display: block;
        }
        .skill-item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .skill-item-name {
            font-weight: 500;
            flex: 1;
        }
        .skill-item select {
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
        }
        .skill-item-remove {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.25rem;
        }
        .skill-item-remove:hover {
            color: #ef4444;
        }
        .add-skill-input {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .add-skill-input input {
            flex: 1;
        }
        .add-skill-input button {
            padding: 0.75rem 1rem;
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 0.75rem;
            cursor: pointer;
        }
        .add-skill-input button:hover {
            background: var(--primary);
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="onboarding-container">
        <?php include __DIR__ . '/../includes/onboarding-header.php'; ?>

        <h1 class="step-title">Tes compétences principales</h1>
        <p class="step-subtitle">Clique sur les compétences qui te correspondent</p>

        <div class="onboarding-content">
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="skillsForm">
                <div class="tags-container" id="tagsContainer">
                    <?php foreach ($defaultSkills as $skill): ?>
                    <label class="tag <?= isset($currentSkills[$skill]) ? 'selected' : '' ?>">
                        <input type="checkbox" name="skills[]" value="<?= htmlspecialchars($skill) ?>" 
                               <?= isset($currentSkills[$skill]) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($skill) ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="add-skill-input">
                    <input type="text" id="customSkill" placeholder="Ajouter une compétence...">
                    <button type="button" onclick="addCustomSkill()">+</button>
                </div>

                <div class="skill-selected-list <?= count($currentSkills) > 0 ? 'has-items' : '' ?>" id="selectedList">
                    <p style="font-weight:600;margin-bottom:0.75rem;color:var(--text-light);font-size:0.9rem;">NIVEAU DE COMPÉTENCE</p>
                    <div id="selectedSkillsContainer">
                        <?php foreach ($currentSkills as $skill => $level): ?>
                        <div class="skill-item" data-skill="<?= htmlspecialchars($skill) ?>">
                            <span class="skill-item-name"><?= htmlspecialchars($skill) ?></span>
                            <select name="skill_level[<?= htmlspecialchars($skill) ?>]">
                                <?php foreach ($levels as $lv => $label): ?>
                                <option value="<?= $lv ?>" <?= $level === $lv ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="skill-item-remove" onclick="removeSkill('<?= htmlspecialchars($skill) ?>')">×</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="onboarding-footer">
                    <button type="submit" class="btn">Suivant</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const levels = <?= json_encode($levels) ?>;
        
        // Gestion des tags
        document.querySelectorAll('.tag').forEach(tag => {
            tag.addEventListener('click', function(e) {
                const checkbox = this.querySelector('input[type="checkbox"]');
                const skill = checkbox.value;
                
                if (checkbox.checked) {
                    addSkillToList(skill);
                } else {
                    removeSkillFromList(skill);
                }
                
                this.classList.toggle('selected', checkbox.checked);
                updateSelectedList();
            });
        });

        function addSkillToList(skill) {
            const container = document.getElementById('selectedSkillsContainer');
            if (container.querySelector(`[data-skill="${skill}"]`)) return;
            
            const div = document.createElement('div');
            div.className = 'skill-item';
            div.dataset.skill = skill;
            
            let options = '';
            for (const [value, label] of Object.entries(levels)) {
                const selected = value === 'intermediate' ? 'selected' : '';
                options += `<option value="${value}" ${selected}>${label}</option>`;
            }
            
            div.innerHTML = `
                <span class="skill-item-name">${skill}</span>
                <select name="skill_level[${skill}]">${options}</select>
                <button type="button" class="skill-item-remove" onclick="removeSkill('${skill}')">×</button>
            `;
            container.appendChild(div);
        }

        function removeSkillFromList(skill) {
            const container = document.getElementById('selectedSkillsContainer');
            const item = container.querySelector(`[data-skill="${skill}"]`);
            if (item) item.remove();
        }

        function removeSkill(skill) {
            // Décocher le tag s'il existe
            const checkbox = document.querySelector(`input[value="${skill}"]`);
            if (checkbox) {
                checkbox.checked = false;
                checkbox.closest('.tag').classList.remove('selected');
            }
            removeSkillFromList(skill);
            updateSelectedList();
        }

        function updateSelectedList() {
            const list = document.getElementById('selectedList');
            const container = document.getElementById('selectedSkillsContainer');
            list.classList.toggle('has-items', container.children.length > 0);
        }

        function addCustomSkill() {
            const input = document.getElementById('customSkill');
            const skill = input.value.trim();
            if (!skill) return;
            
            // Créer un nouveau tag
            const container = document.getElementById('tagsContainer');
            const label = document.createElement('label');
            label.className = 'tag selected';
            label.innerHTML = `<input type="checkbox" name="skills[]" value="${skill}" checked>${skill}`;
            label.addEventListener('click', function(e) {
                const cb = this.querySelector('input');
                if (cb.checked) {
                    addSkillToList(skill);
                } else {
                    removeSkillFromList(skill);
                }
                this.classList.toggle('selected', cb.checked);
                updateSelectedList();
            });
            container.appendChild(label);
            
            addSkillToList(skill);
            updateSelectedList();
            input.value = '';
        }

        // Permettre d'ajouter avec Enter
        document.getElementById('customSkill').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCustomSkill();
            }
        });
    </script>
</body>
</html>
