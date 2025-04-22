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
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        
        form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        p {
            text-align: center;
            margin-top: 20px;
        }
        
        a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .message {
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
            padding: 8px;
            border-radius: 4px;
        }
        
        .error-message {
            color: white;
            background-color: #f44336;
        }
        
        .success-message {
            color: white;
            background-color: #4CAF50;
        }
        
        .admin-notice {
            background-color: #f9f9f9;
            padding: 12px;
            border-left: 4px solid #4CAF50;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>ACCESSO</h1>
        
        <form method="post">
            <?php if ($is_admin && $show_security_code): ?>
                <!-- Form per amministratori che devono inserire il codice di sicurezza -->
                <div class="admin-notice">
                    <p>Accesso come <strong>amministratore</strong>.<br>
                    Inserisci il codice di sicurezza per completare l'accesso.</p>
                </div>
                
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email_value); ?>">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                
                <div class="form-group">
                    <label for="codice_sicurezza">Codice di sicurezza</label>
                    <input type="password" id="codice_sicurezza" name="codice_sicurezza" required autofocus>
                </div>
                
                <button type="submit">Completa Accesso</button>
            <?php else: ?>
                <!-- Form standard di login -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_value); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit">Accedi</button>
            <?php endif; ?>
        </form>
        
        <?php if(!$is_admin): ?>
        <p>Non hai un account? <a href="../Autenticazione/registrazione.php">Registrati</a></p>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="message error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if(isset($success_message)): ?>
            <div class="message success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>