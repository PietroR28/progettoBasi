<?php
session_start();

// Verifica login
if (!isset($_SESSION['id_utente'])) {
    die("Errore: utente non loggato.");
}

$id_utente = $_SESSION['id_utente'];
require_once __DIR__ . '/../mamp_xampp.php';

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Associa un Profilo</title>
    <style>
        .competenza {
            margin-bottom: 10px;
        }
        .competenza select, .competenza button {
            margin-right: 10px;
        }
    </style>
    <script>
    function aggiungiCompetenza() {
        const wrapper = document.createElement('div');
        wrapper.className = 'competenza';
        wrapper.innerHTML = `
            Competenza:
            <select name="competenze[]">
                <?php
                $query = "SELECT id_competenza, nome FROM competenza";
                $result = $conn->query($query);
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id_competenza']}'>{$row['nome']}</option>";
                }
                ?>
            </select>

            Livello:
            <select name="livelli[]">
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>

            <button type="button" onclick="rimuoviCompetenza(this)">❌</button>
            <br><br>
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

<h2>Associa un profilo a un progetto software</h2>

<form method="POST" action="inserisci_profilo.php">
    <label for="id_progetto">Seleziona il progetto:</label><br>
    <select name="id_progetto" required>
        <?php
        $query = "SELECT id_progetto, nome 
                  FROM progetto 
                  WHERE id_utente_creatore = $id_utente AND tipo = 'software'";
        $result = $conn->query($query);

        if ($result->num_rows === 0) {
            echo "<option disabled>Nessun progetto software disponibile</option>";
        } else {
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['id_progetto']}'>{$row['nome']}</option>";
            }
        }
        ?>
    </select><br><br>

    <label for="nome_profilo">Nome del profilo:</label><br>
    <input type="text" name="nome_profilo" required><br><br>

    <div id="competenze">
        <h4>Competenze richieste:</h4>
        <div class="competenza">
            Competenza:
            <select name="competenze[]">
                <?php
                $query = "SELECT id_competenza, nome FROM competenza";
                $result = $conn->query($query);
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id_competenza']}'>{$row['nome']}</option>";
                }
                ?>
            </select>

            Livello:
            <select name="livelli[]">
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>

            <button type="button" onclick="rimuoviCompetenza(this)">❌</button>
            <br><br>
        </div>
    </div>

    <button type="button" onclick="aggiungiCompetenza()">Aggiungi competenza</button><br><br>
    <input type="submit" value="Associa Profilo">
</form>

</body>
</html>
    <a href="../Autenticazione/home_creatore.php" style="text-decoration: none;">
    <button type="button" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Torna alla Home
    </button>
    </a>