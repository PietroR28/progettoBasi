<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Stile/registrazione.css">
</head>

<body>
<div class="container-register">
    <div class="illustration d-none d-md-block"></div>

    <div class="form-wrapper">
        <div class="form-wrapper2">
            <h1 class="text-center mb-4"><strong>REGISTRAZIONE</strong></h1>

            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required class="form-control" placeholder="Inserisci la tua email">
                    </div>
                    <div class="form-group">
                        <label for="nickname">Nickname</label>
                        <input type="text" id="nickname" name="nickname" required class="form-control" placeholder="Scegli un nickname">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" required class="form-control" placeholder="Inserisci il tuo nome">
                    </div>
                    <div class="form-group">
                        <label for="cognome">Cognome</label>
                        <input type="text" id="cognome" name="cognome" required class="form-control" placeholder="Inserisci il tuo cognome">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="anno_nascita">Anno di nascita</label>
                        <input type="number" id="anno_nascita" name="anno_nascita" required class="form-control" min="1950" max="2010" placeholder="Es: 1990">
                    </div>
                    <div class="form-group">
                        <label for="luogo_nascita">Luogo di nascita</label>
                        <input type="text" id="luogo_nascita" name="luogo_nascita" required class="form-control" placeholder="Città di nascita">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required class="form-control" placeholder="Inserisci una password">
                    </div>
                    <div class="form-group">
                        <label for="conferma_password">Conferma Password</label>
                        <input type="password" id="conferma_password" name="conferma_password" required class="form-control" placeholder="Ripeti la password">
                    </div>
                </div>

                <div class="role-selector text-center mt-4">
                    <h5>Seleziona il tuo ruolo:</h5>
                    <div class="d-flex justify-content-center gap-3 mt-2">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="utente" name="ruolo" value="utente" checked onclick="toggleSecurityCode()">
                            <label class="form-check-label" for="utente">Utente</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="amministratore" name="ruolo" value="amministratore" onclick="toggleSecurityCode()">
                            <label class="form-check-label" for="amministratore">Amministratore</label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="creatore" name="ruolo" value="creatore" onclick="toggleSecurityCode()">
                            <label class="form-check-label" for="creatore">Creatore</label>
                        </div>
                    </div>
                </div>

                <div class="form-row hidden mt-3" id="security_code_container">
                    <div class="form-group w-100">
                        <label for="codice_sicurezza">Codice di sicurezza</label>
                        <input type="text" id="codice_sicurezza" name="codice_sicurezza" class="form-control" placeholder="Inserisci il codice di sicurezza">
                    </div>
                </div>

                <button type="button" class="btn-custom mt-4" onclick="validaForm()">REGISTRATI</button>
                <input type="submit" id="submit_btn" style="display: none;">
            </form>

            <p class="mt-3">Sei già registrato? <a href="login.php" class="text-danger">Accedi</a></p>

            <?php if(isset($error_message)): ?>
                <p class="error-message"><?= $error_message ?></p>
            <?php endif; ?>

            <?php if(isset($success_message)): ?>
                <p class="success-message"><?= $success_message ?></p>
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
    $codiceSicurezza = ($ruolo === 'amministratore' && isset($_POST['codice_sicurezza'])) ? $conn->real_escape_string($_POST['codice_sicurezza']) : '';
    
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
            $error_message = "Errore durante la registrazione: " . $conn->error;
            echo "<script>
                alert('Errore durante la registrazione: " . $conn->error . "');
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