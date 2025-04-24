<?php
session_start();

if (!isset($_SESSION['id_utente'])) {
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
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id_progetto, nome, budget, stato, data_limite, descrizione FROM progetto WHERE id_progetto = ? AND stato = 'aperto'");
    $stmt->bind_param("i", $id_progetto);
    $stmt->execute();
    $result = $stmt->get_result();
    $progetto_exists = ($result->num_rows > 0);
    if ($progetto_exists) {
        $progetto_info = $result->fetch_assoc();

        $stmt_fin = $conn->prepare("SELECT SUM(importo) as totale FROM finanziamento WHERE id_progetto = ?");
        $stmt_fin->bind_param("i", $id_progetto);
        $stmt_fin->execute();
        $res_fin = $stmt_fin->get_result();
        if ($res_fin->num_rows > 0) {
            $row_fin = $res_fin->fetch_assoc();
            $totale = $row_fin['totale'] ?? 0;
        }
        $stmt_fin->close();

        $budget = $progetto_info['budget'];
        $percentuale = ($budget > 0) ? min(100, round(($totale / $budget) * 100)) : 0;
    } else {
        $message = "Errore: Il progetto selezionato non esiste o non è aperto per finanziamenti.";
    }
    $stmt->close();
    $conn->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['importo']) && $id_progetto > 0) {
    $importo = floatval(str_replace(',', '.', $_POST['importo']));
    $id_utente = $_SESSION['id_utente'];
    try {
        $conn1 = getConnection();
        $stmt = $conn1->prepare("CALL InserisciFinanziamento(?, ?, ?)");
        $stmt->bind_param("iid", $id_utente, $id_progetto, $importo);
        if ($stmt->execute()) {
            $stmt->close();
            $conn1->close();
            $conn2 = getConnection();
            $query = "SELECT MAX(id_finanziamento) as id FROM finanziamento WHERE id_utente = ? AND id_progetto = ? ORDER BY data DESC LIMIT 1";
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
                    "L'utente {$_SESSION['email']} ha finanziato il progetto \"{$progetto_info['nome']}\" (ID $id_progetto) con l'importo di €$importo.",
                    [
                        'id_utente' => $_SESSION['id_utente'],
                        'id_progetto' => $id_progetto,
                        'nome_progetto' => $progetto_info['nome'],
                        'id_finanziamento' => $id_finanziamento,
                        'importo' => $importo
                    ]
                );
                
            
                $stmt_id->close();
                $conn2->close();
            
                header("Location: scelta_reward.php?id_finanziamento=$id_finanziamento&id_progetto=$id_progetto");
                exit;
            }
             else {
                $message = "Finanziamento registrato, ma non è stato possibile recuperare l'ID.";
                $stmt_id->close();
                $conn2->close();
            }
        } else {
            $message = "Errore durante l'inserimento del finanziamento: " . $stmt->error;
            $stmt->close();
            $conn1->close();
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
                            <td><?php echo $progetto['id_progetto']; ?></td>
                            <td><?php echo htmlspecialchars($progetto['nome']); ?></td>
                            <td><?php echo htmlspecialchars(substr($progetto['descrizione'], 0, 100)) . (strlen($progetto['descrizione']) > 100 ? '...' : ''); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($progetto['data_inserimento'])); ?></td>
                            <td><a href="finanzia.php?id=<?php echo $progetto['id_progetto']; ?>" class="btn btn-primary btn-sm">Seleziona</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif ($progetto_exists): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h3><?php echo htmlspecialchars($progetto_info['nome']); ?></h3>
                <p><strong>Descrizione:</strong> <?php echo nl2br(htmlspecialchars($progetto_info['descrizione'])); ?></p>
                <p>
                    <strong>Budget richiesto:</strong> €<?php echo number_format($progetto_info['budget'], 2, ',', '.'); ?> |
                    <strong>Stato:</strong> <?php echo htmlspecialchars($progetto_info['stato']); ?> |
                    <strong>Data limite:</strong> <?php echo date('d/m/Y', strtotime($progetto_info['data_limite'])); ?>
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

                <form method="post" class="mt-4">
                    <div class="mb-3">
                        <label for="importo" class="form-label">Importo da finanziare (€):</label>
                        <input type="number" class="form-control" id="importo" name="importo" step="0.01" min="1" required>
                    </div>
                    <div class="d-flex">
                        <a href="finanzia.php" class="btn btn-secondary me-2">Torna alla lista</a>
                        <button type="submit" class="btn btn-primary">Finanzia</button>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-danger">Progetto non trovato o non disponibile per il finanziamento.</div>
        <a href="finanzia.php" class="btn btn-primary">Torna alla lista</a>
    <?php endif; ?>

    <div class="mt-4">
        <a href="../Autenticazione/<?php 
            echo ($_SESSION['ruolo'] === 'amministratore') ? 'home_amministratore.php' :
                 (($_SESSION['ruolo'] === 'creatore') ? 'home_creatore.php' : 'home_utente.php');
        ?>" class="btn btn-secondary">Torna alla Home</a>
    </div>
</body>
</html>
