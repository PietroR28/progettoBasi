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
$messaggio = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';

    if ($azione === 'modifica') {
        // Modifica livello esistente
        $nome_skill    = $_POST['nome_skill']   ?? '';
        $nuovo_livello = isset($_POST['nuovo_livello']) ? (int) $_POST['nuovo_livello'] : null;

        if ($nuovo_livello === null || $nuovo_livello < 0 || $nuovo_livello > 5) {
            $messaggio = "⚠️ Il livello deve essere compreso tra 0 e 5.";
        } else {
            $stmt = $conn->prepare("CALL AggiornaLivelloSkillUtente(?, ?, ?)");
            $stmt->bind_param("ssi", $email_utente, $nome_skill, $nuovo_livello);
            $stmt->execute();
            while ($conn->more_results()) { $conn->next_result(); }
            $stmt->close();

            $messaggio = "✅ Livello aggiornato!";
        }

    } elseif ($azione === 'elimina') {
        // Eliminazione skill
        $nome_skill = $_POST['nome_skill'] ?? '';

        $stmt = $conn->prepare("CALL EliminaSkillUtente(?, ?)");
        $stmt->bind_param("ss", $email_utente, $nome_skill);
        $stmt->execute();
        while ($conn->more_results()) { $conn->next_result(); }
        $stmt->close();

        $messaggio = "🗑️ Skill rimossa!";

    } else {
        // Inserimento nuova skill
        $nome_skill = $_POST['nome_skill'] ?? null;
        $livello_utente_skill = isset($_POST['livello_utente_skill']) ? (int) $_POST['livello_utente_skill'] : null;

        if ($nome_skill !== null && $livello_utente_skill !== null) {
            if ($livello_utente_skill < 0 || $livello_utente_skill > 5) {
                $messaggio = "⚠️ Il livello deve essere compreso tra 0 e 5.";
            } else {
                // Controllo duplicato
                $check = $conn->prepare("
                    SELECT 1
                      FROM utente_skill
                     WHERE email_utente = ?
                       AND nome_skill    = ?
                     LIMIT 1
                ");
                $check->bind_param("ss", $email_utente, $nome_skill);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $messaggio = "⚠️ Skill già inserita, aggiorna il livello";
                } else {
                    // Inserimento via stored procedure
                    $stmt = $conn->prepare("CALL InserisciSkillCurriculum(?, ?, ?)");
                    $stmt->bind_param("ssi", $email_utente, $nome_skill, $livello_utente_skill);
                    $stmt->execute();
                    while ($conn->more_results()) { $conn->next_result(); }
                    $stmt->close();

                    $messaggio = "✅ Skill inserita con successo!";

                    // Log MongoDB
                    try {
                        require_once __DIR__ . '/../mongoDB/mongodb.php';
                        log_event(
                            'SKILL CURRICULUM AGGIUNTA',
                            $email_utente,
                            "L'utente {$email_utente} ha aggiunto la skill \"{$nome_skill}\" con livello {$livello_utente_skill}.",
                            [
                                'email_utente'          => $email_utente,
                                'nome_skill'            => $nome_skill,
                                'livello_utente_skill'  => $livello_utente_skill,
                            ]
                        );
                    } catch (Exception $e) {
                        error_log("❌ Errore nel log MongoDB (inserimento): " . $e->getMessage());
                    }
                }

                $check->close();
            }
        } else {
            $messaggio = "⚠️ Devi selezionare una skill e un livello.";
        }
    }
}

// Carica lista di tutte le competenze disponibili
$lista_skills = [];
$result = $conn->query("SELECT nome_skill FROM skill ORDER BY nome_skill ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lista_skills[] = $row;
    }
}

// Recupera skill utente
$skill_utente = [];
$res = $conn->prepare("
    SELECT nome_skill, livello_utente_skill
      FROM utente_skill
     WHERE email_utente = ?
");
$res->bind_param("s", $email_utente);
$res->execute();
$res_ris = $res->get_result();
while ($riga = $res_ris->fetch_assoc()) {
    $skill_utente[] = $riga;
}
$res->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Aggiungi skill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Stile/skill_utente.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">💡 Inserisci una Skill al tuo profilo</h1>

    <?php if (!empty($messaggio)): ?>
        <div class="alert <?=
            strpos($messaggio, '✅') === 0 ? 'alert-success' :
            (strpos($messaggio, '🗑️') === 0 ? 'alert-success' :
            (strpos($messaggio, '⚠️') === 0 ? 'alert-warning' : 'alert-danger'))
        ?>">
            <?= htmlspecialchars($messaggio) ?>
        </div>
    <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Aggiungi una nuova Skill</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nome_skill" class="form-label">Seleziona skill:</label>
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
                            <?php for ($i = 0; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success"> Salva Skill</button>
                </form>
            </div>
        </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">📚 Le tue Skill</h5>
        </div>
        <div class="card-body">
            <?php if (empty($skill_utente)): ?>
                <p>Non hai ancora skill associate.</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($skill_utente as $skill): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <strong><?= htmlspecialchars($skill['nome_skill']) ?></strong>
                                – Livello: <?= htmlspecialchars($skill['livello_utente_skill']) ?>
                            </div>
                          <div class="d-flex gap-2">
    <form method="POST" class="d-flex align-items-center">
        <input type="hidden" name="azione" value="modifica">
        <input type="hidden" name="nome_skill" value="<?= htmlspecialchars($skill['nome_skill']) ?>">
        <select name="nuovo_livello" class="form-select form-select-sm me-2">
            <?php for ($i = 0; $i <= 5; $i++): ?>
                <option value="<?= $i ?>" <?= $skill['livello_utente_skill'] == $i ? 'selected' : '' ?>>
                    <?= $i ?>
                </option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="btn btn-outline-warning">✏️Modifica</button>
    </form>

    <form method="POST" class="d-flex align-items-center" onsubmit="return confirm('Sei sicuro di voler eliminare questa skill?')">
        <input type="hidden" name="azione" value="elimina">
        <input type="hidden" name="nome_skill" value="<?= htmlspecialchars($skill['nome_skill']) ?>">
        <button type="submit" class="btn btn-outline-danger">🗑️Elimina</button>
    </form>
</div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="../Autenticazione/<?=
            ($_SESSION['ruolo_utente'] === 'amministratore') ? 'home_amministratore.php' :
            (($_SESSION['ruolo_utente'] === 'creatore') ? 'home_creatore.php' : 'home_utente.php')
        ?>" class="btn btn-success">Torna alla Home</a>
    </div>
</div>
</body>
</html>
