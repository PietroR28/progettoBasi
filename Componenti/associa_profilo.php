<?php
session_start();
if (!isset($_SESSION['email_utente'])) {
    die("Errore: utente non loggato.");
}
$email_utente = $_SESSION['email_utente'];

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
        function aggiungiSkill() {
            const wrapper = document.createElement('div');
            wrapper.className = 'skill row align-items-end mb-3';
            wrapper.innerHTML = `
                <div class="col-md-5">
                    <label>Skill:</label>
                    <select name="skills[]" class="form-select">
                        <?php
                        $query = "SELECT nome_skill FROM skill";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['nome_skill']}'>{$row['nome_skill']}</option>";
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
                    <button type="button" class="btn btn-danger" onclick="rimuoviSkill(this)">🗑️ Rimuovi</button>
                </div>
            `;
            document.getElementById('skills').appendChild(wrapper);
        }

        function rimuoviSkill(button) {
            const div = button.closest('.skill');
            div.remove();
        }
    </script>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">👤 Associa un Profilo ad un Progetto Software</h2>

    <form method="POST" action="inserisci_profilo.php" class="shadow p-4 bg-white rounded">
        <div class="mb-3">
            <label for="nome_progetto" class="form-label">Progetto Software:</label>
            <select name="nome_progetto" class="form-select" required>
                <?php
                $query = "SELECT nome_progetto FROM progetto WHERE email_utente_creatore = ? AND tipo_progetto = 'software' AND stato_progetto = 'aperto'";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $email_utente);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    echo "<option disabled>Nessun progetto disponibile</option>";
                } else {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['nome_progetto']}'>{$row['nome_progetto']}</option>";
                    }
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="nome_profilo" class="form-label">Nome del Profilo:</label>
            <input type="text" name="nome_profilo" class="form-control" required>
        </div>

        <div id="skills">
            <h5 class="mt-4">🛠️ Skills richieste:</h5>
            <div class="skill row align-items-end mb-3">
                <div class="col-md-5">
                    <label>Skill:</label>
                    <select name="skills[]" class="form-select">
                        <?php
                        $query = "SELECT nome_skill FROM skill";
                        $result = $conn->query($query);
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['nome_skill']}'>{$row['nome_skill']}</option>";
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
                    <button type="button" class="btn btn-danger" onclick="rimuoviSkill(this)">🗑️ Rimuovi</button>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <button type="button" onclick="aggiungiSkill()" class="btn btn-primary">➕ Aggiungi skill</button>
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
