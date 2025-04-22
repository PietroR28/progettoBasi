<?php
// Abilita la visualizzazione degli errori
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connessione al database
require_once __DIR__ . '/../mamp_xampp.php';

// Prendi i dati dal form
$id_progetto = $_POST['id_progetto'];
$nome_profilo = $_POST['nome_profilo'];
$competenze = $_POST['competenze']; // array
$livelli = $_POST['livelli'];       // array

// Verifica che competenze e livelli siano consistenti
if (count($competenze) !== count($livelli)) {
    die("Errore: competenze e livelli non corrispondono.");
}

// 1. Inserisci il profilo e ottieni l'ID
$stmt = $conn->prepare("CALL InserisciProfilo(?, ?)");
$stmt->bind_param("si", $nome_profilo, $id_progetto);
$stmt->execute();

$result = $stmt->get_result();
if (!$result) {
    die("Errore nell'inserimento del profilo.");
}
$row = $result->fetch_assoc();
$id_profilo = $row['id_profilo'];
$stmt->close();

// 2. Inserisci le competenze associate al profilo
for ($i = 0; $i < count($competenze); $i++) {
    $id_competenza = $competenze[$i];
    $livello = $livelli[$i];

    $stmt = $conn->prepare("CALL InserisciSkillProfilo(?, ?, ?)");
    $stmt->bind_param("iii", $id_profilo, $id_competenza, $livello);
    $stmt->execute();
    $stmt->close();
}

// Messaggio finale
echo "<p>✅ Profilo creato con successo!</p>";
echo "<p><a href='../Componenti/associa_profilo.php'>🔙 Torna alla creazione profilo</a></p>";
?>
