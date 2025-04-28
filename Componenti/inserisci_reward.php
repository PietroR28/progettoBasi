<?php
session_start();

// Verifica che l'utente sia un creatore
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'creatore') {
    header("Location: ../Autenticazione/login.php");
    exit;
}

require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = "";
$id_utente = $_SESSION['id_utente'];

// Recupera i progetti del creatore
$stmt = $conn->prepare("SELECT id_progetto, nome FROM progetto WHERE id_utente_creatore = ?");
$stmt->bind_param("i", $id_utente);
$stmt->execute();
$progetti = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Gestione inserimento reward
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['descrizione'], $_POST['id_progetto'])) {
    $descrizione = trim($_POST['descrizione']);
    $id_progetto = intval($_POST['id_progetto']);
    $foto_path = "";

    // Gestione immagine
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $upload_dir = '../uploads/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            $new_filename = uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
                $foto_path = 'uploads/' . $new_filename;
            } else {
                $messaggio = "❌ Errore durante l'upload dell'immagine.";
            }
        } else {
            $messaggio = "❌ Formato immagine non supportato.";
        }
    }

    if (empty($messaggio)) {
        // Inserisci la reward
        $stmt = $conn->prepare("CALL InserisciReward(?, ?, ?)");
        $stmt->bind_param("ssi", $descrizione, $foto_path, $id_progetto);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        while ($stmt->more_results() && $stmt->next_result()) {;} // Fix sync
        $stmt->close();

        if ($row['id_reward'] > 0) {
            // LOG MongoDB
            try {
                require_once __DIR__ . '/../mongoDB/mongodb.php';

                $q = $conn->prepare("SELECT nome FROM progetto WHERE id_progetto = ?");
                $q->bind_param("i", $id_progetto);
                $q->execute();
                $q->bind_result($nome_progetto);
                $q->fetch();
                $q->close();

                log_event(
                    'REWARD_INSERITA',
                    $_SESSION['email'],
                    "Il creatore {$_SESSION['email']} ha inserito una reward nel progetto \"$nome_progetto\".",
                    [
                        'id_utente' => $id_utente,
                        'id_progetto' => $id_progetto,
                        'nome_progetto' => $nome_progetto,
                        'id_reward' => $row['id_reward'],
                        'descrizione_reward' => $descrizione,
                        'immagine' => $foto_path ?: 'nessuna'
                    ]
                );
            } catch (Exception $e) {
                error_log("❌ Errore log MongoDB: " . $e->getMessage());
            }

            $messaggio = "✅ " . $row['message'];
        } else {
            $messaggio = "❌ " . $row['message'];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Reward - BOSTARTER</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">🎁 Inserisci Reward</h1>

    <?php if ($messaggio): ?>
        <div class="alert <?php echo str_starts_with($messaggio, '✅') ? 'alert-success' : 'alert-danger'; ?>">
            <?= $messaggio ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Nuova Reward</h5>
        </div>
        <div class="card-body">
            <?php if (empty($progetti)): ?>
                <p class="text-center">Non hai ancora creato progetti.</p>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="id_progetto" class="form-label">Progetto:</label>
                        <select name="id_progetto" id="id_progetto" class="form-control" required>
                            <option value="">-- Seleziona --</option>
                            <?php foreach ($progetti as $progetto): ?>
                                <option value="<?= $progetto['id_progetto'] ?>">
                                    <?= htmlspecialchars($progetto['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione reward:</label>
                        <textarea name="descrizione" id="descrizione" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="foto" class="form-label">Immagine (opzionale):</label>
                        <input type="file" name="foto" id="foto" class="form-control" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-danger">Inserisci Reward</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-5">
        <a href="../Autenticazione/home_creatore.php" class="btn btn-success"> Torna alla Home</a>
    </div>
</div>
</body>
</html>
