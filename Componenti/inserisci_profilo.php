<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = "";
$errore = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_progetto = $_POST['id_progetto'];
    $nome_profilo = $_POST['nome_profilo'];
    $competenze = $_POST['competenze']; 
    $livelli = $_POST['livelli'];       

    if (count($competenze) !== count($livelli)) {
        $errore = "Errore: competenze e livelli non corrispondono.";
    } else {
        // 1. Inserisci il profilo
        $stmt = $conn->prepare("CALL InserisciProfilo(?, ?)");
        $stmt->bind_param("si", $nome_profilo, $id_progetto);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $row = $result->fetch_assoc();
            $id_profilo = $row['id_profilo'];
            $stmt->close();

            // 2. Inserisci competenze
            $competenze_richieste = [];

            for ($i = 0; $i < count($competenze); $i++) {
                $id_competenza = $competenze[$i];
                $livello = $livelli[$i];

                $query = "SELECT nome FROM competenza WHERE id_competenza = ?";
                $stmtComp = $conn->prepare($query);
                $stmtComp->bind_param("i", $id_competenza);
                $stmtComp->execute();
                $stmtComp->bind_result($nome_competenza);
                $stmtComp->fetch();
                $stmtComp->close();

                $stmtSkill = $conn->prepare("CALL InserisciSkillProfilo(?, ?, ?)");
                $stmtSkill->bind_param("iii", $id_profilo, $id_competenza, $livello);
                $stmtSkill->execute();
                $stmtSkill->close();

                $competenze_richieste[] = [
                    'nome_competenza' => $nome_competenza,
                    'livello' => $livello
                ];
            }

            // 3. Recupera nome progetto
            $queryProgetto = "SELECT nome FROM progetto WHERE id_progetto = ?";
            $check = $conn->prepare($queryProgetto);
            $check->bind_param("i", $id_progetto);
            $check->execute();
            $check->bind_result($nome_progetto);
            $check->fetch();
            $check->close();

            // 4. Log MongoDB
            require_once __DIR__ . '/../mongoDB/mongodb.php';
            log_event(
                'PROFILO_INSERITO',
                $_SESSION['email'],
                "Il creatore {$_SESSION['email']} ha inserito il profilo \"$nome_profilo\" per il progetto \"$nome_progetto\".",
                [
                    'id_utente' => $_SESSION['id_utente'],
                    'id_profilo' => $id_profilo,
                    'nome_profilo' => $nome_profilo,
                    'id_progetto' => $id_progetto,
                    'nome_progetto' => $nome_progetto,
                    'competenze_richieste' => $competenze_richieste
                ]
            );

            $messaggio = "✅ Profilo creato con successo!";
        } else {
            $errore = "Errore nell'inserimento del profilo.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserimento Profilo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../CSS/stile.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="mb-4 text-center">👤 Inserimento Nuovo Profilo</h2>

        <?php if ($messaggio): ?>
            <div class="alert alert-success text-center">
                <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php elseif ($errore): ?>
            <div class="alert alert-danger text-center">
                <?= htmlspecialchars($errore) ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="../Componenti/associa_profilo.php" class="btn btn-success">
                 Torna alla creazione profilo
            </a>
        </div>
    </div>
</div>

</body>
</html>
