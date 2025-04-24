<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connessione al DB
require_once __DIR__ . '/../mamp_xampp.php';

$id_utente = $_SESSION['id_utente'] ?? null;
if (!$id_utente) {
    die("Errore: utente non loggato.");
}

// Se è stato inviato il form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['azione']) && $_POST['azione'] === 'modifica') {
        $id_competenza = $_POST['id_competenza'];
        $nuovo_livello = $_POST['nuovo_livello'];

        $stmt = $conn->prepare("CALL AggiornaLivelloSkillUtente(?, ?, ?)");
        $stmt->bind_param("iis", $id_utente, $id_competenza, $nuovo_livello);
        $stmt->execute();
        $stmt->close();
        $messaggio = "✅ Livello aggiornato!";

    } elseif (isset($_POST['azione']) && $_POST['azione'] === 'elimina') {
        $id_competenza = $_POST['id_competenza'];

        $stmt = $conn->prepare("CALL bostarter_db.EliminaSkillUtente(?, ?)");
        $stmt->bind_param("ii", $id_utente, $id_competenza);
        $stmt->execute();
        $stmt->close();
        $messaggio = "🗑️ Skill rimossa!";

    } else {
        // Inserimento standard
        $id_competenza = $_POST['id_competenza'] ?? null;
        $livello = $_POST['livello'] ?? null;

        if ($id_competenza && $livello) {
            $stmt = $conn->prepare("CALL InserisciSkillCurriculum(?, ?, ?)");
            $stmt->bind_param("iis", $id_utente, $id_competenza, $livello);
            if ($stmt->execute()) {
                $messaggio = "✅ Skill inserita con successo!";
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
$lista_competenze = [];
$result = $conn->query("SELECT id_competenza, nome FROM competenza");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lista_competenze[] = $row;
    }
}
// 🔽 Recupera skill utente
$skill_utente = [];
$res = $conn->prepare("SELECT c.nome, uc.id_competenza, uc.livello 
    FROM utente_competenze uc 
    JOIN competenza c ON c.id_competenza = uc.id_competenza 
    WHERE uc.id_utente = ?");
$res->bind_param("i", $id_utente);
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
                        <label for="id_competenza" class="form-label">Seleziona competenza:</label>
                        <select name="id_competenza" id="id_competenza" class="form-control" required>
                            <option value="">-- Scegli --</option>
                            <?php foreach ($lista_competenze as $comp): ?>
                                <option value="<?= $comp['id_competenza'] ?>"><?= htmlspecialchars($comp['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="livello" class="form-label">Livello:</label>
                        <select name="livello" id="livello" class="form-control" required>
                            <option value="">-- Seleziona livello --</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">💾 Salva competenza</button>
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
                                    <strong><?= htmlspecialchars($skill['nome']) ?></strong> - Livello: <?= htmlspecialchars($skill['livello']) ?>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="azione" value="modifica">
                                        <input type="hidden" name="id_competenza" value="<?= $skill['id_competenza'] ?>">
                                        <select name="nuovo_livello" class="form-select form-select-sm me-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>" <?= $skill['livello'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="submit" class="btn-like-input">✏️Modifica</button>
                                    </form>

                                    <form method="POST" onsubmit="return confirm('Sei sicuro?')">
                                        <input type="hidden" name="azione" value="elimina">
                                        <input type="hidden" name="id_competenza" value="<?= $skill['id_competenza'] ?>">
                                        <button type="submit" class="btn-like-input">🗑️Elimina</button>
                                    </form>
                                </div>

                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center home-button-container mb-5">
            <a href="../Autenticazione/home_utente.php" class="btn btn-success">
                Torna alla Home
            </a>
        </div>

</body>

</html>
