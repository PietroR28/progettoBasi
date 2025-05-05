<?php
session_start();
if (!isset($_SESSION['email_utente'])) {
    die("Errore: utente non loggato.");
}
$id_utente = $_SESSION['email_utente'];

require_once __DIR__ . '/../mamp_xampp.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Associa un Profilo</title>
    <link rel="stylesheet" href="../Stile/associa_profilo.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function aggiungiCompetenza() {
            const wrapper = document.createElement('div');
            wrapper.className = 'competenza row align-items-end mb-3';
            wrapper.innerHTML = `
                <div class="col-md-5">
                    <label>Competenza:</label>
                    <select name="competenze[]" class="form-select">
                        <?php
                        $query = "SELECT id_competenza, nome FROM competenza";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id_competenza']}'>{$row['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Livello:</label>
                    <select name="livelli[]" class="form-select">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger" onclick="rimuoviCompetenza(this)">🗑️ Rimuovi</button>
                </div>
            `;
            document.getElementById('competenze').appendChild(wrapper);
        }

        function rimuoviCompetenza(button) {
            const div = button.closest('.competenza');
            div.remove();
        }
    </script>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">👤 Associa un Profilo ad un Progetto Software</h2>

    <form method="POST" action="inserisci_profilo.php" class="shadow p-4 bg-white rounded">
        <div class="mb-3">
            <label for="id_progetto" class="form-label">Progetto Software:</label>
            <select name="id_progetto" class="form-select" required>
                <?php
                $query = "SELECT id_progetto, nome FROM progetto WHERE id_utente_creatore = $id_utente AND tipo = 'software'";
                $result = $conn->query($query);

                if ($result->num_rows === 0) {
                    echo "<option disabled>Nessun progetto disponibile</option>";
                } else {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id_progetto']}'>{$row['nome']}</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="nome_profilo" class="form-label">Nome del Profilo:</label>
            <input type="text" name="nome_profilo" class="form-control" required>
        </div>

        <div id="competenze">
            <h5 class="mt-4">🛠️ Competenze richieste:</h5>
            <div class="competenza row align-items-end mb-3">
                <div class="col-md-5">
                    <label>Competenza:</label>
                    <select name="competenze[]" class="form-select">
                        <?php
                        $query = "SELECT id_competenza, nome FROM competenza";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id_competenza']}'>{$row['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Livello:</label>
                    <select name="livelli[]" class="form-select">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger" onclick="rimuoviCompetenza(this)">🗑️ Rimuovi</button>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <button type="button" onclick="aggiungiCompetenza()" class="btn btn-primary">➕ Aggiungi competenza</button>
        </div>

        <button type="submit" class="btn btn-secondary"> Associa Profilo</button>
    </form>

    <div class="text-center mt-5 home-button-container">
        <a href="../Autenticazione/home_creatore.php" class="btn btn-success">
            Torna alla Home
        </a>
    </div>
</div>
</body>
</html>
