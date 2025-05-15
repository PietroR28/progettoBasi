<?php
session_start();

if (!isset($_SESSION['email_utente']) || $_SESSION['ruolo_utente'] !== 'creatore') {
    header("Location: ../Autenticazione/login.php");
    exit;
}

require_once __DIR__ . '/../mamp_xampp.php';
$email_utente = $_SESSION['email_utente'];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Candidature ricevute</title>
    <link rel="stylesheet" href="../Stile/gestione_candidatura.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">📨 Candidature ricevute</h2>

    <?php
    // 1. Progetti
    $stmtProgetti = $conn->prepare("SELECT nome_progetto FROM progetto WHERE email_utente_creatore = ? AND tipo_progetto = 'software'");
    $stmtProgetti->bind_param("s", $email_utente);
    $stmtProgetti->execute();
    $resProgetti = $stmtProgetti->get_result();

    while ($progetto = $resProgetti->fetch_assoc()):
        $nome_progetto = $progetto['nome_progetto'];
    ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h4 class="card-title">📁 Progetto: <?= htmlspecialchars($nome_progetto) ?></h4>

                <?php
                // 2. Profili
                $stmtProfili = $conn->prepare("SELECT nome_profilo FROM profilo WHERE nome_progetto = ?");
                $stmtProfili->bind_param("s", $nome_progetto);
                $stmtProfili->execute();
                $resProfili = $stmtProfili->get_result();

                while ($profilo = $resProfili->fetch_assoc()):
                    $nome_profilo = $profilo['nome_profilo'];
                ?>
                    <div class="mt-4">
                        <h5>👤 Profilo: <?= htmlspecialchars($nome_profilo) ?></h5>

                        <?php
                        // 3. Candidature
                        $stmtCandidature = $conn->prepare("
                            SELECT c.data_candidatura, c.email_utente, u.nome_utente, c.accettazione_candidatura
                            FROM candidatura c
                            JOIN utente u ON c.email_utente = u.email_utente
                            WHERE c.nome_profilo = ?
                        ");
                        $stmtCandidature->bind_param("s", $nome_profilo);
                        $stmtCandidature->execute();
                        $resCandidature = $stmtCandidature->get_result();

                        if ($resCandidature->num_rows === 0): ?>
                            <p class="text-muted">Nessuna candidatura ricevuta.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php while ($cand = $resCandidature->fetch_assoc()): ?>
                                    <li class="list-group-item">
                                        🧑 <strong><?= htmlspecialchars($cand['nome_utente']) ?></strong> – Stato:
                                        <?php
                                        if ($cand['accettazione_candidatura'] === 'accettata') {
                                            echo "<span class='badge bg-success'>Accettata</span>";
                                        } elseif ($cand['accettazione_candidatura'] === 'rifiutata') {
                                            echo "<span class='badge bg-danger'>Rifiutata</span>";
                                        } else {
                                            echo "<span class='badge bg-warning text-dark'>In attesa</span>";
                                        }
                                        ?>

                                        <?php if ($cand['accettazione_candidatura'] === 'in attesa'): ?>
    <form method="POST" action="gestione_candidatura_azione.php" class="d-inline ms-3">
        <input type="hidden" name="data_candidatura" value="<?= $cand['data_candidatura'] ?>">
        <input type="hidden" name="nome_profilo" value="<?= $nome_profilo ?>">
        <button type="submit" name="azione" value="accetta" class="btn btn-sm btn-success">✅ Accetta</button>
        <button type="submit" name="azione" value="rifiuta" class="btn btn-sm btn-danger">🗑️ Rifiuta</button>
    </form>
<?php endif; ?>

                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <hr>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endwhile; ?>

    <div class="text-center mt-5 home-button-container">
        <a href="../Autenticazione/home_creatore.php" class="btn btn-success">
            Torna alla Home
        </a>
    </div>
</div>
</body>
</html>
