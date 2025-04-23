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


// Carica tutte le competenze disponibili
$lista_competenze = [];
$result = $conn->query("SELECT id_competenza, nome FROM competenza");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lista_competenze[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Aggiungi Competenza</title>
</head>
<body>
    <h2>💡 Inserisci una skill al tuo profilo</h2>

    <?php if (!empty($messaggio)): ?>
        <p><strong><?php echo $messaggio; ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="id_competenza">Seleziona competenza:</label><br>
        <select name="id_competenza" required>
            <option value="">-- Scegli --</option>
            <?php foreach ($lista_competenze as $comp): ?>
                <option value="<?= $comp['id_competenza'] ?>">
                    <?= htmlspecialchars($comp['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="livello">Livello:</label><br>
        <select name="livello" required>
            <option value="">-- Seleziona livello --</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
        </select><br><br>

        <button type="submit">💾 Salva competenza</button>
    </form>

    <br>
    <a href="../Autenticazione/home_utente.php">⬅ Torna alla home</a>
</body>
</html>