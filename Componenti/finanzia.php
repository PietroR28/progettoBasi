<?php
// Inizializza la sessione
session_start();

// Verifica se l'utente è loggato, altrimenti reindirizza al login
if (!isset($_SESSION['id_utente'])) {
    header("Location: ../Autenticazione/login.php");
    exit;
}

$message = "";
$id_progetto = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_finanziamento = 0;
$progetto_exists = false;
$progetto_info = [];
$progetti_disponibili = [];

// Connessione al database - crea una nuova connessione per ogni operazione
function getConnection() {
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "bostarter_db";
    
    $conn = new mysqli($host, $user, $password, $database);
    if ($conn->connect_error) {
        die("Errore di connessione: " . $conn->connect_error);
    }
    return $conn;
}

// Recupera i progetti disponibili usando la SP
if (!$id_progetto) {
    $connessione = getConnection();
    try {
        // Chiamata alla stored procedure per ottenere i progetti disponibili
        $stmt = $connessione->prepare("CALL VisualizzaProgettiDisponibili()");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $progetti_disponibili[] = $row;
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $message = "Errore durante il recupero dei progetti: " . $e->getMessage();
    }
    $connessione->close();
}

// Verifica l'esistenza del progetto e recupera informazioni importanti
if ($id_progetto > 0) {
    $connessione = getConnection();
    $stmt = $connessione->prepare("SELECT p.id_progetto, p.nome, p.budget, p.stato, p.data_limite, p.descrizione 
                                  FROM progetto p
                                  WHERE p.id_progetto = ? AND p.stato = 'aperto'");
    $stmt->bind_param("i", $id_progetto);
    $stmt->execute();
    $result = $stmt->get_result();
    $progetto_exists = ($result->num_rows > 0);
    
    if ($progetto_exists) {
        $progetto_info = $result->fetch_assoc();
    } else {
        $message = "Errore: Il progetto selezionato non esiste o non è aperto per finanziamenti.";
    }
    
    $stmt->close();
    $connessione->close();
}

// Gestione del finanziamento
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['importo']) && $id_progetto > 0) {
    $importo = floatval(str_replace(',', '.', $_POST['importo']));
    $id_utente = $_SESSION['id_utente'];
    
    try {
        // Prima connessione per eseguire il finanziamento
        $conn1 = getConnection();
        
        // Chiamata alla stored procedure per inserire il finanziamento
        $stmt = $conn1->prepare("CALL InserisciFinanziamento(?, ?, ?)");
        $stmt->bind_param("iid", $id_utente, $id_progetto, $importo);
        
        if ($stmt->execute()) {
            // Chiudi la connessione precedente e crea una nuova connessione per la query successiva
            $stmt->close();
            $conn1->close();
            
            // Nuova connessione per ottenere l'ID del finanziamento
            $conn2 = getConnection();
            $query = "SELECT MAX(id_finanziamento) as id FROM finanziamento 
                      WHERE id_utente = ? AND id_progetto = ? 
                      ORDER BY data DESC LIMIT 1";
            $stmt_id = $conn2->prepare($query);
            $stmt_id->bind_param("ii", $id_utente, $id_progetto);
            $stmt_id->execute();
            $result_id = $stmt_id->get_result();
            
            if ($result_id->num_rows > 0) {
                $row = $result_id->fetch_assoc();
                $id_finanziamento = $row['id'];
                
                // Chiudi risorse prima di reindirizzare
                $stmt_id->close();
                $conn2->close();
                
                // Reindirizza alla pagina di selezione reward
                header("Location: scelta_reward.php?id_finanziamento=$id_finanziamento&id_progetto=$id_progetto");
                exit;
            } else {
                $message = "Finanziamento registrato, ma non è stato possibile recuperare l'ID.";
                $stmt_id->close();
                $conn2->close();
            }
        } else {
            $message = "Errore durante l'inserimento del finanziamento: " . $stmt->error;
            $stmt->close();
            $conn1->close();
        }
    } catch (Exception $e) {
        $message = "Errore: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanzia Progetto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Finanzia Progetto</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo (strpos($message, 'Errore') === 0) ? 'alert-danger' : 'alert-success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$id_progetto): ?>
            <div class="mb-4">
                <h2>Progetti disponibili</h2>
                <p>Seleziona un progetto da finanziare:</p>
            </div>
            
            <?php if (empty($progetti_disponibili)): ?>
                <div class="alert alert-info">Non ci sono progetti disponibili per il finanziamento al momento.</div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titolo</th>
                            <th>Descrizione</th>
                            <th>Data</th>
                            <th>Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($progetti_disponibili as $progetto): ?>
                            <tr>
                                <td><?php echo $progetto['id_progetto']; ?></td>
                                <td><?php echo htmlspecialchars($progetto['nome']); ?></td>
                                <td><?php echo htmlspecialchars(substr($progetto['descrizione'], 0, 100)) . (strlen($progetto['descrizione']) > 100 ? '...' : ''); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($progetto['data_inserimento'])); ?></td>
                                <td>
                                    <a href="finanzia.php?id=<?php echo $progetto['id_progetto']; ?>" class="btn btn-sm btn-primary">Seleziona</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php elseif ($progetto_exists): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($progetto_info['nome']); ?></h3>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <p><strong>Descrizione:</strong> <?php echo nl2br(htmlspecialchars($progetto_info['descrizione'])); ?></p>
                            <p>
                                <strong>Budget richiesto:</strong> €<?php echo number_format($progetto_info['budget'], 2, ',', '.'); ?> | 
                                <strong>Stato:</strong> <?php echo htmlspecialchars($progetto_info['stato']); ?> | 
                                <strong>Data limite:</strong> <?php echo htmlspecialchars($progetto_info['data_limite']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Form per finanziare un progetto -->
                    <form method="post" class="mt-3">
                        <div class="mb-3">
                            <label for="importo" class="form-label">Importo da finanziare (€):</label>
                            <input type="number" class="form-control" id="importo" name="importo" step="0.01" min="1" required>
                        </div>
                        <div class="d-flex">
                            <a href="finanzia.php" class="btn btn-secondary me-2">Torna alla lista</a>
                            <button type="submit" class="btn btn-primary">Finanzia</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Progetto non trovato o non disponibile per il finanziamento.</div>
            <a href="finanzia.php" class="btn btn-primary">Torna alla lista dei progetti</a>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="../Autenticazione/<?php 
                if ($_SESSION['ruolo'] === 'amministratore') {
                    echo 'home_amministratore.php';
                } elseif ($_SESSION['ruolo'] === 'creatore') {
                    echo 'home_creatore.php';
                } else {
                    echo 'home_utente.php';
                }
            ?>" class="btn btn-secondary">Torna alla Home</a>
        </div>
    </div>
</body>
</html>