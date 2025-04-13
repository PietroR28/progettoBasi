<?php
session_start();

// Mostra gli errori
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifica accesso utente
if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato. Effettua il login.");
}

// Parametri dalla query string
$id_progetto = isset($_GET['id_progetto']) ? (int)$_GET['id_progetto'] : 0;
$id_commento_padre = isset($_GET['id_commento']) ? (int)$_GET['id_commento'] : 0;

// Connessione al DB
$conn = new mysqli('localhost', 'root', '', 'bostarter_db');
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$messaggio = '';

// Gestione invio risposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testo'])) {
    $testo = trim($_POST['testo']);
    $id_utente = $_SESSION['id_utente'];

    if (!empty($testo)) {
        $stmt = $conn->prepare("INSERT INTO commento (testo, data, id_progetto, id_utente, id_commento_padre) VALUES (?, NOW(), ?, ?, ?)");
        $stmt->bind_param("siii", $testo, $id_progetto, $id_utente, $id_commento_padre);

        if ($stmt->execute()) {
            $messaggio = "✅ Risposta inserita con successo!";
        } else {
            $messaggio = "❌ Errore: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $messaggio = "⚠️ Il campo testo non può essere vuoto.";
    }
}

// Recupera il commento originale
$stmt = $conn->prepare("SELECT c.testo, c.data, u.nickname 
                        FROM commento c 
                        JOIN utente u ON c.id_utente = u.id_utente 
                        WHERE c.id_commento = ?");
$stmt->bind_param("i", $id_commento_padre);
$stmt->execute();
$commento = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Rispondi al commento</title>
</head>
<body>
    <h2>Risposta a un commento</h2>

    <?php if (!empty($messaggio)) echo "<p><strong>$messaggio</strong></p>"; ?>

    <?php if ($commento): ?>
        <h4>🗨 Commento originale</h4>
        <blockquote style="background-color: #f0f0f0; padding: 10px; border-left: 4px solid #ccc;">
            <p><strong><?= htmlspecialchars($commento['nickname']) ?></strong> - <?= htmlspecialchars($commento['data']) ?></p>
            <p><?= nl2br(htmlspecialchars($commento['testo'])) ?></p>
        </blockquote>
    <?php else: ?>
        <p>⚠️ Commento non trovato.</p>
    <?php endif; ?>

    <h4>✍ Scrivi la tua risposta</h4>
    <form method="POST">
        <textarea name="testo" rows="4" cols="60" required></textarea><br><br>
        <button type="submit">Invia risposta</button>
    </form>

    <br>
    <a href="../Componenti/risposta_commento.php" style="text-decoration: none;">
    <button type="button" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Torna ai progetti
    </button>
    </a>
</body>
</html>
