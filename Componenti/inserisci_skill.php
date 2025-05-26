<?php
session_start();
require_once __DIR__ . '/../mamp_xampp.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// se non loggato, rimanda al login
if (!isset($_SESSION['email_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}
$email_utente = $_SESSION['email_utente'];

$messaggio = "";

// Inserimento nuova skill
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // legge il nome della skill dal form
    $nome_skill = trim($_POST['nome_skill'] ?? '');

    if ($nome_skill !== '') {
        // 1) inserimento con SP
        $stmt = $conn->prepare("CALL InserisciSkill(?)");
        $stmt->bind_param("s", $nome_skill);
        if ($stmt->execute()) {
            $messaggio = "✅ Skill inserita con successo!";
        } else {
            $messaggio = "❌ Errore durante l'inserimento: " . $stmt->error;
        }
        $stmt->close();

        // 2) log su MongoDB
        try {
            require_once __DIR__ . '/../mongoDB/mongodb.php';
            log_event(
                'inserimento_skill',
                $email_utente,
                "L'utente {$email_utente} ha aggiunto la skill \"{$nome_skill}\" alla lista condivisa.",
                [
                    'email_utente' => $email_utente,
                    'skill'        => $nome_skill,
                ]
            );
        } catch (Exception $e) {
            error_log("❌ Errore nel log MongoDB: " . $e->getMessage());
        }
    } else {
        $messaggio = "⚠️ Il nome della skill non può essere vuoto.";
    }
}

// Recupero skill esistenti
$skills = [];
$result = $conn->query("SELECT nome_skill FROM skill ORDER BY nome_skill ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $skills[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Skill</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="mb-4">Inserisci una nuova skill</h2>
        <form method="post">
            <div class="mb-3">
                <label for="nome_skill" class="form-label">Nome skill</label>
                <input type="text" name="nome_skill" id="nome_skill" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Inserisci</button>
        </form>

        <?php if (!empty($messaggio)): ?>
            <div class="alert alert-info mt-3"><?= htmlspecialchars($messaggio) ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($skills)): ?>
        <div class="card shadow mt-4 p-4">
            <h4 class="mb-3">Skills già presenti</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($skills as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['nome_skill']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="../Autenticazione/home_amministratore.php" class="btn btn-success">
            Torna alla Home
        </a>
    </div>
</div>
</body>
</html>