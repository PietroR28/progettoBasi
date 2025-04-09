<?php
// Inizializza la sessione
session_start();

// Verifica se l'utente è loggato, altrimenti reindirizza al login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Connessione al database
$host = "localhost";
$user = "root";
$password = "";
$database = "bostarter_db";
$connessione = new mysqli($host, $user, $password, $database);

if ($connessione->connect_error) {
    die("Errore di connessione: " . $connessione->connect_error);
}

$message = "";
$id_progetto = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Se l'id progetto viene fornito tramite POST dal form di selezione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['seleziona_progetto'])) {
    if (isset($_POST['id_progetto']) && is_numeric($_POST['id_progetto'])) {
        header("Location: finanzia.php?id=" . intval($_POST['id_progetto']));
        exit;
    } else {
        $message = "Errore: ID progetto non valido.";
    }
}

// Gestione del finanziamento
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['importo']) && $id_progetto > 0) {
    $importo = floatval(str_replace(',', '.', $_POST['importo']));
    $email = $_SESSION['user_id'];
    
    // Recupera l'ID utente dall'email
    $stmt_user = $connessione->prepare("SELECT id_utente FROM utente WHERE email = ?");
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if ($result_user->num_rows > 0) {
        $user_data = $result_user->fetch_assoc();
        $id_utente = $user_data['id_utente'];
        
        try {
            // Chiamata alla stored procedure per inserire il finanziamento
            $stmt = $connessione->prepare("CALL InserisciFinanziamento(?, ?, ?)");
            $stmt->bind_param("iid", $id_utente, $id_progetto, $importo);
            
            if ($stmt->execute()) {
                $message = "Finanziamento di €" . number_format($importo, 2, ',', '.') . " registrato con successo.";
            } else {
                $message = "Errore durante l'inserimento del finanziamento: " . $connessione->error;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $message = "Errore: " . $e->getMessage();
        }
    } else {
        $message = "Errore: Utente non trovato.";
    }
    
    $stmt_user->close();
}

// Verifica l'esistenza del progetto se è stato fornito un ID
$progetto_exists = false;
if ($id_progetto > 0) {
    $stmt = $connessione->prepare("SELECT COUNT(*) as count FROM progetto WHERE id_progetto = ?");
    $stmt->bind_param("i", $id_progetto);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $progetto_exists = ($row['count'] > 0);
    $stmt->close();
}

$connessione->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzia Progetto</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .message { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { background-color: #ffdddd; }
        .success { background-color: #ddffdd; }
        form { margin: 20px 0; }
        label, input { display: block; margin: 10px 0; }
        input[type="number"] { padding: 5px; width: 200px; }
        button { padding: 8px 16px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        a button { background-color: #2196F3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Finanzia Progetto</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo (strpos($message, 'Errore') === 0) ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$id_progetto): ?>
            <!-- Form per selezionare un progetto -->
            <form method="post">
                <label for="id_progetto">ID Progetto:</label>
                <input type="number" id="id_progetto" name="id_progetto" required min="1">
                <button type="submit" name="seleziona_progetto">Seleziona Progetto</button>
            </form>
        <?php elseif ($progetto_exists): ?>
            <!-- Form per finanziare un progetto -->
            <form method="post">
                <label for="importo">Importo da finanziare (€):</label>
                <input type="number" id="importo" name="importo" step="0.01" min="1" required>
                <button type="submit">Finanzia</button>
            </form>
        <?php else: ?>
            <div class="message error">Progetto non trovato.</div>
        <?php endif; ?>
        
        <a href="dashboard.php"><button type="button">Torna alla Dashboard</button></a>
    </div>
</body>
</html>