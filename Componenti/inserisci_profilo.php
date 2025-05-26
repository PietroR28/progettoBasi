<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = "";
$errore = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_progetto = $_POST['nome_progetto'];
    $nome_profilo = $_POST['nome_profilo'];
    $skills = $_POST['skills']; 
    $livelli = $_POST['livelli'];       

    if (count($skills) !== count($livelli)) {
        $errore = "Errore: skills e livelli non corrispondono.";
    } else {
        try {
            // Inserire le skill del profilo
            $conn->begin_transaction();
            $success = true;
            
            for ($i = 0; $i < count($skills); $i++) {
                $nome_skill = $skills[$i];
                $livello = $livelli[$i];
                
                // Utilizziamo la stored procedure InserisciSkillProfilo
                $stmtSkill = $conn->prepare("CALL InserisciSkillProfilo(?, ?, ?, ?)");
                $stmtSkill->bind_param("ssis", $nome_profilo, $nome_skill, $livello, $nome_progetto);
                
                if (!$stmtSkill->execute()) {
                    $success = false;
                    $errore = "Errore nell'inserimento della skill: " . $stmtSkill->error;
                    break;
                }
                
                $stmtSkill->close();
            }
            
            if ($success) {
                $conn->commit();
                
                // Prepara dati per il log
                $skills_richieste = [];
                for ($i = 0; $i < count($skills); $i++) {                $skills_richieste[] = [
                        'nome_skill' => $skills[$i],
                        'livello' => $livelli[$i]
                    ];
                }
                
                // Log MongoDB
                if (isset($_SESSION['email_utente'])) {
                    try {
                        require_once __DIR__ . '/../mongoDB/mongodb.php';
                        log_event(
                            'PROFILO_INSERITO',
                            $_SESSION['email_utente'],
                            "Il creatore {$_SESSION['email_utente']} ha inserito il profilo \"$nome_profilo\" per il progetto \"$nome_progetto\".",
                            [
                                'email_utente' => $_SESSION['email_utente'],
                                'nome_profilo' => $nome_profilo,
                                'nome_progetto' => $nome_progetto,
                                'skills_richieste' => $skills_richieste
                            ]
                        );
                    } catch (Exception $e) {
                        // Registriamo l'errore ma non interrompiamo il flusso
                        error_log("❌ Errore nel log MongoDB: " . $e->getMessage());
                    }
                }

                $messaggio = "✅ Profilo creato con successo!";
            } else {
                $conn->rollback();
                if (empty($errore)) {
                    $errore = "Errore nell'inserimento del profilo.";
                }
            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollback();
            }
            $errore = "❌ Errore: " . $e->getMessage();
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
