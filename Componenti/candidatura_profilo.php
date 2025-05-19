<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['email_utente'])) {
    die("Accesso negato.");
}

$email_utente = $_SESSION['email_utente'];

$linkHome = "../Autenticazione/login.php";
if (isset($_SESSION['ruolo_utente'])) {
    switch ($_SESSION['ruolo_utente']) {
        case 'creatore': $linkHome = "../Autenticazione/home_creatore.php"; break;
        case 'utente':   $linkHome = "../Autenticazione/home_utente.php"; break;
        case 'amministratore':    $linkHome = "../Autenticazione/home_amministratore.php"; break;
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
    $queryProgetti = "SELECT nome_progetto, descrizione_progetto FROM progetto WHERE tipo_progetto = 'software' AND stato_progetto = 'aperto'";
    $resultProgetti = $conn->query($queryProgetti);

    while ($progetto = $resultProgetti->fetch_assoc()):
        $nome_progetto = $progetto['nome_progetto'];
    ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h4 class="card-title"><?= htmlspecialchars($progetto['nome_progetto']) ?></h4>
                <p class="card-text"><?= htmlspecialchars($progetto['descrizione_progetto']) ?></p>
                <hr>

                <?php
                $queryProfili = "SELECT nome_profilo FROM profilo WHERE nome_progetto = '$nome_progetto'";
                $resProfili = $conn->query($queryProfili);

                if ($resProfili->num_rows === 0):
                    echo "<p class='text-muted'>Nessun profilo richiesto.</p>";
                else:
                    while ($profilo = $resProfili->fetch_assoc()):
                        $nome_profilo = $profilo['nome_profilo'];
                        echo "<h5 class='mt-3'>👤 Profilo: " . htmlspecialchars($profilo['nome_profilo']) . "</h5>";

                        $querySkill = "
                            SELECT s.nome_skill, p.livello_profilo
                            FROM profilo p
                            JOIN skill s ON p.nome_skill = s.nome_skill
                            WHERE p.nome_profilo = '$nome_profilo'";
                        $resSkill = $conn->query($querySkill);

                        echo "<p>Competenze richieste:</p><ul>";
                        while ($s = $resSkill->fetch_assoc()) {
                            echo "<li>" . htmlspecialchars($s['nome_skill']) . " (livello " . $s['livello_profilo'] . ")</li>";
                        }
                        echo "</ul>";

                        $queryTot = "SELECT COUNT(*) AS tot FROM profilo WHERE nome_profilo = '$nome_profilo'";
                        $tot = $conn->query($queryTot)->fetch_assoc()['tot'];

                        $queryOk = "
                            SELECT COUNT(*) AS tot_ok
                            FROM profilo p
                            JOIN utente_skill us ON p.nome_skill = us.nome_skill
                            WHERE p.nome_profilo = '$nome_profilo'
                              AND us.email_utente = '$email_utente'
                              AND us.livello_utente_skill >= p.livello_profilo";
                        $ok = $conn->query($queryOk)->fetch_assoc()['tot_ok'];

                        $queryCheck = "SELECT accettazione_candidatura FROM candidatura WHERE email_utente = '$email_utente' AND nome_profilo = '$nome_profilo'";
                        $resCandidatura = $conn->query($queryCheck);

                        if ($resCandidatura->num_rows > 0) {
                            $stato = $resCandidatura->fetch_assoc()['accettazione_candidatura'];
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
                                    <input type='hidden' name='nome_profilo' value='$nome_profilo'>
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
