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
        $stmt = $conn->prepare("CALL Crea_progetto(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssi", $nome, $descrizione, $budget, $data_limite, $tipo, $id_utente);

        if ($stmt->execute()) {
            $result = $conn->query("SELECT LAST_INSERT_ID() AS id_progetto");
            $row = $result->fetch_assoc();
            $id_progetto = $row['id_progetto'];
            $foto_caricate = [];

            // Gestione componenti solo per hardware
            if ($tipo === 'hardware' && !empty($_POST['componente'])) {
                foreach ($_POST['componente'] as $comp) {
                    $stmtComp = $conn->prepare("CALL Inserisci_componente(?, ?, ?, ?)");
                    $stmtComp->bind_param("sdii", $comp['nome'], $comp['prezzo'], $comp['quantita'], $id_progetto);
                    $stmtComp->execute();
                    $stmtComp->close();
                }
            }

            // Gestione immagini multiple
            if (isset($_FILES['foto']) && count($_FILES['foto']['name']) > 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $upload_dir = __DIR__ . '/../uploads/';

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                foreach ($_FILES['foto']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['foto']['error'][$key] === 0) {
                        $ext = strtolower(pathinfo($_FILES['foto']['name'][$key], PATHINFO_EXTENSION));

                        if (in_array($ext, $allowed)) {
                            $new_filename = uniqid() . '.' . $ext;
                            $destination = $upload_dir . $new_filename;

                            if (move_uploaded_file($tmp_name, $destination)) {
                                $relative_path = 'uploads/' . $new_filename;
                                $stmtFoto = $conn->prepare("INSERT INTO foto_progetto (id_progetto, percorso) VALUES (?, ?)");
                                $stmtFoto->bind_param("is", $id_progetto, $relative_path);
                                $stmtFoto->execute();
                                $stmtFoto->close();

                                $foto_caricate[] = $relative_path;
                            }
                        }
                    }
                }
            }

            // Log evento su MongoDB
            require_once __DIR__ . '/../mongoDB/mongodb.php';
            log_event(
                'PROGETTO_CREATO',
                $_SESSION['email'],
                "Il creatore '{$_SESSION['email']}' ha creato un nuovo progetto.",
                [
                    'nome_progetto' => $nome,
                    'budget' => $budget,
                    'tipo' => $tipo,
                    'data_limite' => $data_limite,
                    'foto_progetto' => !empty($foto_caricate) ? $foto_caricate : ['nessuna']
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
            <form method="POST" action="" enctype="multipart/form-data">
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

                <div id="sezione-componenti" style="display:none;">
                    <h5>Componenti necessari</h5>
                    <div id="componenti-container"></div>
                    <button type="button" class="btn btn-secondary" onclick="aggiungiComponente()">➕ Aggiungi Componente</button>
                </div>

                <div class="mb-3 mt-3">
                    <label for="foto" class="form-label">Foto del progetto (selezionare più file se desiderato):</label>
                    <input type="file" name="foto[]" id="foto" class="form-control" multiple accept="image/*" required>
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

<script>
document.getElementById('tipo').addEventListener('change', function() {
    if (this.value === 'hardware') {
        document.getElementById('sezione-componenti').style.display = 'block';
    } else {
        document.getElementById('sezione-componenti').style.display = 'none';
    }
});

let contatoreComponenti = 0;

function aggiungiComponente() {
    const container = document.getElementById('componenti-container');
    const div = document.createElement('div');
    div.className = "card p-3 mb-2";

    div.innerHTML = `
        <div class="mb-2">
            <label>Nome componente:</label>
            <input type="text" name="componenti[\${contatoreComponenti}][nome]" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Prezzo (€):</label>
            <input type="number" name="componenti[\${contatoreComponenti}][prezzo]" class="form-control" step="0.01" required>
        </div>
        <div class="mb-2">
            <label>Quantità:</label>
            <input type="number" name="componenti[\${contatoreComponenti}][quantita]" class="form-control" min="1" required>
        </div>
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">🗑️ Rimuovi</button>
        <hr>
    `;

    container.appendChild(div);
    contatoreComponenti++;
}
</script>
</body>
</html>

