<?php
session_start();

if (!isset($_SESSION['email_utente']) || $_SESSION['ruolo_utente'] !== 'utente') {
    header("Location: ../Autenticazione/login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Home Utente - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-hover {
            transition: transform 0.2s;
        }
        .card-hover:hover {
            transform: scale(1.03);
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Benvenuto, <?php echo htmlspecialchars($_SESSION['nickname_utente']); ?>!</h2>
    <p>Da qui puoi gestire tutte le funzionalità disponibili per te.</p>
    <hr>

    <div class="row row-cols-1 row-cols-md-2 g-4">

        <!-- Le tue Skill -->
        <div class="col">
            <a href="../Componenti/skill_utente.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">🧠 Le tue Skill</h5>
                        <p class="card-text">Aggiungi o aggiorna le skill del tuo profilo.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Progetti disponibili -->
        <div class="col">
            <a href="../Componenti/visualizza_progetto.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">📂 Progetti Disponibili</h5>
                        <p class="card-text">Consulta i progetti disponibili e commentali se necessario.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Finanzia un progetto -->
        <div class="col">
            <a href="../Componenti/finanzia.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">💰 Finanzia un Progetto</h5>
                        <p class="card-text">Sostieni economicamente i progetti che ti ispirano.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Candidatura profilo software -->
        <div class="col">
            <a href="../Componenti/candidatura_profilo.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">📋 Candidati a un Progetto Software</h5>
                        <p class="card-text">Invia la tua candidatura ai progetti in cerca di sviluppatori.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Le Mie Reward -->
        <div class="col">
            <a href="../Componenti/le_mie_reward.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">💫 Le mie Reward</h5>
                        <p class="card-text">Visualizza le reward ottenute.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Statistiche globali -->
        <div class="col">
            <a href="../Componenti/statistiche_globali.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">📊 Statistiche</h5>
                        <p class="card-text">Visualizza le classifiche dei migliori utenti e progetti.</p>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <hr class="mt-5">
    <a href="../Autenticazione/logout.php" class="btn btn-danger">Logout</a>
</div>
</body>
</html>