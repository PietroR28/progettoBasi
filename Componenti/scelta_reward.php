<?php
session_start();

// Verifica che l'utente sia loggato
if (!isset($_SESSION['id_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}

// Connessione al database
require_once __DIR__ . '/../mamp_xampp.php';

$messaggio = "";
$id_utente = $_SESSION['id_utente'];

// Recupera i parametri dall'URL - quando si arriva direttamente da finanzia.php
$id_finanziamento = isset($_GET['id_finanziamento']) ? intval($_GET['id_finanziamento']) : 0;
$id_progetto = isset($_GET['id_progetto']) ? intval($_GET['id_progetto']) : 0;
$nome_progetto = "";

// Verifica se il finanziamento appartiene all'utente corrente
if ($id_finanziamento > 0) {
    $stmt = $conn->prepare("
        SELECT f.id_finanziamento, f.importo, f.data, p.nome as nome_progetto, p.id_progetto
        FROM finanziamento f
        JOIN progetto p ON f.id_progetto = p.id_progetto
        WHERE f.id_finanziamento = ? AND f.id_utente = ? AND f.id_reward IS NULL
    ");
    
    $stmt->bind_param("ii", $id_finanziamento, $id_utente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Se non troviamo il finanziamento o non appartiene all'utente, reset dei parametri
    if ($result->num_rows == 0) {
        $messaggio = "Errore: finanziamento non trovato o non autorizzato.";
        $id_finanziamento = 0;
        $id_progetto = 0;
    } else {
        $finanziamento_corrente = $result->fetch_assoc();
        $id_progetto = $finanziamento_corrente['id_progetto']; // Assicuriamoci di avere l'id progetto corretto
        $nome_progetto = $finanziamento_corrente['nome_progetto'];
        $messaggio = "Seleziona una reward per il tuo finanziamento di €" . number_format($finanziamento_corrente['importo'], 2, ',', '.') . 
                     " per il progetto \"" . htmlspecialchars($finanziamento_corrente['nome_progetto']) . "\"";
    }
    $stmt->close();
}

$rewards = [];
if ($id_progetto > 0) {
    // Chiamata alla stored procedure per visualizzare le reward del progetto
    $stmt = $conn->prepare("CALL VisualizzaRewardProgetto(?)");
    $stmt->bind_param("i", $id_progetto);
    $stmt->execute();
    $rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Gestisce l'assegnazione di una reward
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reward'])) {
    $id_reward = intval($_POST['id_reward']);
    $id_finanziamento = intval($_POST['id_finanziamento']);

    // Chiamata alla stored procedure per assegnare la reward
    $stmt = $conn->prepare("CALL AssegnaReward(?, ?, ?)");
    $stmt->bind_param("iii", $id_utente, $id_finanziamento, $id_reward);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['risultato'] === 'OK') {
        header("Location: scelta_reward.php?success=1");
        exit;
    } else {
        $messaggio = "Errore nell'assegnazione della reward. Potrebbe essere già stata assegnata o non essere compatibile con questo finanziamento.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scegli Reward - BOSTARTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../Stile/scelta_reward.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">🎁 Scegli una Reward</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <h4 class="alert-heading">Reward assegnata con successo!</h4>
                <p>La tua reward è stata assegnata al finanziamento.</p>
                <hr>
                <p class="mb-0">
                    <a href="../Autenticazione/<?php 
                        if ($_SESSION['ruolo'] === 'amministratore') {
                            echo 'home_amministratore.php';
                        } elseif ($_SESSION['ruolo'] === 'creatore') {
                            echo 'home_creatore.php';
                        } else {
                            echo 'home_utente.php';
                        }
                    ?>" class="btn btn-success">Torna alla Home</a>
                </p>
            </div>
        <?php elseif ($id_finanziamento && $id_progetto): ?>
            <?php if ($messaggio): ?>
                <div class="alert <?php echo strpos($messaggio, 'Errore') === false ? 'alert-info' : 'alert-danger'; ?>">
                    <?php echo $messaggio; ?>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Rewards disponibili per <?php echo htmlspecialchars($nome_progetto); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rewards)): ?>
                        <div class="alert alert-warning">
                            <p class="mb-0">Non ci sono reward disponibili per questo progetto.</p>
                        </div>
                        <div class="mt-3">
                            <a href="../Autenticazione/<?php 
                                if ($_SESSION['ruolo'] === 'amministratore') {
                                    echo 'home_amministratore.php';
                                } elseif ($_SESSION['ruolo'] === 'creatore') {
                                    echo 'home_creatore.php';
                                } else {
                                    echo 'home_utente.php';
                                }
                            ?>" class="btn btn-success">Torna alla Home</a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="id_finanziamento" value="<?php echo $id_finanziamento; ?>">
                            
                            <div class="row row-cols-1 row-cols-md-2 g-4">
                                <?php foreach($rewards as $reward): ?>
                                    <div class="col">
                                        <div class="card reward-card h-100" onclick="selectReward(this, <?php echo $reward['id_reward']; ?>)">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="id_reward" 
                                                          id="reward_<?php echo $reward['id_reward']; ?>" 
                                                          value="<?php echo $reward['id_reward']; ?>" required>
                                                    <label class="form-check-label" for="reward_<?php echo $reward['id_reward']; ?>">
                                                        <h5 class="card-title">Reward #<?php echo $reward['id_reward']; ?></h5>
                                                    </label>
                                                </div>
                                                <p class="card-text"><?php echo nl2br(htmlspecialchars($reward['descrizione'])); ?></p>
                                                
                                                <?php if (!empty($reward['foto'])): ?>
                                                    <div class="text-center">
                                                        <img src="<?php echo "../" . htmlspecialchars($reward['foto']); ?>" 
                                                             class="reward-image img-fluid" 
                                                             alt="Immagine reward"
                                                             onerror="this.onerror=null; console.log('Errore caricamento immagine: ' + this.src); this.src='../uploads/placeholder.png'; this.alt='Immagine non disponibile';">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-success mt-4">Scegli questa reward</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Errore: nessun finanziamento specificato o non autorizzato.</p>
                <a href="../Autenticazione/<?php 
                    if ($_SESSION['ruolo'] === 'amministratore') {
                        echo 'home_amministratore.php';
                    } elseif ($_SESSION['ruolo'] === 'creatore') {
                        echo 'home_creatore.php';
                    } else {
                        echo 'home_utente.php';
                    }
                ?>" class="btn btn-secondary mt-2">Torna alla Home</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    function selectReward(card, rewardId) {
        // Prima rimuovi la classe "selected" da tutte le card
        document.querySelectorAll('.reward-card').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Seleziona la card corrente e il radio button
        card.classList.add('selected');
        document.getElementById('reward_' + rewardId).checked = true;
    }
    </script>
</body>
</html>