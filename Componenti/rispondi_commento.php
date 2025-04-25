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
require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = '';

// Verifica che l'utente sia il creatore del progetto
$autorizzato = false;
$stmt = $conn->prepare("SELECT id_utente_creatore FROM progetto WHERE id_progetto = ?");
$stmt->bind_param("i", $id_progetto);
$stmt->execute();
$stmt->bind_result($id_utente_creatore);
if ($stmt->fetch()) {
    if ($id_utente_creatore === $_SESSION['id_utente']) {
        $autorizzato = true;
    }
}
$stmt->close();

if (!$autorizzato) {
    die("⛔ Non sei autorizzato a rispondere a commenti su questo progetto.");
}

// Recupera il testo del commento padre
$testo_commento_padre = '';
$stmt = $conn->prepare("SELECT testo FROM commento WHERE id_commento = ?");
$stmt->bind_param("i", $id_commento_padre);
$stmt->execute();
$stmt->bind_result($testo_commento_padre);
$stmt->fetch();
$stmt->close();

// Gestione invio risposta
$countRisposte = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testo'])) {
    $testo = trim($_POST['testo']);
    $id_utente = $_SESSION['id_utente'];

    if (!empty($testo)) {
        // Verifica se esiste già una risposta
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM commento WHERE id_commento_padre = ?");
        $checkStmt->bind_param("i", $id_commento_padre);
        $checkStmt->execute();
        $checkStmt->bind_result($countRisposte);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($countRisposte > 0) {
            $messaggio = "⚠️ Esiste già una risposta per questo commento. È possibile inserirne solo una.";
        } else {
            $stmt = $conn->prepare("INSERT INTO commento (testo, data, id_progetto, id_utente, id_commento_padre) VALUES (?, NOW(), ?, ?, ?)");
            $stmt->bind_param("siii", $testo, $id_progetto, $id_utente, $id_commento_padre);

            if ($stmt->execute()) {
                require_once __DIR__ . '/../mongoDB/mongodb.php';

                log_event(
                    'RISPOSTA_COMMENTO',
                    $_SESSION['email'],
                    "L'utente {$_SESSION['email']} ha risposto al commento ID $id_commento_padre sul progetto ID $id_progetto.",
                    [
                        'id_utente' => $id_utente,
                        'id_progetto' => $id_progetto,
                        'id_commento_padre' => $id_commento_padre,
                        'testo_commento_padre' => $testo_commento_padre,
                        'testo_risposta' => $testo
                    ]
                );
                $messaggio = "✅ Risposta inserita con successo!";
            } else {
                $messaggio = "❌ Errore: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $messaggio = "⚠️ Il campo testo non può essere vuoto.";
    }
} else {
    // Calcola countRisposte anche per disabilitare il form
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM commento WHERE id_commento_padre = ?");
    $checkStmt->bind_param("i", $id_commento_padre);
    $checkStmt->execute();
    $checkStmt->bind_result($countRisposte);
    $checkStmt->fetch();
    $checkStmt->close();
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
    <link rel="stylesheet" href="/progettoBasi/Stile/rispondi_commento.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="container-box">
            <h2 class="mb-4">Risposta a un commento</h2>

            <?php if (!empty($messaggio)) echo "<div class='alert alert-success'>$messaggio</div>"; ?>

            <?php if ($commento): ?>
                <h5>💬 Commento originale</h5>
                <blockquote>
                    <p><strong><?= htmlspecialchars($commento['nickname']) ?></strong> - <?= htmlspecialchars($commento['data']) ?></p>
                    <p><?= nl2br(htmlspecialchars($commento['testo'])) ?></p>
                </blockquote>
            <?php else: ?>
                <div class="alert alert-warning">⚠️ Commento non trovato.</div>
            <?php endif; ?>

            <?php if ($countRisposte == 0): ?>
                <h5 class="mt-4">✍🏼 Scrivi la tua risposta</h5>
                <form method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" name="testo" rows="4" required placeholder="Scrivi qui la tua risposta..."></textarea>
                    </div>
                    <button class="btn btn-danger">Invia risposta</button>

                </form>
            <?php else: ?>
                <div class="alert alert-success mt-4">✅ Hai già risposto a questo commento.</div>
            <?php endif; ?>

            <div class="text-center home-button-container">
                <a href="../Componenti/risposta_commento.php" class="btn btn-success">
                     Torna ai progetti
                </a>
            </div>

        </div>
    </div>
</body>
</html>