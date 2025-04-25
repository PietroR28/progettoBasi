<?php
// Inizializza la sessione
session_start();

// Messaggi di errore e successo
$error_message = null;
$success_message = null;

// Verifica se l'utente è già loggato, in tal caso reindirizza
if (isset($_SESSION['id_utente'])) {
    // Reindirizza in base al ruolo dell'utente
    if ($_SESSION['ruolo'] === 'amministratore') {
        header("Location: home_amministratore.php");
        exit;
    } elseif ($_SESSION['ruolo'] === 'creatore') {
        header("Location: home_creatore.php");
        exit;
    } else {
        header("Location: home_utente.php");
        exit;
    }
}

// Flag per mostrare il campo codice di sicurezza
$show_security_code = false;
// Memorizza i valori inviati in precedenza
$email_value = '';
$is_admin = false;

// Gestione del form di login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once __DIR__ . '/../mamp_xampp.php'; // Connessione centralizzata

    // Ottieni e sanitizza i dati dal form
    $email = $conn->real_escape_string($_POST['email']);
    $email_value = $email; // Memorizza per il form
    $password = $_POST['password'];
    
    try {
        // Chiamata alla stored procedure per l'autenticazione
        $stmt = $conn->prepare("CALL autenticazione(?)");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Utente trovato
            $user = $result->fetch_assoc();
            
            // Verifica la password
            if (password_verify($password, $user['password'])) {
                // Per gli amministratori, verifica anche il codice di sicurezza
                if ($user['ruolo'] === 'amministratore') {
                    $is_admin = true; // Segna l'utente come admin
                    
                    if (!isset($_POST['codice_sicurezza']) || empty($_POST['codice_sicurezza'])) {
                        $error_message = "Per completare l'accesso come amministratore, inserisci il codice di sicurezza.";
                        $show_security_code = true;
                    } 
                    elseif ($_POST['codice_sicurezza'] !== $user['codice_sicurezza']) {
                        $error_message = "Codice di sicurezza non valido. Riprova.";
                        $show_security_code = true;
                    } 
                    else {
                        $_SESSION['id_utente'] = $user['id_utente'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['nickname'] = $user['nickname'];
                        $_SESSION['nome'] = $user['nome'];
                        $_SESSION['cognome'] = $user['cognome'];
                        $_SESSION['ruolo'] = $user['ruolo'];
                        
                        header("Location: home_amministratore.php");
                        exit;
                    }
                } else {
                    $_SESSION['id_utente'] = $user['id_utente'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['nickname'] = $user['nickname'];
                    $_SESSION['nome'] = $user['nome'];
                    $_SESSION['cognome'] = $user['cognome'];
                    $_SESSION['ruolo'] = $user['ruolo'];
                    
                    if ($user['ruolo'] === 'creatore') {
                        header("Location: home_creatore.php");
                        exit;
                    } else {
                        header("Location: home_utente.php");
                        exit;
                    }
                }
            } else {
                $error_message = "Password non corretta. Riprova.";
            }
        } else {
            $error_message = "Nessun account trovato con questa email.";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Si è verificato un errore: " . $e->getMessage();
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BoStarter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Stile/login.css" rel="stylesheet">
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <div class="login-container">
        <h1 class="login-title"> <strong> ACCESSO </strong></h1>

        <form method="post">
            <?php if ($is_admin && $show_security_code): ?>
                <div class="admin-notice">
                    Accesso come <strong>amministratore</strong>. Inserisci il codice di sicurezza per completare l'accesso.
                </div>

                <input type="hidden" name="email" value="<?= htmlspecialchars($email_value); ?>">
                <input type="hidden" name="password" value="<?= htmlspecialchars($_POST['password']); ?>">

                <div class="mb-3">
                    <label for="codice_sicurezza" class="form-label">Codice di sicurezza</label>
                    <input type="password" id="codice_sicurezza" name="codice_sicurezza" class="form-control" required autofocus>
                </div>

                <button type="submit" class="btn-login">Completa Accesso</button>
            <?php else: ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email_value); ?>" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn-login">Accedi</button>
            <?php endif; ?>
        </form>

        <?php if (!$is_admin): ?>
            <p class="mt-3 text-center">Non hai un account? <a href="../Autenticazione/registrazione.php" class="text-danger">Registrati</a></p>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="message error-message"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="message success-message"><?= $success_message ?></div>
        <?php endif; ?>
    </div>
</body>
</html>