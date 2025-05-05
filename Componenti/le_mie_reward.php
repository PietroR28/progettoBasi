<?php
session_start();
if (!isset($_SESSION['email_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}

require_once __DIR__ . '/../mamp_xampp.php';

$email_utente = $_SESSION['email_utente'];

// Recupera tutte le reward ottenute dai finanziamenti dell'utente
$stmt = $conn->prepare("
    SELECT 
        f.id_finanziamento,
        f.importo_finanziamento AS importo,
        f.data_finanziamento AS data,
        f.nome_progetto,
        r.descrizione_reward,
        r.foto_reward
    FROM finanziamento f
    LEFT JOIN reward r ON f.id_reward = r.id_reward
    WHERE f.email_utente = ?
    ORDER BY f.data_finanziamento DESC
");
$stmt->bind_param("s", $email_utente);
$stmt->execute();
$result = $stmt->get_result();
$rewards = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Le mie Reward</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Stile/le_mie_reward.css" rel="stylesheet">

</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">💫 Le mie Reward Ricevute</h1>

    <?php if (empty($rewards)): ?>
        <div class="alert alert-info">Non hai ancora ottenuto reward.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($rewards as $r): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                    <?php if (!empty($r['foto_reward'])): ?>
                        <img src="../<?= htmlspecialchars($r['foto_reward']) ?>" class="reward-image" alt="Reward">
                    <?php endif; ?>

                    <div class="card-body">
                        <p class="card-text">
                            <strong>Nome progetto:</strong> <?= htmlspecialchars($r['nome_progetto']) ?><br>
                            <strong>Importo finanziato:</strong> €<?= number_format($r['importo'], 2, ',', '.') ?><br>
                            <strong>Data finanziamento:</strong> <?= date('d/m/Y', strtotime($r['data'])) ?><br>
                            <strong>Reward:</strong> <?= nl2br(htmlspecialchars($r['descrizione_reward'] ?? 'Nessuna reward assegnata')) ?>
                        </p>
                    </div>
                        <div class="card-footer text-muted">
                            Finanziamento #<?= $r['id_finanziamento'] ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <a href="../Autenticazione/<?php 
            echo ($_SESSION['ruolo_utente'] === 'amministratore') ? 'home_amministratore.php' :
                (($_SESSION['ruolo_utente'] === 'creatore') ? 'home_creatore.php' : 'home_utente.php');
        ?>" class="btn btn-success me-2">Torna alla Home</a>    
        <a href="finanzia.php" class="btn btn-success">Vai ai progetti da finanziare</a>    
    </div>
</div>
</body>
</html>
