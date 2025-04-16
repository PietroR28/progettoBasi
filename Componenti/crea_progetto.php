<?php
session_start();

// Verifica che l'utente sia loggato e sia un creatore
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'creatore') {
    die("Accesso non autorizzato.");
}

// Connessione al database
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bostarter_db';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$messaggio = '';

// Gestione del form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $descrizione = trim($_POST['descrizione']);
    $budget = (float)$_POST['budget'];
    $data_limite = $_POST['data_limite'];
    $tipo = $_POST['tipo'];
    $id_utente = $_SESSION['id_utente'];

    if ($nome && $descrizione && $budget > 0 && $data_limite && $tipo) {
        $stmt = $conn->prepare("INSERT INTO progetto (nome, descrizione, budget, data_limite, tipo, stato, id_utente_creatore, data_inserimento) VALUES (?, ?, ?, ?, ?, 'aperto', ?, NOW())");
        $stmt->bind_param("ssdssi", $nome, $descrizione, $budget, $data_limite, $tipo, $id_utente);

        if ($stmt->execute()) {
            $messaggio = "✅ Progetto inserito con successo!";
        } else {
            $messaggio = "❌ Errore durante l'inserimento: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $messaggio = "⚠️ Compila tutti i campi correttamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Nuovo Progetto</title>
</head>
<body>
    <h2>Inserisci un nuovo progetto</h2>

    <?php if (!empty($messaggio)) echo "<p><strong>$messaggio</strong></p>"; ?>

    <form method="POST" action="">
        <label>Nome progetto:</label><br>
        <input type="text" name="nome" required><br><br>

        <label>Descrizione:</label><br>
        <textarea name="descrizione" rows="5" cols="40" required></textarea><br><br>

        <label>Budget (€):</label><br>
        <input type="number" name="budget" step="0.01" required><br><br>

        <label>Data limite:</label><br>
        <input type="date" name="data_limite" required><br><br>

        <label>Tipo:</label><br>
        <select name="tipo" required>
            <option value="">-- Seleziona --</option>
            <option value="software">Software</option>
            <option value="hardware">Hardware</option>
        </select><br><br>

        <button type="submit">Crea Progetto</button>
    </form>

    <br>
    <a href="../Autenticazione/home_creatore.php" style="text-decoration: none;">
    <button type="button" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Torna alla Home
    </button>
    </a>
</body>
</html>
