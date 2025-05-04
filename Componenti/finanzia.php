<?php
session_start();

if (!isset($_SESSION['email_utente']) || !isset($_SESSION['ruolo_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}

$message = "";
$id_progetto = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_finanziamento = 0;
$progetto_exists = false;
$progetto_info = [];
$progetti_disponibili = [];
$totale = 0;
$percentuale = 0;

function getConnection() {
    require __DIR__ . '/../mamp_xampp.php';
    return $conn;
}

if (!$id_progetto) {
    $conn = getConnection();
    try {
        $stmt = $conn->prepare("CALL VisualizzaProgettiDisponibili()");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $progetti_disponibili[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $message = "Errore durante il recupero dei progetti: " . $e->getMessage();
    }
    $conn->close();
}

if ($id_progetto > 0) {
    // Verifica se l'utente ha già finanziato oggi questo progetto
        $ha_gia_finanziato_oggi = false;
        if (isset($_SESSION['email_utente'])) {
            $oggi = date('Y-m-d');
            $conn = getConnection();
            $stmt_check = $conn->prepare("SELECT COUNT(*) AS count FROM finanziamento WHERE email_utente = ? AND nome_progetto = ? AND DATE(data) = ?");
            $stmt_check->bind_param("iis", $_SESSION['email_utente'], $id_progetto, $oggi);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            $row_check = $res_check->fetch_assoc();
            $ha_gia_finanziato_oggi = ($row_check['count'] > 0);
            $stmt_check->close();
            $conn->close();
        }

        $conn = getConnection();
        $stmt = $conn->prepare("SELECT nome_progetto, budget_progetto, stato_progetto, data_limite_progetto, descrizione_progetto FROM progetto WHERE nome_progetto = ? AND stato_progetto = 'aperto'");
        $stmt->bind_param("i", $id_progetto);
        $stmt->execute();
        $result = $stmt->get_result();
        $progetto_exists = ($result->num_rows > 0);
        if ($progetto_exists) {
            $progetto_info = $result->fetch_assoc();

        $stmt_fin = $conn->prepare("SELECT SUM(importo_progetto) as totale FROM finanziamento WHERE nome_progetto = ?");
        $stmt_fin->bind_param("i", $id_progetto);
        $stmt_fin->execute();
        $res_fin = $stmt_fin->get_result();
        if ($res_fin->num_rows > 0) {
            $row_fin = $res_fin->fetch_assoc();
            $totale = $row_fin['totale'] ?? 0;
        }
        $stmt_fin->close();

        $budget = $progetto_info['budget_progetto'];
        $percentuale = ($budget > 0) ? min(100, round(($totale / $budget) * 100)) : 0;
    } else {
        $message = "Errore: Il progetto selezionato non esiste o non è aperto per finanziamenti.";
    }
    $stmt->close();
    $conn->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['importo_progetto']) && $id_progetto > 0) {
    $importo = floatval(str_replace(',', '.', $_POST['importo_progetto']));
    $id_utente = $_SESSION['email_utente'];

    try {
        $conn1 = getConnection();

        // Controllo: un solo finanziamento al giorno per ciascun progetto per utente
        $oggi = date('Y-m-d');
        $stmt_check = $conn1->prepare("SELECT COUNT(*) AS count FROM finanziamento WHERE email_utente = ? AND nome_progetto = ? AND DATE(data) = ?");
        $stmt_check->bind_param("iis", $id_utente, $id_progetto, $oggi);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        $row_check = $res_check->fetch_assoc();
        $stmt_check->close();

        if ($row_check['count'] > 0) {
            $message = "Hai già finanziato questo progetto oggi. Puoi farlo di nuovo domani oppure scegliere un altro progetto.";
            $conn1->close();
        } else {
            $stmt = $conn1->prepare("CALL InserisciFinanziamento(?, ?, ?)");
            $stmt->bind_param("iid", $id_utente, $id_progetto, $importo);
            if ($stmt->execute()) {
                $stmt->close();
                $conn1->close();

                $conn2 = getConnection();
                $query = "SELECT MAX(id_finanziamento) as id FROM finanziamento WHERE email_utente = ? AND nome_progetto = ? ORDER BY data DESC LIMIT 1";
                $stmt_id = $conn2->prepare($query);
                $stmt_id->bind_param("ii", $id_utente, $id_progetto);
                $stmt_id->execute();
                $result_id = $stmt_id->get_result();

                if ($result_id->num_rows > 0) {
                    $row = $result_id->fetch_assoc();
                    $id_finanziamento = $row['id'];

                    require_once __DIR__ . '/../mongoDB/mongodb.php';

                    log_event(
                        'FINANZIAMENTO',
                        $_SESSION['email'],
                        "L'utente {$_SESSION['email']} ha finanziato il progetto \"{$progetto_info['nome_progetto']}\" con l'importo di €$importo.",
                        [
                            'email_utente' => $_SESSION['email_utente'],
                            'nome_progetto' => $progetto_info['nome_progetto'],
                            'id_finanziamento' => $id_finanziamento,
                            'importo_finanziamento' => $importo
                        ]
                    );

                    $stmt_id->close();
                    $conn2->close();

                    header("Location: scelta_reward.php?id_finanziamento=$id_finanziamento&nome_progetto=$nome_progetto");
                    exit;
                } else {
                    $message = "Finanziamento registrato, ma non è stato possibile recuperare l'ID.";
                    $stmt_id->close();
                    $conn2->close();
                }
            } else {
                $message = "Errore durante l'inserimento del finanziamento: " . $stmt->error;
                $stmt->close();
                $conn1->close();
            }
        }
    } catch (Exception $e) {
        $message = "Errore: " . $e->getMessage();
    }
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
    <h1>Finanzia Progetto</h1>

    <?php if ($message): ?>
        <div class="alert <?php echo (strpos($message, 'Errore') === 0) ? 'alert-danger' : 'alert-success'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if (!$id_progetto): ?>
        <h2 class="mb-3">Progetti disponibili</h2>
        <?php if (empty($progetti_disponibili)): ?>
            <div class="alert alert-info">Nessun progetto disponibile al momento.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr><th>ID</th><th>Titolo</th><th>Descrizione</th><th>Data</th><th>Azione</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($progetti_disponibili as $progetto): ?>
                        <tr>
                            
                            <td><?php echo htmlspecialchars($progetto['nome_progetto']); ?></td>
                            <td><?php echo htmlspecialchars(substr($progetto['descrizione_progetto'], 0, 100)) . (strlen($progetto['descrizione_progetto']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($progetto['data_inserimento_progetto'])); ?></td>
                            <td><a href="finanzia.php?id=<?php echo $progetto['nome_progetto']; ?>" class="btn btn-danger btn-sm">Seleziona</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif ($progetto_exists): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h3><?php echo htmlspecialchars($progetto_info['nome_progetto']); ?></h3>
                <p><strong>Descrizione:</strong> <?php echo nl2br(htmlspecialchars($progetto_info['descrizione_progetto'])); ?></p>
                <p>
                    <strong>Budget richiesto:</strong> €<?php echo number_format($progetto_info['budget_progetto'], 2, ',', '.'); ?> |
                    <strong>Stato:</strong> <?php echo htmlspecialchars($progetto_info['stato_progetto']); ?> |
                    <strong>Data limite:</strong> <?php echo date('d/m/Y', strtotime($progetto_info['data_limite_progetto'])); ?>
                </p>
                <p>
                    <strong>Finanziato:</strong> €<?php echo number_format($totale, 2, ',', '.'); ?> (<?php echo $percentuale; ?>%)
                </p>

            <div class="progress" style="height: 25px;">
                <div class="progress-bar 
                    <?php
                        if ($percentuale <= 33) {
                            echo 'bg-danger';
                        } elseif ($percentuale <= 70) {
                            echo 'bg-warning';
                        } else {
                            echo 'bg-success';
                        }
                    ?>"
                    role="progressbar" 
                    style="width: <?php echo $percentuale; ?>%;" 
                    aria-valuenow="<?php echo $percentuale; ?>" 
                    aria-valuemin="0" 
                    aria-valuemax="100">
                    <?php echo $percentuale; ?>%
                </div>
            </div>

            <?php if ($ha_gia_finanziato_oggi): ?>
                <div class="alert alert-warning mt-4">
                    Hai già finanziato questo progetto oggi. Puoi farlo di nuovo domani.
                </div>
            <?php else: ?>
                <form method="post" class="mt-4">
                    <div class="mb-3">
                        <label for="importo" class="form-label">Importo da finanziare (€):</label>
                        <input type="number" class="form-control" id="importo_progetto" name="importo_progetto" step="0.01" min="1" required>
                    </div>
                    <div class="d-flex">
                        <a href="finanzia.php" class="btn btn-secondary me-2">Torna alla lista</a>
                        <button type="submit" class="btn btn-danger">Finanzia</button>
                    </div>
                </form>
            <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-danger">Progetto non trovato o non disponibile per il finanziamento.</div>
        <a href="finanzia.php" class="btn btn-primary">Torna alla lista</a>
    <?php endif; ?>

    <div class="mt-4">
        <a href="../Autenticazione/<?php 
            echo ($_SESSION['ruolo_utente'] === 'amministratore') ? 'home_amministratore.php' :
                 (($_SESSION['ruolo_utente'] === 'creatore') ? 'home_creatore.php' : 'home_utente.php');
        ?>" class="btn btn-success">Torna alla Home</a>
    </div>
</body>
</html>
