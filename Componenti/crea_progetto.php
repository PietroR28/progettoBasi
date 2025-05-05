<?php
session_start();

if (!isset($_SESSION['email_utente']) || $_SESSION['ruolo_utente'] !== 'creatore') {
    die("Accesso non autorizzato.");
}

require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = '';
$tipo_selezionato = $_POST['tipo_progetto'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome_progetto']);
    $descrizione = trim($_POST['descrizione_progetto']);
    $budget = (float)$_POST['budget_progetto'];
    $data_limite = $_POST['data_limite_progetto'];
    $tipo = $_POST['tipo_progetto'];
    $email_utente = $_SESSION['email_utente'];

    if ($nome && $descrizione && $budget > 0 && $data_limite && $tipo) {
        $check = $conn->prepare("SELECT COUNT(*) as cnt FROM progetto WHERE nome_progetto = ?");
        $check->bind_param("s", $nome);
        $check->execute();
        $check_result = $check->get_result()->fetch_assoc();
        $check->close();

        if ($check_result['cnt'] > 0) {
            $messaggio = "⚠️ Esiste già un progetto con questo nome. Scegline un altro.";
        } else {
            $stmt = $conn->prepare("CALL CreaProgetto(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsss", $nome, $descrizione, $budget, $data_limite, $tipo, $email_utente);

            if ($stmt->execute()) {
                $stmt->close();
                $foto_caricate = [];

                if ($tipo === 'hardware' && !empty($_POST['componenti'])) {
                    foreach ($_POST['componenti'] as $nome_comp => $info) {
                        if (isset($info['selezionato']) && is_numeric($info['prezzo_componente']) && is_numeric($info['quantita_componente'])) {
                            $stmtComp = $conn->prepare("CALL AssegnaComponente(?, ?, ?, ?)");
                            $stmtComp->bind_param("ssdi", $nome, $nome_comp, $info['prezzo_componente'], $info['quantita_componente']);
                            $stmtComp->execute();
                            $stmtComp->close();
                        }
                    }
                }

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
                                    $stmtFoto = $conn->prepare("INSERT INTO foto_progetto (nome_progetto, percorso) VALUES (?, ?)");
                                    $stmtFoto->bind_param("ss", $nome, $relative_path);
                                    $stmtFoto->execute();
                                    $stmtFoto->close();
                                    $foto_caricate[] = $relative_path;
                                }
                            }
                        }
                    }
                }

                require_once __DIR__ . '/../mongoDB/mongodb.php';
                log_event('PROGETTO_CREATO', $_SESSION['email_utente'], "Creato progetto", [
                    'nome_progetto' => $nome,
                    'budget_progetto' => $budget,
                    'tipo_progetto' => $tipo,
                    'data_limite_progetto' => $data_limite,
                ]);

                $messaggio = "✅ Progetto inserito con successo!";
            } else {
                $messaggio = "❌ Errore durante l'inserimento: " . $stmt->error;
            }
        }
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
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">🚀 Inserisci un nuovo progetto</h1>

    <?php if (!empty($messaggio)): ?>
        <div class="alert <?php echo strpos($messaggio, '✅') === 0 ? 'alert-success' : 'alert-danger'; ?> text-center fw-semibold fs-5 shadow-sm">
            <?php echo htmlspecialchars($messaggio); ?>
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
                    <input type="text" name="nome_progetto" id="nome_progetto" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="descrizione" class="form-label">Descrizione:</label>
                    <textarea name="descrizione_progetto" id="descrizione_progetto" class="form-control" rows="5" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="budget" class="form-label">Budget (€):</label>
                    <input type="number" name="budget_progetto" id="budget_progetto" step="0.01" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="data_limite" class="form-label">Data limite:</label>
                    <input type="date" name="data_limite_progetto" id="data_limite_progetto" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="tipo" class="form-label">Tipo di progetto:</label>
                    <select name="tipo_progetto" id="tipo_progetto" class="form-control" required>
                        <option value="">-- Seleziona --</option>
                        <option value="software" <?= $tipo_selezionato === 'software' ? 'selected' : '' ?>>Software</option>
                        <option value="hardware" <?= $tipo_selezionato === 'hardware' ? 'selected' : '' ?>>Hardware</option>
                    </select>
                </div>

                <div id="sezione-componenti" class="mt-4" style="display: none;">
                    <h5>Componenti disponibili</h5>
                    <?php
                    $res = $conn->query("SELECT nome_componente FROM componente ORDER BY nome_componente ASC");
                    if ($res && $res->num_rows > 0):
                        while ($r = $res->fetch_assoc()):
                            $nome_comp = htmlspecialchars($r['nome_componente']);
                    ?>
                    <div class="card mb-3 p-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="componenti[<?= $nome_comp ?>][selezionato]" id="comp_<?= $nome_comp ?>" value="1">
                            <label class="form-check-label" for="comp_<?= $nome_comp ?>"><strong><?= $nome_comp ?></strong></label>
                        </div>
                        <div class="row">
                            <div class="col">
                                <input type="number" class="form-control" name="componenti[<?= $nome_comp ?>][prezzo_componente]" placeholder="Prezzo (€)" step="0.01" min="0">
                            </div>
                            <div class="col">
                                <input type="number" class="form-control" name="componenti[<?= $nome_comp ?>][quantita_componente]" placeholder="Quantità" min="1">
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                        <p>Nessun componente disponibile.</p>
                    <?php endif; ?>
                </div>

                <div class="mb-3 mt-3">
                    <label for="foto" class="form-label">Foto del progetto:</label>
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
document.getElementById('tipo_progetto').addEventListener('change', function() {
    const section = document.getElementById('sezione-componenti');
    if (section) section.style.display = (this.value === 'hardware') ? 'block' : 'none';
});

window.addEventListener('DOMContentLoaded', function () {
    const tipo = document.getElementById('tipo_progetto');
    const section = document.getElementById('sezione-componenti');
    if (tipo && tipo.value === 'hardware' && section) {
        section.style.display = 'block';
    }
});
</script>
</body>
</html>
