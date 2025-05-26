<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['email_utente'])) {
    die("Accesso negato. Effettua il login.");
}

$nome_progetto = $_GET['nome_progetto'] ?? '';
$id_commento = isset($_GET['id_commento']) ? (int)$_GET['id_commento'] : 0;

require_once __DIR__ . '/../mamp_xampp.php';
require_once __DIR__ . '/../mongoDB/mongodb.php';

$messaggio = '';
$email_utente = $_SESSION['email_utente'];

// Verifica che l'utente sia il creatore del progetto
$stmt = $conn->prepare("SELECT email_utente_creatore FROM progetto WHERE nome_progetto = ?");
$stmt->bind_param("s", $nome_progetto);
$stmt->execute();
$stmt->bind_result($email_creatore_commento);
$stmt->fetch();
$stmt->close();

if ($email_creatore_commento !== $email_utente) {
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Accesso Negato</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f8f9fa;
            }
            .access-denied-box {
                max-width: 600px;
                margin: 100px auto;
                background: white;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                padding: 30px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="access-denied-box">
            <h2 class="text-danger">⛔ Accesso Negato</h2>
            <p class="mt-3">Non sei autorizzato a rispondere a commenti su questo progetto.</p>
            <a href="../Componenti/risposta_commento.php" class="btn btn-success mt-4">Torna ai progetti</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}


// Recupera il commento originale
$stmt = $conn->prepare("SELECT c.testo_commento AS testo_commento, c.data_commento AS data, u.nickname_utente AS nickname FROM commento c JOIN utente u ON c.email_utente = u.email_utente WHERE c.id_commento = ?");
$stmt->bind_param("i", $id_commento);
$stmt->execute();
$commento = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Recupera eventuale risposta esistente
$stmt = $conn->prepare("SELECT testo_risposta FROM risposta_commento WHERE id_commento = ?");
$stmt->bind_param("i", $id_commento);
$stmt->execute();
$stmt->store_result();
$risposta_presente = $stmt->num_rows > 0;
$stmt->close();

// Inserimento risposta se non esiste con stored procedure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testo']) && !$risposta_presente) {
    $testo = trim($_POST['testo']);
    if (!empty($testo)) {
        $stmt = $conn->prepare("CALL InserisciRisposta(?, ?, ?, ?)");
        $stmt->bind_param("isss", $id_commento, $email_utente, $testo, $nome_progetto);
        
        if ($stmt->execute()) {
            log_event(
                'RISPOSTA_COMMENTO',
                $email_utente,
                "Il creatore ha risposto al commento '{$commento['testo_commento']}' sul progetto $nome_progetto.",
                [
                    'nome_progetto' => $nome_progetto,
                    'testo_commento' => $commento['testo_commento'],
                    'testo_risposta' => $testo
                ]
            );
            $messaggio = "✅ Risposta inserita con successo!";
        } else {
            $messaggio = "❌ Errore: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $messaggio = "⚠️ Il campo testo non può essere vuoto.";
    }
}
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
                <p><?= nl2br(htmlspecialchars($commento['testo_commento'])) ?></p>
            </blockquote>
        <?php else: ?>
            <div class="alert alert-warning">⚠️ Commento non trovato.</div>
        <?php endif; ?>

        <?php if (!$risposta_presente): ?>
            <h5 class="mt-4">✍️ Scrivi la tua risposta</h5>
            <form method="POST">
                <div class="mb-3">
                    <textarea class="form-control" name="testo" rows="4" required placeholder="Scrivi qui la tua risposta..."></textarea>
                </div>
                <button class="btn btn-danger">Invia risposta</button>
            </form>
        <?php else: ?>
            <div class="alert alert-success mt-4">✅ Hai già risposto a questo commento.</div>
        <?php endif; ?>

        <div class="text-center home-button-container mt-4">
            <a href="../Componenti/risposta_commento.php" class="btn btn-success">
                Torna ai progetti
            </a>
        </div>
    </div>
</div>
</body>
</html>