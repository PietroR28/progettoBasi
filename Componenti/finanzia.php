<?php
session_start();

if (!isset($_SESSION['email_utente']) || !isset($_SESSION['ruolo_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}

require __DIR__ . '/../mamp_xampp.php';
$message = "";
$nome_progetto = isset($_GET['id']) ? $_GET['id'] : '';
$progetto_info = [];
$totale = 0;
$percentuale = 0;
$progetto_exists = false;
$ha_gia_finanziato_oggi = false;
$id_finanziamento = 0;

function getConnection() {
    require __DIR__ . '/../mamp_xampp.php';
    return $conn;
}

if (!$nome_progetto) {
    $conn = getConnection();
    $stmt = $conn->prepare("CALL VisualizzaProgettiDisponibili()");
    $stmt->execute();
    $result = $stmt->get_result();
    $progetti_disponibili = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
} else {
    $email_utente = $_SESSION['email_utente'];
    $oggi = date('Y-m-d');

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM finanziamento WHERE email_utente = ? AND nome_progetto = ? AND DATE(data_finanziamento) = ?");
    $stmt->bind_param("sss", $email_utente, $nome_progetto, $oggi);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $ha_gia_finanziato_oggi = $res['count'] > 0;
    $stmt->close();

    $stmt = $conn->prepare("SELECT nome_progetto, budget_progetto, stato_progetto, data_limite_progetto, descrizione_progetto FROM progetto WHERE nome_progetto = ? AND stato_progetto = 'aperto'");
    $stmt->bind_param("s", $nome_progetto);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $progetto_info = $result->fetch_assoc();
        $progetto_exists = true;

        $stmt_sum = $conn->prepare("SELECT SUM(importo_finanziamento) AS totale FROM finanziamento WHERE nome_progetto = ?");
        $stmt_sum->bind_param("s", $nome_progetto);
        $stmt_sum->execute();
        $res_sum = $stmt_sum->get_result();
        $row_sum = $res_sum->fetch_assoc();
        $totale = $row_sum['totale'] ?? 0;
        $stmt_sum->close();

        $budget_progetto = $progetto_info['budget_progetto'];
        $percentuale = ($budget_progetto > 0) ? min(100, round(($totale / $budget_progetto) * 100)) : 0;
    } else {
        $message = "Errore: Il progetto selezionato non esiste o non è aperto per finanziamenti.";
    }
    $stmt->close();
    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['importo_finanziamento']) && $nome_progetto) {
    $importo = floatval(str_replace(',', '.', $_POST['importo_finanziamento']));
    $email_utente = $_SESSION['email_utente'];
    $oggi = date('Y-m-d');

    $conn = getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM finanziamento WHERE email_utente = ? AND nome_progetto = ? AND DATE(data_finanziamento) = ?");
    $stmt->bind_param("sss", $email_utente, $nome_progetto, $oggi);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res['count'] > 0) {
        $message = "Hai già finanziato questo progetto oggi. Puoi farlo di nuovo domani.";
    } else {
        $stmt = $conn->prepare("CALL InserisciFinanziamento(?, ?, ?)");
        $stmt->bind_param("ssd", $email_utente, $nome_progetto, $importo);
        if ($stmt->execute()) {
            $stmt->close();

$stmt_id = $conn->prepare("SELECT MAX(id_finanziamento) AS id FROM finanziamento WHERE email_utente = ? AND nome_progetto = ?");
            $stmt_id->bind_param("ss", $email_utente, $nome_progetto);
            $stmt_id->execute();
            $res_id = $stmt_id->get_result();
            $row_id = $res_id->fetch_assoc();
            $id_finanziamento = $row_id['id'];
            $stmt_id->close();

            require_once __DIR__ . '/../mongoDB/mongodb.php';
            log_event('FINANZIAMENTO', $email_utente, "L'utente $email_utente ha finanziato il progetto '$nome_progetto' con €$importo.", [
                'email_utente' => $email_utente,
                'nome_progetto' => $nome_progetto,
                'importo_finanziamento' => $importo,
            ]);

            header("Location: scelta_reward.php?id_finanziamento=$id_finanziamento&nome_progetto=$nome_progetto");
            exit;
        } else {
            $message = "Errore durante l'inserimento del finanziamento: " . $stmt->error;
            $stmt->close();
        }
    }
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Finanzia Progetto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1>Finanzia un Progetto</h1>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo str_starts_with($message, "Errore") ? 'alert-danger' : 'alert-success'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if (!$nome_progetto): ?>
        <h2 class="mb-3">Progetti disponibili</h2>
        <?php if (empty($progetti_disponibili)): ?>
            <div class="alert alert-info">Nessun progetto disponibile al momento.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr><th>Nome Progetto</th><th>Descrizione</th><th>Data</th><th>Azione</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($progetti_disponibili as $progetto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($progetto['nome_progetto']); ?></td>
                            <td><?php echo htmlspecialchars(substr($progetto['descrizione_progetto'], 0, 100)) . (strlen($progetto['descrizione_progetto']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($progetto['data_inserimento_progetto'])); ?></td>
                            <td><a href="finanzia.php?id=<?php echo urlencode($progetto['nome_progetto']); ?>" class="btn btn-danger btn-sm">Seleziona</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif ($progetto_exists): ?>
        <div class="card mt-4">
            <div class="card-body">
                <h3><?php echo htmlspecialchars($progetto_info['nome_progetto']); ?></h3>
                <p><strong>Descrizione:</strong> <?php echo nl2br(htmlspecialchars($progetto_info['descrizione_progetto'])); ?></p>
                <p>
                    <strong>Budget:</strong> €<?php echo number_format($progetto_info['budget_progetto'], 2, ',', '.'); ?> |
                    <strong>Finanziato:</strong> €<?php echo number_format($totale, 2, ',', '.'); ?> (<?php echo $percentuale; ?>%) |
                    <strong>Data limite:</strong> <?php echo date('d/m/Y', strtotime($progetto_info['data_limite_progetto'])); ?>
                </p>

                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar <?php
                        if ($percentuale <= 33) echo 'bg-danger';
                        elseif ($percentuale <= 70) echo 'bg-warning';
                        else echo 'bg-success';
                    ?>" role="progressbar" style="width: <?php echo $percentuale; ?>%;" aria-valuenow="<?php echo $percentuale; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?php echo $percentuale; ?>%
                    </div>
                </div>

                <?php if ($ha_gia_finanziato_oggi): ?>
                    <div class="alert alert-warning">
                        Hai già finanziato questo progetto oggi. Puoi farlo di nuovo domani.
                    </div>
                <?php else: ?>
                    <form method="POST" class="mt-3">
                        <div class="mb-3">
                            <label for="importo_finanziamento" class="form-label">Importo da finanziare (€):</label>
                            <input type="number" class="form-control" id="importo_finanziamento" name="importo_finanziamento" step="0.01" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-danger">Finanzia</button>
                        <a href="finanzia.php" class="btn btn-secondary ms-2">Torna alla lista</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger mt-4">Errore: progetto non trovato o non disponibile per il finanziamento.</div>
        <a href="finanzia.php" class="btn btn-primary">Torna alla lista</a>
    <?php endif; ?>

    <div class="mt-4">
        <a href="../Autenticazione/<?php 
            echo ($_SESSION['ruolo_utente'] === 'amministratore') ? 'home_amministratore.php' :
                 (($_SESSION['ruolo_utente'] === 'creatore') ? 'home_creatore.php' : 'home_utente.php');
        ?>" class="btn btn-success">Torna alla Home</a>
    </div>
</div>
</body>
</html>
