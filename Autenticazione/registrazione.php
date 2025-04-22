<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }
        
        .illustration {
            background-image: url('images/background.jpg');
            background-size: cover;
            width: 40%;
            min-height: 100vh;
        }
        
        .form-wrapper {
            width: 60%;
            background-color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .form-wrapper2 {
            padding: 0 30px;
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .form-row {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .form-group {
            width: 48%;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-actions {
            width: 100%;
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        
        .role-selector {
            margin: 20px 0;
            text-align: center;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .hidden {
            display: none;
        }

        .error-message {
            color: #f44336;
            font-size: 14px;
            margin-top: 10px;
        }

        .success-message {
            color: #4CAF50;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="illustration"></div>
    <div class="form-wrapper">
        <div class="form-wrapper2">
            <h1>REGISTRAZIONE</h1>

            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="Inserisci la tua email">
                    </div>
                    <div class="form-group">
                        <label for="nickname">Nickname</label>
                        <input type="text" id="nickname" name="nickname" required placeholder="Scegli un nickname">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" required placeholder="Inserisci il tuo nome">
                    </div>
                    <div class="form-group">
                        <label for="cognome">Cognome</label>
                        <input type="text" id="cognome" name="cognome" required placeholder="Inserisci il tuo cognome">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="anno_nascita">Anno di nascita</label>
                        <input type="number" id="anno_nascita" name="anno_nascita" required min="1900" max="2024" placeholder="Es: 1990">
                    </div>
                    <div class="form-group">
                        <label for="luogo_nascita">Luogo di nascita</label>
                        <input type="text" id="luogo_nascita" name="luogo_nascita" required placeholder="Città di nascita">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Inserisci una password">
                    </div>
                    <div class="form-group">
                        <label for="conferma_password">Conferma Password</label>
                        <input type="password" id="conferma_password" name="conferma_password" required placeholder="Ripeti la password">
                    </div>
                </div>

                <div class="role-selector">
                    <h3>Seleziona il tuo ruolo:</h3>
                    <div class="form-actions">
                        <input type="radio" id="utente" name="ruolo" value="utente" checked onclick="toggleSecurityCode()">
                        <label for="utente">Utente</label>
                        
                        <input type="radio" id="amministratore" name="ruolo" value="amministratore" onclick="toggleSecurityCode()">
                        <label for="amministratore">Amministratore</label>
                        
                        <input type="radio" id="creatore" name="ruolo" value="creatore" onclick="toggleSecurityCode()">
                        <label for="creatore">Creatore</label>
                    </div>
                </div>

                <div class="form-row hidden" id="security_code_container">
                    <div class="form-group" style="width: 100%">
                        <label for="codice_sicurezza">Codice di sicurezza</label>
                        <input type="text" id="codice_sicurezza" name="codice_sicurezza" placeholder="Inserisci il codice di sicurezza">
                    </div>
                </div>

                <button type="button" onclick="validaForm()">REGISTRATI</button>
                <input type="submit" id="submit_btn" style="display: none;">
            </form>

            <p>Sei già registrato? <a href="login.php">Accedi</a></p>
            
            <?php if(isset($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
            
            <?php if(isset($success_message)): ?>
                <p class="success-message"><?php echo $success_message; ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleSecurityCode() {
        const ruolo = document.querySelector('input[name="ruolo"]:checked').value;
        const securityCodeContainer = document.getElementById('security_code_container');
        const securityCodeInput = document.getElementById('codice_sicurezza');
        
        if (ruolo === 'amministratore') {
            securityCodeContainer.classList.remove('hidden');
            securityCodeInput.setAttribute('required', '');
        } else {
            securityCodeContainer.classList.add('hidden');
            securityCodeInput.removeAttribute('required');
        }
    }

    function validaForm() {
        const email = document.getElementById('email').value;
        const nickname = document.getElementById('nickname').value;
        const nome = document.getElementById('nome').value;
        const cognome = document.getElementById('cognome').value;
        const annoNascita = document.getElementById('anno_nascita').value;
        const luogoNascita = document.getElementById('luogo_nascita').value;
        const password = document.getElementById('password').value;
        const confermaPassword = document.getElementById('conferma_password').value;
        const ruolo = document.querySelector('input[name="ruolo"]:checked').value;
        const codiceSicurezza = document.getElementById('codice_sicurezza').value;

        // Verifica campi obbligatori
        if (!email || !nickname || !nome || !cognome || !annoNascita || !luogoNascita || !password || !confermaPassword) {
            alert("Compila tutti i campi obbligatori prima di proseguire.");
            return;
        }

        // Verifica formato email
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            alert("Inserisci un indirizzo email valido.");
            return;
        }

        // Verifica anno di nascita
        const currentYear = new Date().getFullYear();
        if (isNaN(annoNascita) || annoNascita < 1900 || annoNascita > currentYear) {
            alert("Inserisci un anno di nascita valido (tra 1900 e " + currentYear + ").");
            return;
        }

        // Verifica password
        if (password !== confermaPassword) {
            alert("Le password non coincidono.");
            return;
        }
        
        // Verifica complessità password
        if (password.length < 8) {
            alert("La password deve essere di almeno 8 caratteri.");
            return;
        }

        // Verifica codice sicurezza solo per amministratori
        if (ruolo === 'amministratore' && !codiceSicurezza) {
            alert("Per il ruolo di amministratore è richiesto il codice di sicurezza.");
            return;
        }

        // Se tutti i controlli sono passati, invia il form
        document.getElementById('submit_btn').click();
    }
</script>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once __DIR__ . '/../mamp_xampp.php';
    require_once __DIR__ . '/../mongoDB/mongodb.php'; // log_event()

    // Ottieni e sanitizza i dati dal form
    $email = $conn->real_escape_string($_POST['email']);
    $nickname = $conn->real_escape_string($_POST['nickname']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $cognome = $conn->real_escape_string($_POST['cognome']);
    $annoNascita = (int)$_POST['anno_nascita'];
    $luogoNascita = $conn->real_escape_string($_POST['luogo_nascita']);
    $ruolo = $conn->real_escape_string($_POST['ruolo']);
    
    // Il codice di sicurezza è necessario solo per amministratori
    $codiceSicurezza = ($ruolo === 'amministratore' && isset($_POST['codice_sicurezza'])) ? $connessione->real_escape_string($_POST['codice_sicurezza']) : '';
    
    // Gestione password - hash della password per sicurezza
    $password = $_POST['password'];
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Chiamata alla stored procedure per la registrazione
        $stmt = $conn->prepare("CALL registrazione(?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssissss", 
            $email, 
            $nickname, 
            $nome, 
            $cognome, 
            $annoNascita, 
            $luogoNascita, 
            $ruolo, 
            $passwordHash,  
            $codiceSicurezza
        );
        
        if ($stmt->execute()) {// MongoDB logging
            require_once __DIR__ . '/../mongoDB/mongodb.php'; // percorso adattalo se serve

            log_event(
                'REGISTRAZIONE_UTENTE',
                $email,
                "L'utente '$email',ha completato la registrazione.",
                [
                    'nickname' => $nickname,
                    'nome' => $nome,
                    'cognome' => $cognome,
                    'anno_nascita' => $annoNascita,
                    'luogo_nascita' => $luogoNascita,
                    'ruolo' => $ruolo
                ]
            );

            $success_message = "Registrazione avvenuta con successo!";
            echo "<script>
                alert('Registrazione avvenuta con successo!');
                window.location.href = 'login.php';
            </script>";
        } else {
            $error_message = "Errore durante la registrazione: " . $connessione->error;
            echo "<script>
                alert('Errore durante la registrazione: " . $connessione->error . "');
            </script>";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Si è verificato un errore: " . $e->getMessage();
        echo "<script>
            alert('Si è verificato un errore: " . $e->getMessage() . "');
        </script>";
    }
    
    $conn->close();
}
?>
</body>
</html>