<?php
session_start();

// Verifica che l'utente sia loggato e sia un creatore
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'creatore') {
    header("Location: ../Autenticazione/login.php");
    exit;
}

// Connessione al database
require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = "";
$id_utente = $_SESSION['id_utente'];

// Recupera i progetti del creatore
$stmt = $conn->prepare("
    SELECT id_progetto, nome 
    FROM progetto 
    WHERE id_utente_creatore = ?
");
$stmt->bind_param("i", $id_utente);
$stmt->execute();
$progetti = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Se è stato inviato il form per inserire una nuova reward
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['descrizione'], $_POST['id_progetto'])) {
    $descrizione = trim($_POST['descrizione']);
    $id_progetto = intval($_POST['id_progetto']);
    $foto_path = ""; // Valore predefinito
    
    // Gestione upload immagine
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/';
            
            // Crea la directory se non esiste
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Genera un nome univoco per il file
            $new_filename = uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
                $foto_path = 'uploads/' . $new_filename;
            } else {
                $messaggio = "❌ Errore durante l'upload dell'immagine.";
            }
        } else {
            $messaggio = "❌ Formato immagine non supportato. Utilizza JPG, JPEG, PNG o GIF.";
        }
    }
    
    if (empty($messaggio)) {
        // Chiamata alla stored procedure per inserire la reward
        $stmt = $conn->prepare("CALL AssegnaReward(?, ?, ?)");
        $stmt->bind_param("ssi", $descrizione, $foto_path, $id_progetto);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['id_reward'] > 0) {
            $messaggio = "✅ " . $row['message'];
        } else {
            $messaggio = "❌ " . $row['message'];
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserisci Reward - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">🎁 Inserisci Reward</h1>
        
        <?php if ($messaggio): ?>
            <div class="alert <?php echo strpos($messaggio, '✅') === 0 ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Inserisci una nuova Reward</h5>
            </div>
            <div class="card-body">
                <?php if (empty($progetti)): ?>
                    <p class="text-center">Non hai ancora creato nessun progetto.</p>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="id_progetto" class="form-label">Seleziona progetto:</label>
                            <select name="id_progetto" id="id_progetto" class="form-control" required>
                                <option value="">-- Seleziona --</option>
                                <?php foreach($progetti as $progetto): ?>
                                    <option value="<?php echo $progetto['id_progetto']; ?>">
                                        <?php echo htmlspecialchars($progetto['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descrizione" class="form-label">Descrizione della reward:</label>
                            <textarea name="descrizione" id="descrizione" class="form-control" rows="4" required></textarea>
                            <div class="form-text">Descrivi cosa riceveranno gli utenti che scelgono questa reward.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="foto" class="form-label">Immagine (opzionale):</label>
                            <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                            <div class="form-text">Carica un'immagine per rappresentare la reward (max 2MB).</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Inserisci Reward</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-4 mb-5">
            <a href="../Autenticazione/home_creatore.php" class="btn btn-secondary">Torna alla Home</a>
        </div>
    </div>
</body>
</html>