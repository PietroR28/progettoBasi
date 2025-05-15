<?php
session_start();

if (!isset($_SESSION['email_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}

// Connessione al database
require_once __DIR__ . '/../mamp_xampp.php';

// Ottiene i progetti aperti più vicini al completamento dalla vista
$progetti_query = "SELECT * FROM progetti_vicini_completamento";
$progetti_result = $conn->query($progetti_query);

if (!$progetti_result) {
    $error_message = "Errore: La vista 'progetti_vicini_completamento' non è disponibile. " . $conn->error;
    $progetti_top = [];
} else {
    $progetti_top = [];
    while ($row = $progetti_result->fetch_assoc()) {
        $progetti_top[] = $row;
    }
}

// Ottiene i primi 3 utenti che hanno erogato più finanziamenti dalla vista
$utenti_query = "SELECT * FROM top_utenti_finanziatori";
$utenti_result = $conn->query($utenti_query);

if (!$utenti_result) {
    $error_message_utenti = "Errore: La vista 'top_utenti_finanziatori' non è disponibile. " . $conn->error;
    $utenti_top = [];
} else {
    $utenti_top = [];
    while ($row = $utenti_result->fetch_assoc()) {
        $utenti_top[] = $row;
    }
}

// Ottiene i primi 3 creatori più affidabili dalla vista
$creatori_query = "SELECT * FROM top_creatori_affidabili";
$creatori_result = $conn->query($creatori_query);

if (!$creatori_result) {
    $error_message_creatori = "Errore: La vista 'top_creatori_affidabili' non è disponibile. " . $conn->error;
    $creatori_top = [];
} else {
    $creatori_top = [];
    while ($row = $creatori_result->fetch_assoc()) {
        $creatori_top[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiche Globali - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">📊 Statistiche Globali</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Progetti aperti più vicini al completamento</h4>
            </div>
            <div class="card-body">
                <?php if (empty($progetti_top)): ?>
                    <p class="text-muted">Non ci sono progetti aperti con finanziamenti al momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome Progetto</th>
                                    <th>Budget</th>
                                    <th>Finanziamenti ricevuti</th>
                                    <th>Differenza</th>
                                    <th>Completamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($progetti_top as $progetto): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($progetto['nome_progetto']) ?></td>
                                        <td>€<?= number_format($progetto['budget_progetto'], 2, ',', '.') ?></td>
                                        <td>€<?= number_format($progetto['totale_finanziamenti'], 2, ',', '.') ?></td>
                                        <td>€<?= number_format($progetto['differenza'], 2, ',', '.') ?></td>
                                        <td>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar 
                                                    <?php
                                                        $percentuale = round($progetto['percentuale_completamento']);
                                                        if ($percentuale <= 33) {
                                                            echo 'bg-danger';
                                                        } elseif ($percentuale <= 70) {
                                                            echo 'bg-warning';
                                                        } else {
                                                            echo 'bg-success';
                                                        }
                                                    ?>"
                                                    role="progressbar"
                                                    style="width: <?= $percentuale ?>%;" 
                                                    aria-valuenow="<?= $percentuale ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    <?= $percentuale ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($error_message_utenti)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message_utenti; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Top utenti per finanziamenti erogati</h4>
            </div>
            <div class="card-body">
                <?php if (empty($utenti_top)): ?>
                    <p class="text-muted">Non ci sono utenti che hanno erogato finanziamenti al momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Posizione</th>
                                    <th>Nickname</th>
                                    <th>Totale finanziamenti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utenti_top as $index => $utente): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($utente['nickname_utente']) ?></td>
                                        <td>€<?= number_format($utente['totale_finanziamenti'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (isset($error_message_creatori)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message_creatori; ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Top creatori per affidabilità</h4>
            </div>
            <div class="card-body">
                <?php if (empty($creatori_top)): ?>
                    <p class="text-muted">Non ci sono creatori con valori di affidabilità al momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Posizione</th>
                                    <th>Nickname</th>
                                    <th>Affidabilità</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($creatori_top as $index => $creatore): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($creatore['nickname_utente']) ?></td>
                                        <td><?= number_format($creatore['affidabilita_utente'], 2, ',', '.') ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-3 mb-4">
            <a href="../Autenticazione/<?php 
                if ($_SESSION['ruolo_utente'] === 'amministratore') {
                    echo 'home_amministratore.php';
                } elseif ($_SESSION['ruolo_utente'] === 'creatore') {
                    echo 'home_creatore.php';
                } else {
                    echo 'home_utente.php';
                }
            ?>" class="btn btn-success">
                Torna alla Home
            </a>
        </div>
    </div>
</body>
</html>