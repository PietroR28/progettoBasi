<?php
session_start();

$error_message = null;
$success_message = null;

if (isset($_SESSION['email_utente'])) {
    if ($_SESSION['ruolo_utente'] === 'amministratore') {
        header("Location: home_amministratore.php");
        exit;
    } elseif ($_SESSION['ruolo_utente'] === 'creatore') {
        header("Location: home_creatore.php");
        exit;
    } else {
        header("Location: home_utente.php");
        exit;
    }
}

$show_security_code = false;
$email_value = '';
$is_admin = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once __DIR__ . '/../mamp_xampp.php';

    $email = $conn->real_escape_string($_POST['email_utente']);
    $email_value = $email;
    $password = $_POST['password_utente'];

    try {
        $stmt = $conn->prepare("CALL autenticazione(?)");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_utente'])) {
                if ($user['ruolo_utente'] === 'amministratore') {
                    $is_admin = true;

                    if (!isset($_POST['codice_sicurezza_utente']) || empty($_POST['codice_sicurezza_utente'])) {
                        $error_message = "Per completare l'accesso come amministratore, inserisci il codice di sicurezza.";
                        $show_security_code = true;
                    } elseif ($_POST['codice_sicurezza_utente'] !== $user['codice_sicurezza_utente']) {
                        $error_message = "Codice di sicurezza non valido. Riprova.";
                        $show_security_code = true;
                    } else {
                        $_SESSION['email_utente'] = $user['email_utente'];
                        $_SESSION['nickname_utente'] = $user['nickname_utente'];
                        $_SESSION['nome_utente'] = $user['nome_utente'];
                        $_SESSION['cognome_utente'] = $user['cognome_utente'];
                        $_SESSION['ruolo_utente'] = $user['ruolo_utente'];
                        header("Location: home_amministratore.php");
                        exit;
                    }
                } else {
                    $_SESSION['email_utente'] = $user['email_utente'];
                    $_SESSION['nickname_utente'] = $user['nickname_utente'];
                    $_SESSION['nome_utente'] = $user['nome_utente'];
                    $_SESSION['cognome_utente'] = $user['cognome_utente'];
                    $_SESSION['ruolo_utente'] = $user['ruolo_utente'];

                    if ($user['ruolo_utente'] === 'creatore') {
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
        <h1 class="login-title"><strong>ACCESSO</strong></h1>

        <form method="post" action="">
            <?php if ($is_admin && $show_security_code): ?>
                <div class="alert alert-danger text-center fw-semibold">
                    Accesso come <strong>amministratore</strong>. Inserisci il codice di sicurezza per completare l'accesso.
                </div>

    

                <input type="hidden" name="email_utente" value="<?= htmlspecialchars($email_value); ?>">
                <input type="hidden" name="password_utente" value="<?= isset($_POST['password_utente']) ? htmlspecialchars($_POST['password_utente']) : '' ?>">

                <div class="mb-3">
                    <label for="codice_sicurezza_utente" class="form-label">Codice di sicurezza</label>
                    <input type="password" id="codice_sicurezza_utente" name="codice_sicurezza_utente" class="form-control" required autofocus>
                </div>

                <button type="submit" class="btn btn-success w-100">Completa Accesso</button>
            <?php else: ?>
                <div class="mb-3">
                    <label for="email_utente" class="form-label">Email</label>
                    <input type="email" id="email_utente" name="email_utente" value="<?= htmlspecialchars($email_value); ?>" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="password_utente" class="form-label">Password</label>
                    <input type="password" id="password_utente" name="password_utente" class="form-control" required>
                </div>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger text-center fw-semibold mt-2"><?= $error_message ?></div>
                <?php endif; ?>

                <button type="submit" class="btn btn-success w-100">Accedi</button>
            <?php endif; ?>
        </form>

        <?php if (!$is_admin): ?>
            <p class="mt-3 text-center">Non hai un account? <a href="../Autenticazione/registrazione.php" class="text-danger">Registrati</a></p>
        <?php endif; ?>
    </div>
</body>
</html>