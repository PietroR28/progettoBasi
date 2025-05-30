<?php
session_start();

if (!isset($_SESSION['email_utente']) || $_SESSION['ruolo_utente'] !== 'creatore') {
    header("Location: ../Autenticazione/login.php");
    exit();
}

require_once __DIR__ . '/../mamp_xampp.php'; // o regola il percorso


$id_creatore = $_SESSION['email_utente'];

// Query per contare le candidature in attesa
$query = "
    SELECT COUNT(*) AS tot
    FROM candidatura c
    JOIN profilo p ON c.nome_profilo = p.nome_profilo
    JOIN progetto pr ON p.nome_progetto = pr.nome_progetto
    WHERE pr.email_utente_creatore = ?
      AND c.accettazione_candidatura = 'in attesa'
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id_creatore);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$notifiche = $row['tot'];
$stmt->close();

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
                        <p class="card-text">Aggiungi o aggiorna le competenze del tuo profilo.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Progetti disponibili -->
        <div class="col">
            <a href="../Componenti/risposta_commento.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">📂 Progetti Disponibili</h5>
                        <p class="card-text">Consulta i progetti disponibili, commentali e rispondi ai commenti.</p>
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

        <!-- Crea progetto -->
        <div class="col">
                    <a href="../Componenti/crea_progetto.php" class="text-decoration-none text-dark">
                        <div class="card card-hover shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">🛠️ Crea un nuovo Progetto</h5>
                                <p class="card-text">Inserisci un nuovo progetto.</p>
                            </div>
                        </div>
                    </a>
                </div>

        <!-- Componente -->
        <div class="col">
            <a href="../Componenti/inserisci_componente.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">➕ Aggiungi un nuovo componente</h5>
                        <p class="card-text">Inserisci un nuovo componente per i progetti Hardware.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Gestisci Candidature -->
        <div class="col">
            <a href="../Componenti/gestione_candidatura.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100 position-relative">
                    <?php if ($notifiche > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notifiche; ?>
                        </span>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">📨 Gestisci le Candidature</h5>
                        <p class="card-text">Accetta o rifiuta una candidatura per un tuo progetto Software.</p>
                    </div>
                </div>
            </a>
        </div>

          <!-- Inserisci profilo  -->
          <div class="col">
            <a href="../Componenti/associa_profilo.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">👤 Associa un Profilo</h5>
                        <p class="card-text">Associa un profilo ad un progetto Software.</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Inserisci Reward -->
        <div class="col">
            <a href="../Componenti/inserisci_reward.php" class="text-decoration-none text-dark">
                <div class="card card-hover shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">🎁 Inserisci Reward</h5>
                        <p class="card-text">Crea e gestisci ricompense per i tuoi progetti.</p>
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