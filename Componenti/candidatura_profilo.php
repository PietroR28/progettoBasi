<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato.");
}

$id_utente = $_SESSION['id_utente'];

// Link dinamico alla home in base al ruolo
$linkHome = "../Autenticazione/login.php"; // default
if (isset($_SESSION['ruolo'])) {
    switch ($_SESSION['ruolo']) {
        case 'creatore':
            $linkHome = "../Autenticazione/home_creatore.php";
            break;
        case 'utente':
            $linkHome = "../Autenticazione/home_utente.php";
            break;
        case 'admin':
            $linkHome = "../Autenticazione/home_amministratore.php";
            break;
    }
}

// Connessione DB
$conn = new mysqli("localhost", "root", "", "bostarter_db");
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

echo "<h2>Profili richiesti nei progetti software</h2>";

// Progetti software aperti
$queryProgetti = "
    SELECT id_progetto, nome, descrizione
    FROM progetto
    WHERE tipo = 'software' AND stato = 'aperto'
";
$resultProgetti = $conn->query($queryProgetti);

while ($progetto = $resultProgetti->fetch_assoc()) {
    $id_progetto = $progetto['id_progetto'];
    echo "<h3>📦 " . htmlspecialchars($progetto['nome']) . "</h3>";
    echo "<p>" . htmlspecialchars($progetto['descrizione']) . "</p>";

    // Profili associati
    $queryProfili = "
        SELECT id_profilo, nome
        FROM profilo
        WHERE id_progetto = $id_progetto
    ";
    $resProfili = $conn->query($queryProfili);

    if ($resProfili->num_rows === 0) {
        echo "<p style='color:gray;'>Nessun profilo richiesto.</p>";
        continue;
    }

    while ($profilo = $resProfili->fetch_assoc()) {
        $id_profilo = $profilo['id_profilo'];
        echo "<strong>👤 Profilo: " . htmlspecialchars($profilo['nome']) . "</strong><br>";

        // Competenze richieste
        $querySkill = "
            SELECT c.nome AS nome_competenza, ps.livello
            FROM profilo_skill ps
            JOIN competenza c ON ps.id_competenza = c.id_competenza
            WHERE ps.id_profilo = $id_profilo
        ";
        $resSkill = $conn->query($querySkill);
        echo "Competenze richieste: ";
        while ($s = $resSkill->fetch_assoc()) {
            echo htmlspecialchars($s['nome_competenza']) . " (" . $s['livello'] . ") ";
        }
        echo "<br>";

        // Totali e matching
        $queryTot = "SELECT COUNT(*) AS tot FROM profilo_skill WHERE id_profilo = $id_profilo";
        $tot = $conn->query($queryTot)->fetch_assoc()['tot'];

        $queryOk = "
            SELECT COUNT(*) AS tot_ok
            FROM profilo_skill ps
            JOIN utente_competenze uc ON ps.id_competenza = uc.id_competenza
            WHERE ps.id_profilo = $id_profilo
              AND uc.id_utente = $id_utente
              AND uc.livello >= ps.livello
        ";
        $ok = $conn->query($queryOk)->fetch_assoc()['tot_ok'];

        // Verifica candidatura esistente
        $queryCheck = "
            SELECT accettazione 
            FROM candidatura 
            WHERE id_utente = $id_utente AND id_profilo = $id_profilo
        ";
        $resCandidatura = $conn->query($queryCheck);

        if ($resCandidatura->num_rows > 0) {
            $stato = $resCandidatura->fetch_assoc()['accettazione'];
            echo "<p style='color:gray;'>Hai già inviato la candidatura.</p>";

            if ($stato === 'accettata') {
                echo "<p style='color:green;'>✅ La tua candidatura è stata accettata.</p>";
            } elseif ($stato === 'rifiutata') {
                echo "<p style='color:red;'>❌ La tua candidatura è stata rifiutata.</p>";
            } else {
                echo "<p style='color:orange;'>⏳ In attesa di risposta.</p>";
            }
        } elseif ($tot == $ok) {
            echo "<form method='POST' action='candidati.php'>
                    <input type='hidden' name='id_profilo' value='$id_profilo'>
                    <input type='submit' value='Candidati'>
                  </form>";
        } else {
            echo "<p style='color:red;'>Non hai le competenze richieste per candidarti.</p>";
        }

        echo "<hr>";
    }
}

// Torna alla home dinamica
echo "<a href='$linkHome' style='text-decoration: none;'>
        <button type='button' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;'>
            🔙 Torna alla Home
        </button>
      </a>";
?>
