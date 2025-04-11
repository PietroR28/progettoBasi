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
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($_SESSION['ruolo'] === 'creatore') {
        header("Location: creator_dashboard.php");
        exit;
    } else {
        header("Location: home_utente.php");
        exit;
    }
}

// Gestione del form di login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "bostarter_db";

    // Crea connessione
    $connessione = new mysqli($host, $user, $password, $database);

    // Verifica connessione
    if ($connessione->connect_error) {
        $error_message = "Errore di connessione: " . $connessione->connect_error;
    } else {
        // Ottieni e sanitizza i dati dal form
        $email = $connessione->real_escape_string($_POST['email']);
        $password = $_POST['password'];

        try {
            // Chiamata alla stored procedure per l'autenticazione
            $stmt = $connessione->prepare("CALL autenticazione(?)");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Utente trovato
                $user = $result->fetch_assoc();
                
                // Verifica la password
                if (password_verify($password, $user['password'])) {
                    // Password corretta, creare la sessione
                    $_SESSION['id_utente'] = $user['id_utente'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['nickname'] = $user['nickname'];
                    $_SESSION['nome'] = $user['nome'];
                    $_SESSION['cognome'] = $user['cognome'];
                    $_SESSION['ruolo'] = $user['ruolo'];
                    
                    // Reindirizza in base al ruolo
                    if ($user['ruolo'] === 'amministratore') {
                        header("Location: admin_dashboard.php");
                        exit;
                    } elseif ($user['ruolo'] === 'creatore') {
                        header("Location: creator_dashboard.php");
                        exit;
                    } else {
                        header("Location: home_utente.php");
                        exit;
                    }
                } else {
                    // Password errata
                    $error_message = "Password non corretta. Riprova.";
                }
            } else {
                // Utente non trovato
                $error_message = "Nessun account trovato con questa email.";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Si è verificato un errore: " . $e->getMessage();
        }
        
        $connessione->close();
    }
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
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
            max-width: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        h1 {
            margin-bottom: 20px;
        }
        
        form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 20px;
            width: 100%;
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
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        p {
            margin-top: 20px;
        }
        
        a {
            color: #4CAF50;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #f44336;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
        }
        
        .success-message {
            color: #4CAF50;
            font-size: 14px;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="illustration"></div>
    <div class="form-wrapper">
        <div class="form-wrapper2">
            <h1>LOGIN</h1>
            <form method="post">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Inserisci la tua email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Inserisci la password">
                </div>
                <button type="submit">Accedi</button>
            </form>
            <p>Non hai un account? <a href="registrazione.php">Registrati</a></p>
            
            <?php if(isset($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
            
            <?php if(isset($success_message)): ?>
                <p class="success-message"><?php echo $success_message; ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>