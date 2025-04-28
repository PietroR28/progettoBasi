<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato.");
}

$id_utente = $_SESSION['id_utente'];

$linkHome = "../Autenticazione/login.php";
if (isset($_SESSION['ruolo'])) {
    switch ($_SESSION['ruolo']) {
        case 'creatore': $linkHome = "../Autenticazione/home_creatore.php"; break;
        case 'utente':   $linkHome = "../Autenticazione/home_utente.php"; break;
        case 'admin':    $linkHome = "../Autenticazione/home_amministratore.php"; break;
    }
}

require_once __DIR__ . '/../mamp_xampp.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profili richiesti nei progetti software</title>
    <link rel="stylesheet" href="../Stile/candidatura_profilo.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">📂 Profili richiesti nei progetti software aperti</h2>

    <?php
    $queryProgetti = "SELECT id_progetto, nome, descrizione FROM progetto WHERE tipo = 'software' AND stato = 'aperto'";
    $resultProgetti = $conn->query($queryProgetti);

    while ($progetto = $resultProgetti->fetch_assoc()):
        $id_progetto = $progetto['id_progetto'];
    ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h4 class="card-title"> <?= htmlspecialchars($progetto['nome']) ?></h4>
                <p class="card-text"><?= htmlspecialchars($progetto['descrizione']) ?></p>
                <hr>

                <?php
                $queryProfili = "SELECT id_profilo, nome FROM profilo WHERE id_progetto = $id_progetto";
                $resProfili = $conn->query($queryProfili);

                if ($resProfili->num_rows === 0):
                    echo "<p class='text-muted'>Nessun profilo richiesto.</p>";
                else:
                    while ($profilo = $resProfili->fetch_assoc()):
                        $id_profilo = $profilo['id_profilo'];
                        echo "<h5 class='mt-3'>👤 Profilo: " . htmlspecialchars($profilo['nome']) . "</h5>";

                        $querySkill = "
                            SELECT c.nome AS nome_competenza, ps.livello
                            FROM profilo_skill ps
                            JOIN competenza c ON ps.id_competenza = c.id_competenza
                            WHERE ps.id_profilo = $id_profilo";
                        $resSkill = $conn->query($querySkill);

                        echo "<p>Competenze richieste:</p><ul>";
                        while ($s = $resSkill->fetch_assoc()) {
                            echo "<li>" . htmlspecialchars($s['nome_competenza']) . " (livello " . $s['livello'] . ")</li>";
                        }
                        echo "</ul>";

                        $queryTot = "SELECT COUNT(*) AS tot FROM profilo_skill WHERE id_profilo = $id_profilo";
                        $tot = $conn->query($queryTot)->fetch_assoc()['tot'];

                        $queryOk = "
                            SELECT COUNT(*) AS tot_ok
                            FROM profilo_skill ps
                            JOIN utente_competenze uc ON ps.id_competenza = uc.id_competenza
                            WHERE ps.id_profilo = $id_profilo
                              AND uc.id_utente = $id_utente
                              AND uc.livello >= ps.livello";
                        $ok = $conn->query($queryOk)->fetch_assoc()['tot_ok'];

                        $queryCheck = "SELECT accettazione FROM candidatura WHERE id_utente = $id_utente AND id_profilo = $id_profilo";
                        $resCandidatura = $conn->query($queryCheck);

                        if ($resCandidatura->num_rows > 0) {
                            $stato = $resCandidatura->fetch_assoc()['accettazione'];
                            echo "<div class='alert alert-secondary mt-2'>Hai già inviato la candidatura.";

                            if ($stato === 'accettata') {
                                echo "<br><span class='badge bg-success'>✅ Accettata</span>";
                            } elseif ($stato === 'rifiutata') {
                                echo "<br><span class='badge bg-danger'>❌ Rifiutata</span>";
                            } else {
                                echo "<br><span class='badge bg-warning text-dark'>⏳ In attesa</span>";
                            }

                            echo "</div>";
                        } elseif ($tot == $ok) {
                            echo "<form method='POST' action='candidati.php' class='mt-2'>
                                    <input type='hidden' name='id_profilo' value='$id_profilo'>
                                    <button type='submit' class='btn btn-danger'>Candidati</button>
                                  </form>";
                        } else {
                            echo "<p class='text-danger'>❌ Non hai le competenze richieste per candidarti.</p>";
                        }

                        echo "<hr>";
                    endwhile;
                endif;
                ?>
            </div>
        </div>
    <?php endwhile; ?>

    <div class="text-center home-button-container">
    <a href="<?= $linkHome ?>" class="btn btn-success">
         Torna alla Home
    </a>
    </div>

</div>
</body>
</html>
