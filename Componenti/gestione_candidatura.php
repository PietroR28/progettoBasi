<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato.");
}
$id_creatore = $_SESSION['id_utente'];

require_once __DIR__ . '/../mamp_xampp.php';
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
    $queryProgetti = "SELECT id_progetto, nome FROM progetto WHERE id_utente_creatore = $id_creatore";
    $resProgetti = $conn->query($queryProgetti);

    while ($progetto = $resProgetti->fetch_assoc()):
        $id_progetto = $progetto['id_progetto'];
    ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h4 class="card-title">📁 Progetto: <?= htmlspecialchars($progetto['nome']) ?></h4>

                <?php
                $queryProfili = "SELECT id_profilo, nome FROM profilo WHERE id_progetto = $id_progetto";
                $resProfili = $conn->query($queryProfili);

                while ($profilo = $resProfili->fetch_assoc()):
                    $id_profilo = $profilo['id_profilo'];
                ?>
                    <div class="mt-4">
                        <h5>👤 Profilo: <?= htmlspecialchars($profilo['nome']) ?></h5>

                        <?php
                        $queryCandidature = "
                            SELECT c.id_candidatura, c.id_utente, u.nome, c.accettazione
                            FROM candidatura c
                            JOIN utente u ON c.id_utente = u.id_utente
                            WHERE c.id_profilo = $id_profilo
                        ";
                        $resCandidature = $conn->query($queryCandidature);

                        if ($resCandidature->num_rows === 0): ?>
                            <p class="text-muted">Nessuna candidatura ricevuta.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php while ($cand = $resCandidature->fetch_assoc()): ?>
                                    <li class="list-group-item">
                                        🧑 <strong><?= htmlspecialchars($cand['nome']) ?></strong> – Stato:
                                        <?php
                                        if ($cand['accettazione'] === 'accettata') {
                                            echo "<span class='badge bg-success'>Accettata</span>";
                                        } elseif ($cand['accettazione'] === 'rifiutata') {
                                            echo "<span class='badge bg-danger'>Rifiutata</span>";
                                        } else {
                                            echo "<span class='badge bg-warning text-dark'>In attesa</span>";
                                        }
                                        ?>

                                        <?php if ($cand['accettazione'] === 'in attesa'): ?>
                                            <form method="POST" action="gestione_candidatura_azione.php" class="d-inline ms-3">
                                    
                                                <input type="hidden" name="id_candidatura" value="<?= $cand['id_candidatura'] ?>">
                                                <button type="submit" name="azione" value="accetta" class="btn btn-sm btn-success">✅ Accetta</button>
                                                <button type="submit" name="azione" value="rifiuta" class="btn btn-sm btn-danger">❌ Rifiuta</button>
                                                
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
