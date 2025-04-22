<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    die("Accesso negato.");
}
$id_creatore = $_SESSION['id_utente'];

require_once __DIR__ . '/../mamp_xampp.php';

echo "<h2>Candidature ricevute</h2>";

$queryProgetti = "
    SELECT id_progetto, nome 
    FROM progetto 
    WHERE id_utente_creatore = $id_creatore
";
$resProgetti = $conn->query($queryProgetti);

while ($progetto = $resProgetti->fetch_assoc()) {
    echo "<h3>📁 Progetto: " . $progetto['nome'] . "</h3>";

    $id_progetto = $progetto['id_progetto'];

    // Profili del progetto
    $queryProfili = "
        SELECT id_profilo, nome 
        FROM profilo 
        WHERE id_progetto = $id_progetto
    ";
    $resProfili = $conn->query($queryProfili);

    while ($profilo = $resProfili->fetch_assoc()) {
        $id_profilo = $profilo['id_profilo'];

        echo "<strong>👤 Profilo: " . $profilo['nome'] . "</strong><br>";

        // Candidature per il profilo
        $queryCandidature = "
            SELECT c.id_candidatura, c.id_utente, u.nome, c.accettazione
            FROM candidatura c
            JOIN utente u ON c.id_utente = u.id_utente
            WHERE c.id_profilo = $id_profilo
        ";
        $resCandidature = $conn->query($queryCandidature);

        if ($resCandidature->num_rows === 0) {
            echo "<p style='color:gray;'>Nessuna candidatura ricevuta.</p>";
        }

        while ($cand = $resCandidature->fetch_assoc()) {
            echo "🧑 Utente: <strong>" . $cand['nome'] . "</strong> – Stato: " . $cand['accettazione'];

            if ($cand['accettazione'] === 'in attesa') {
                echo "<form method='POST' action='gestione_candidatura_azione.php' style='display:inline; margin-left:10px;'>
                        <input type='hidden' name='id_candidatura' value='" . $cand['id_candidatura'] . "'>
                        <button type='submit' name='azione' value='accetta'>✅ Accetta</button>
                        <button type='submit' name='azione' value='rifiuta'>❌ Rifiuta</button>
                      </form>";
            }

            echo "<br>";
        }

        echo "<hr>";
    }
}
?>

    <a href="../Autenticazione/home_creatore.php" style="text-decoration: none;">
    <button type="button" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Torna alla Home
    </button>
    </a>