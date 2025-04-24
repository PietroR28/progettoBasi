<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connessione al DB
require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
    { $nome_competenza = trim($_POST['nome_competenza']); 
    if (!empty($nome_competenza)) 
        { $stmt = $conn->prepare("INSERT INTO competenza (nome) VALUES (?)"); 

$stmt->bind_param("s", $nome_competenza); 
if ($stmt->execute()) {
    require_once __DIR__ . '/../mongoDB/mongodb.php';

    log_event(
        'COMPETENZA_AGGIUNTA',
        $_SESSION['email'],
        "L'amministratore {$_SESSION['email']} ha aggiunto una nuova competenza: \"$nome_competenza\".",
        [
            'id_utente' => $_SESSION['id_utente'],
            'ruolo' => $_SESSION['ruolo'],
            'competenza' => $nome_competenza
        ]
    );

    $messaggio = "✅ Competenza inserita!";
} else { $messaggio = "❌ Errore: " . $stmt->error; } $stmt->close(); 
} else { $messaggio = "❌ Devi inserire un nome!"; } 
}  ?>

<!DOCTYPE html> <html> <head> <meta charset="UTF-8"> 
<title>Nuova Competenza</title> </head> <body> <h2>➕ Inserisci una nuova competenza</h2> <?php if ($messaggio): ?>
<p><strong><?php echo $messaggio; ?></strong></p>
<?php endif; ?> <form method="POST" action=""> 
    <label for="nome_competenza">Nome Competenza:</label><br> <input type="text" name="nome_competenza" required><br><br>
<button type="submit">💾 Salva</button>
</form>
<?php 
// Recupera competenze esistenti 
$query = "SELECT id_competenza, nome FROM competenza"; 
$result = $conn->query($query); 
    if ($result->num_rows > 0) { echo "<h3>📋 Competenze già presenti:</h3><ul>"; 
        while ($row = $result->fetch_assoc()) 
        { echo "<li>" . htmlspecialchars($row['nome']) . "</li>"; } 
        echo "</ul>"; } 
    else { echo "<p>❌ Nessuna competenza trovata.</p>"; } 
$conn->close(); 
?>
<a href="../Autenticazione/home_amministratore.php">⬅ Torna alla Home</a>

</body> </html>