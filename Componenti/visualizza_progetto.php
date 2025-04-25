<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connessione al DB
require_once __DIR__ . '/../mamp_xampp.php';

$statoFiltro = $_GET['stato'] ?? '';
$tipoFiltro = $_GET['tipo'] ?? '';
$progetti = [];

// Filtro progetti
if (($statoFiltro !== 'tutti' && !empty($statoFiltro)) || ($tipoFiltro !== 'tutti' && !empty($tipoFiltro))) {
    $query = "SELECT * FROM progetto WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($statoFiltro) && $statoFiltro !== 'tutti') {
        $query .= " AND stato = ?";
        $params[] = $statoFiltro;
        $types .= 's';
    }

    if (!empty($tipoFiltro) && $tipoFiltro !== 'tutti') {
        $query .= " AND tipo = ?";
        $params[] = $tipoFiltro;
        $types .= 's';
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $progetti[] = $row;
    }
    $stmt->close();
} elseif ($statoFiltro === 'tutti' && $tipoFiltro === 'tutti') {
    $result = $conn->query("SELECT * FROM progetto");
    while ($row = $result->fetch_assoc()) {
        $progetti[] = $row;
    }
}

// Inserimento commenti (solo commenti principali)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commento'], $_GET['id_progetto'])) {
    $id_progetto = (int)$_GET['id_progetto'];
    $commento = trim($_POST['commento']);

    if (isset($_SESSION['id_utente']) && !empty($_SESSION['id_utente'])) {
        $id_utente = (int)$_SESSION['id_utente'];

        $stmt = $conn->prepare("INSERT INTO commento (testo, id_progetto, id_utente, data, id_commento_padre) VALUES (?, ?, ?, NOW(), NULL)");
        $stmt->bind_param('sii', $commento, $id_progetto, $id_utente);

        if ($stmt->execute()) {
            $id_commento = $stmt->insert_id;
            require_once __DIR__ . '/../mongoDB/mongodb.php';

            log_event(
                'COMMENTO_INSERITO',
                $_SESSION['email'],
                "L'utente '{$_SESSION['email']}' ha inserito un commento al progetto ID $id_progetto.",
                [
                    'id_progetto' => $id_progetto,
                    'id_utente' => $_SESSION['id_utente'],
                    'id_commento' => $id_commento,
                    'testo_commento' => $commento
                ]
            );
            
            header("Location: visualizza_progetto.php?stato=" . urlencode($statoFiltro) . "&tipo=" . urlencode($tipoFiltro));
            exit;
        } else {
            die("Errore SQL durante inserimento commento: " . $stmt->error);
        }
        $stmt->close();
    } else {
        die("Non hai effettuato l'accesso.");
    }
}

// Recupera commenti e risposte per ogni progetto
foreach ($progetti as $index => $progetto) {
    $commenti = [];

    $stmt = $conn->prepare("SELECT c.id_commento, c.testo, c.data, u.nickname 
                            FROM commento c 
                            JOIN utente u ON c.id_utente = u.id_utente 
                            WHERE c.id_progetto = ? AND c.id_commento_padre IS NULL 
                            ORDER BY c.data DESC");
    $stmt->bind_param('i', $progetto['id_progetto']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['risposte'] = [];

        $substmt = $conn->prepare("SELECT c.testo, c.data, u.nickname 
                                   FROM commento c 
                                   JOIN utente u ON c.id_utente = u.id_utente 
                                   WHERE c.id_commento_padre = ? 
                                   ORDER BY c.data ASC");
        $substmt->bind_param("i", $row['id_commento']);
        $substmt->execute();
        $subres = $substmt->get_result();

        while ($r = $subres->fetch_assoc()) {
            $row['risposte'][] = $r;
        }
        $substmt->close();

        $commenti[] = $row;
    }

    $stmt->close();
    $progetti[$index]['commenti'] = $commenti;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Visualizza Progetti</title>
    <link rel="stylesheet" href="../Stile/risposta_commento.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="filtro-box mb-5">
        <h2 class="mb-4">Filtra i progetti disponibili</h2>

        <form method="GET" action="visualizza_progetto.php" class="row g-3">
            <div class="col-md-6">
                <label for="stato" class="form-label">Stato progetto</label>
                <select name="stato" class="form-select" required>
                    <option disabled <?= !isset($_GET['stato']) ? 'selected' : '' ?>>Seleziona</option>
                    <option value="tutti" <?= ($statoFiltro ?? '') === 'tutti' ? 'selected' : '' ?>>Tutti</option>
                    <option value="aperto" <?= ($statoFiltro ?? '') === 'aperto' ? 'selected' : '' ?>>Aperto</option>
                    <option value="chiuso" <?= ($statoFiltro ?? '') === 'chiuso' ? 'selected' : '' ?>>Chiuso</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="tipo" class="form-label">Tipo</label>
                <select name="tipo" class="form-select" required>
                    <option disabled <?= !isset($_GET['tipo']) ? 'selected' : '' ?>>Seleziona</option>
                    <option value="tutti" <?= ($tipoFiltro ?? '') === 'tutti' ? 'selected' : '' ?>>Tutti</option>
                    <option value="hardware" <?= ($tipoFiltro ?? '') === 'hardware' ? 'selected' : '' ?>>Hardware</option>
                    <option value="software" <?= ($tipoFiltro ?? '') === 'software' ? 'selected' : '' ?>>Software</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success">Filtra</button>
            </div>
        </form>
    </div>

    <?php if (!empty($progetti)): ?>
        <h3 class="mb-4">Progetti trovati:</h3>
        <div class="row row-cols-1 g-4">
            <?php foreach ($progetti as $progetto): ?>
                <div class="col">
                    <div class="card shadow-sm p-4">
                        <h4><?= htmlspecialchars($progetto['nome']) ?></h4>
                        <p><strong>Tipo:</strong> <?= htmlspecialchars($progetto['tipo']) ?></p>
                        <p><strong>Stato:</strong> <?= htmlspecialchars($progetto['stato']) ?></p>
                        <p><strong>Descrizione:</strong> <?= htmlspecialchars($progetto['descrizione']) ?></p>
                        <p><strong>Budget:</strong> €<?= htmlspecialchars($progetto['budget']) ?></p>
                        <p><strong>Data limite:</strong> <?= htmlspecialchars($progetto['data_limite']) ?></p>

                        <hr>

                        <h5>Commenti:</h5>
                        <?php if (empty($progetto['commenti'])): ?>
                            <p class="text-muted">Non ci sono commenti ancora.</p>
                        <?php else: ?>
                            <ul class="list-group mb-3">
                                <?php foreach ($progetto['commenti'] as $commento): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($commento['nickname']) ?></strong> - <?= htmlspecialchars($commento['data']) ?>
                                        <p><?= nl2br(htmlspecialchars($commento['testo'])) ?></p>

                                        <?php if (!empty($commento['risposte'])): ?>
                                            <ul class="list-group list-group-flush ms-3">
                                                <?php foreach ($commento['risposte'] as $risposta): ?>
                                                    <li class="list-group-item">
                                                        <strong><?= htmlspecialchars($risposta['nickname']) ?></strong> - <?= htmlspecialchars($risposta['data']) ?>
                                                        <p><?= nl2br(htmlspecialchars($risposta['testo'])) ?></p>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Aggiunta commento -->
                        <form method="POST" action="visualizza_progetto.php?id_progetto=<?= $progetto['id_progetto'] ?>&stato=<?= urlencode($statoFiltro) ?>&tipo=<?= urlencode($tipoFiltro) ?>">
                            <div class="mb-3">
                                <textarea name="commento" class="form-control" required placeholder="Scrivi il tuo commento..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger">Aggiungi commento</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-danger"><strong>Nessun progetto trovato con i filtri selezionati.</strong></p>
    <?php endif; ?>

    <div class="text-center mt-5 home-button-container">
        <a href="../Autenticazione/home_utente.php" class="btn btn-success">
            Torna alla Home
        </a>
    </div>
</div>
</body>
</html>

