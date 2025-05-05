<?php
session_start();

if (!isset($_SESSION['email_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}

require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = "";
$email_utente = $_SESSION['email_utente'];

// Recupero parametri GET
$id_finanziamento = isset($_GET['id_finanziamento']) ? intval($_GET['id_finanziamento']) : 0;
$nome_progetto = isset($_GET['nome_progetto']) ? $_GET['nome_progetto'] : '';
$rewards = [];

if ($id_finanziamento > 0 && $nome_progetto !== '') {
    // Verifica che il finanziamento appartenga all'utente
    $stmt = $conn->prepare("
        SELECT f.id_finanziamento, f.importo_finanziamento AS importo, f.data_finanziamento AS data, f.nome_progetto
        FROM finanziamento f
        WHERE f.id_finanziamento = ? AND f.email_utente = ? AND f.id_reward IS NULL
    ");
    $stmt->bind_param("is", $id_finanziamento, $email_utente);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $messaggio = "Errore: finanziamento non trovato o già assegnato.";
        $id_finanziamento = 0;
        $nome_progetto = '';
    } else {
        $finanziamento = $result->fetch_assoc();
        $messaggio = "Hai finanziato il progetto \"{$finanziamento['nome_progetto']}\" con €" . number_format($finanziamento['importo'], 2, ',', '.') . ". Scegli una reward:";
    }
    $stmt->close();

    // Carica le reward del progetto
    if ($nome_progetto !== '') {
        $stmt = $conn->prepare("CALL VisualizzaRewardProgetto(?)");
        $stmt->bind_param("s", $nome_progetto);
        $stmt->execute();
        $res = $stmt->get_result();
        $rewards = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}

// Gestione POST: assegnazione reward
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reward'], $_POST['id_finanziamento'])) {
    $id_reward = intval($_POST['id_reward']);
    $id_finanziamento = intval($_POST['id_finanziamento']);

    $stmt = $conn->prepare("CALL AssegnaReward(?, ?, ?)");
    $stmt->bind_param("sii", $email_utente, $id_finanziamento, $id_reward);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if ($row && $row['risultato'] === 'OK') {
        header("Location: scelta_reward.php?success=1");
        exit;
    } else {
        $messaggio = "❌ Errore nell'assegnazione della reward. Verifica di non aver già scelto una reward.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Scegli una Reward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Stile/scelta_reward.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">🎁 Scegli una Reward</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            Reward assegnata con successo!
            <a href="../Autenticazione/<?=
                $_SESSION['ruolo_utente'] === 'amministratore' ? 'home_amministratore.php' :
                ($_SESSION['ruolo_utente'] === 'creatore' ? 'home_creatore.php' : 'home_utente.php')
            ?>" class="btn btn-success mt-2">Torna alla Home</a>
        </div>
    <?php elseif ($id_finanziamento && $nome_progetto): ?>
        <?php if ($messaggio): ?>
            <div class="alert alert-info"><?= htmlspecialchars($messaggio) ?></div>
        <?php endif; ?>

        <?php if (empty($rewards)): ?>
            <div class="alert alert-warning">Nessuna reward disponibile per questo progetto.</div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="id_finanziamento" value="<?= $id_finanziamento ?>">
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($rewards as $reward): ?>
                        <div class="col">
                            <div class="card reward-card h-100" onclick="selectReward(this, <?= $reward['id_reward'] ?>)">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="id_reward"
                                               id="reward_<?= $reward['id_reward'] ?>"
                                               value="<?= $reward['id_reward'] ?>" required>
                                        <label class="form-check-label" for="reward_<?= $reward['id_reward'] ?>">
                                            <h5 class="card-title">Reward #<?= $reward['id_reward'] ?></h5>
                                        </label>
                                    </div>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($reward['descrizione_reward'])) ?></p>
                                    <?php if (!empty($reward['foto_reward'])): ?>
                                        <div class="text-center">
                                            <img src="../<?= htmlspecialchars($reward['foto_reward']) ?>"
                                                 alt="Immagine reward"
                                                 class="img-fluid"
                                                 onerror="this.onerror=null; this.src='../uploads/placeholder.png'; this.alt='Non disponibile';">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-success mt-4">Conferma Reward</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-danger">
            Errore: finanziamento non valido o non autorizzato.
            <a href="../Autenticazione/<?=
                $_SESSION['ruolo_utente'] === 'amministratore' ? 'home_amministratore.php' :
                ($_SESSION['ruolo_utente'] === 'creatore' ? 'home_creatore.php' : 'home_utente.php')
            ?>" class="btn btn-secondary mt-2">Torna alla Home</a>
        </div>
    <?php endif; ?>
</div>

<script>
function selectReward(card, rewardId) {
    document.querySelectorAll('.reward-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('reward_' + rewardId).checked = true;
}
</script>
</body>
</html>
