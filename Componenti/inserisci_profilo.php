<?php
session_start();

// Abilita errori
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connessione al DB
require_once __DIR__ . '/../mamp_xampp.php';

// Dati dal form
$id_progetto = $_POST['id_progetto'];
$nome_profilo = $_POST['nome_profilo'];
$competenze = $_POST['competenze']; // array di id_competenza
$livelli = $_POST['livelli'];       // array di livelli

// Verifica consistenza
if (count($competenze) !== count($livelli)) {
    die("Errore: competenze e livelli non corrispondono.");
}

// 1. Inserisci il profilo
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

// 2. Inserisci competenze e prepara array per il log
$competenze_richieste = [];

for ($i = 0; $i < count($competenze); $i++) {
    $id_competenza = $competenze[$i];
    $livello = $livelli[$i];

    // Recupera nome competenza
    $query = "SELECT nome FROM competenza WHERE id_competenza = ?";
    $stmtComp = $conn->prepare($query);
    $stmtComp->bind_param("i", $id_competenza);
    $stmtComp->execute();
    $stmtComp->bind_result($nome_competenza);
    $stmtComp->fetch();
    $stmtComp->close();

    // Inserisci nel DB
    $stmt = $conn->prepare("CALL InserisciSkillProfilo(?, ?, ?)");
    $stmt->bind_param("iii", $id_profilo, $id_competenza, $livello);
    $stmt->execute();
    $stmt->close();

    // Aggiungi per log---Il campo competenze_richieste è strutturato come un array di oggetti perché un profilo può avere più competenze.
    // competenze_richieste: Array (1) → vuol dire che hai un array con 1 elemento
    // 0: Object → quell’unico elemento dell’array è un oggetto (con nome_competenza, livello)
    $competenze_richieste[] = [
        'nome_competenza' => $nome_competenza,
        'livello' => $livello
    ];
}

// 3. Recupera nome progetto
$queryProgetto = "SELECT nome FROM progetto WHERE id_progetto = ?";
$check = $conn->prepare($queryProgetto);
$check->bind_param("i", $id_progetto);
$check->execute();
$check->bind_result($nome_progetto);
$check->fetch();
$check->close();

// 4. Log MongoDB
require_once __DIR__ . '/../mongoDB/mongodb.php';

log_event(
    'PROFILO_INSERITO',
    $_SESSION['email'],
    "Il creatore {$_SESSION['email']} ha inserito il profilo \"$nome_profilo\" per il progetto \"$nome_progetto\".",
    [
        'id_utente' => $_SESSION['id_utente'],
        'id_profilo' => $id_profilo,
        'nome_profilo' => $nome_profilo,
        'id_progetto' => $id_progetto,
        'nome_progetto' => $nome_progetto,
        'competenze_richieste' => $competenze_richieste
    ]
);

// Conferma all'utente
echo "<p>✅ Profilo creato con successo!</p>";
echo "<p><a href='../Componenti/associa_profilo.php'>🔙 Torna alla creazione profilo</a></p>";
?>
