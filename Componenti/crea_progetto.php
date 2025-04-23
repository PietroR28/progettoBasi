<?php
session_start();

// Verifica che l'utente sia loggato e sia un creatore
if (!isset($_SESSION['id_utente']) || $_SESSION['ruolo'] !== 'creatore') {
    die("Accesso non autorizzato.");
}

// Connessione al database
require_once __DIR__ . '/../mamp_xampp.php';

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
            require_once __DIR__ . '/../mongoDB/mongodb.php';
        
            log_event(
                'PROGETTO_CREATO',
                $_SESSION['email'],
                "Il creatore '{$_SESSION['email']}' ha creato un nuovo progetto.",
                [
                    'nome_progetto' => $nome,
                    'budget' => $budget,
                    'tipo' => $tipo,
                    'data_limite' => $data_limite
                ]
            );
        
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Stile/crea_progetto.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">🚀 Inserisci un nuovo progetto</h1>

        <?php if (!empty($messaggio)): ?>
            <div class="alert <?php echo strpos($messaggio, '✅') === 0 ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Dettagli del progetto</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome progetto:</label>
                        <input type="text" name="nome" id="nome" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="descrizione" class="form-label">Descrizione:</label>
                        <textarea name="descrizione" id="descrizione" class="form-control" rows="5" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="budget" class="form-label">Budget (€):</label>
                        <input type="number" name="budget" id="budget" step="0.01" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="data_limite" class="form-label">Data limite:</label>
                        <input type="date" name="data_limite" id="data_limite" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo di progetto:</label>
                        <select name="tipo" id="tipo" class="form-control" required>
                            <option value="">-- Seleziona --</option>
                            <option value="software">Software</option>
                            <option value="hardware">Hardware</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">Crea Progetto</button>
                </form>
            </div>
        </div>

        <div class="text-center mt-5 home-button-container">
        <a href="../Autenticazione/home_creatore.php" class="btn btn-success">
             Torna alla Home
        </a>
    </div>
    </div>
</body>

</html>
