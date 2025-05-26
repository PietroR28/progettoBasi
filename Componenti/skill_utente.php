<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connessione al DB
require_once __DIR__ . '/../mamp_xampp.php';

if (!isset($_SESSION['email_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}
$email_utente = $_SESSION['email_utente'];

// Se è stato inviato il form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['azione']) && $_POST['azione'] === 'modifica') {
        $nome_skill = $_POST['nome_skill'];
        $nuovo_livello = $_POST['nuovo_livello'];

        $stmt = $conn->prepare("CALL AggiornaLivelloSkillUtente(?, ?, ?)");
        $stmt->bind_param("ssi", $email_utente, $nome_skill, $nuovo_livello);
        $stmt->execute();
        $stmt->close();
        $messaggio = "✅ Livello aggiornato!";

    } elseif (isset($_POST['azione']) && $_POST['azione'] === 'elimina') {
        $nome_skill = $_POST['nome_skill'];

        $stmt = $conn->prepare("CALL bostarter_db.EliminaSkillUtente(?, ?)");
        $stmt->bind_param("ss", $email_utente, $nome_skill);
        $stmt->execute();
        $stmt->close();
        try {
            require_once __DIR__ . '/../mongoDB/mongodb.php';

            log_event(
                'SKILL CURRICULUM ELIMINATA',
                $_SESSION['email_utente'],
                "L'utente {$_SESSION['email_utente']} ha eliminato la skill \"{$nome_skill}\" dal proprio curriculum.",
                [
                    'email_utente' => $email_utente,
                    'nome_skill'   => $nome_skill,
                ]
            );
        } catch (Exception $e) {
            error_log("❌ Errore nel log MongoDB: " . $e->getMessage());
        }
        $messaggio = "🗑️ Skill rimossa!";

    } else {
        // Inserimento standard
        $nome_skill = $_POST['nome_skill'] ?? null;
        $livello_utente_skill = $_POST['livello_utente_skill'] ?? null;

        if ($nome_skill && $livello_utente_skill) {
            $stmt = $conn->prepare("CALL InserisciSkillCurriculum(?, ?, ?)");
            $stmt->bind_param("ssi", $email_utente, $nome_skill, $livello_utente_skill);
            if ($stmt->execute()) {
                $messaggio = "✅ Skill inserita con successo!";

            try {
                require_once __DIR__ . '/../mongoDB/mongodb.php';

                log_event(
                    'SKILL CURRICULUM AGGIUNTA',
                    $_SESSION['email_utente'],
                    "L'utente {$_SESSION['email_utente']} ha aggiunto la skill \"{$nome_skill}\"al proprio curriculum.",
                    [
                        'email_utente' => $email_utente,
                        'nome_skill' => $nome_skill,
                        'livello_utente_skill' => $livello_utente_skill
                    ]
                );
            } catch (Exception $e) {
                error_log("❌ Errore nel log MongoDB: " . $e->getMessage());
            }

            } else {
                $messaggio = "❌ Errore durante l'inserimento: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $messaggio = "⚠️ Devi selezionare una competenza e un livello.";
        }
    }
}

// Carica tutte le competenze disponibili
$lista_skills = [];
$result = $conn->query("SELECT nome_skill FROM skill");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lista_skills[] = $row;
    }
}
// 🔽 Recupera skill utente
$skill_utente = [];
$res = $conn->prepare("SELECT uc.email_utente, uc.nome_skill, uc.livello_utente_skill 
    FROM utente_skill uc 
    JOIN skill c ON c.nome_skill = uc.nome_skill 
    WHERE uc.email_utente = ?");
$res->bind_param("i", $email_utente);
$res->execute();
$res_ris = $res->get_result();
while ($riga = $res_ris->fetch_assoc()) {
    $skill_utente[] = $riga;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Aggiungi Competenza</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Stile/skill_utente.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">💡 Inserisci una skill al tuo profilo</h1>

        <?php if (!empty($messaggio)): ?>
            <div class="alert <?php echo strpos($messaggio, '✅') === 0 ? 'alert-success' : (strpos($messaggio, '❌') === 0 ? 'alert-danger' : 'alert-warning'); ?>">
                <?= $messaggio ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Aggiungi una nuova competenza</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nome_skill" class="form-label">Seleziona competenza:</label>
                        <select name="nome_skill" id="nome_skill" class="form-control" required>
                            <option value="">-- Scegli --</option>
                            <?php foreach ($lista_skills as $comp): ?>
                                <option value="<?= $comp['nome_skill'] ?>"><?= htmlspecialchars($comp['nome_skill']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="livello_utente_skill" class="form-label">Livello:</label>
                        <select name="livello_utente_skill" id="livello_utente_skill" class="form-control" required>
                            <option value="">-- Seleziona livello --</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success"> Salva competenza</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">📚 Le tue skill</h5>
            </div>
            <div class="card-body">
                <?php if (empty($skill_utente)): ?>
                    <p>Non hai ancora skill associate.</p>
                <?php else: ?>
                    <ul class="list-group">
                        <?php foreach ($skill_utente as $skill): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <strong><?= htmlspecialchars($skill['nome_skill']) ?></strong> - Livello: <?= htmlspecialchars($skill['livello_utente_skill']) ?>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="azione" value="modifica">
                                        <input type="hidden" name="nome_skill" value="<?= $skill['nome_skill'] ?>">
                                        <select name="nuovo_livello" class="form-select form-select-sm me-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>" <?= $skill['livello_utente_skill'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="submit" class="btn-like-input">✏️Modifica</button>
                                    </form>

                                    <form method="POST" onsubmit="return confirm('Sei sicuro?')">
                                        <input type="hidden" name="azione" value="elimina">
                                        <input type="hidden" name="nome_skill" value="<?= $skill['nome_skill'] ?>">
                                        <button type="submit" class="btn-like-input">🗑️Elimina</button>
                                    </form>
                                </div>

                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <a href="../Autenticazione/<?php 
                echo ($_SESSION['ruolo_utente'] === 'amministratore') ? 'home_amministratore.php' :
                    (($_SESSION['ruolo_utente'] === 'creatore') ? 'home_creatore.php' : 'home_utente.php');
            ?>" class="btn btn-success">Torna alla Home</a>
        </div>
</body>

</html>