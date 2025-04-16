<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connessione al DB
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bostarter_db';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

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
</head>
<body>
    <h2>Filtra i progetti disponibili</h2>

    <form method="GET" action="visualizza_progetto.php">
        <label for="stato">Stato progetto:</label>
        <select name="stato" required>
            <option disabled <?= !isset($_GET['stato']) ? 'selected' : '' ?>>Seleziona</option>
            <option value="tutti" <?= ($statoFiltro ?? '') === 'tutti' ? 'selected' : '' ?>>Tutti</option>
            <option value="aperto" <?= ($statoFiltro ?? '') === 'aperto' ? 'selected' : '' ?>>Aperto</option>
            <option value="chiuso" <?= ($statoFiltro ?? '') === 'chiuso' ? 'selected' : '' ?>>Chiuso</option>
        </select>

        <label for="tipo">Tipo:</label>
        <select name="tipo" required>
            <option disabled <?= !isset($_GET['tipo']) ? 'selected' : '' ?>>Seleziona</option>
            <option value="tutti" <?= ($tipoFiltro ?? '') === 'tutti' ? 'selected' : '' ?>>Tutti</option>
            <option value="hardware" <?= ($tipoFiltro ?? '') === 'hardware' ? 'selected' : '' ?>>Hardware</option>
            <option value="software" <?= ($tipoFiltro ?? '') === 'software' ? 'selected' : '' ?>>Software</option>
        </select>

        <button type="submit">Filtra</button>
    </form>

    <hr>

    <?php if (!empty($progetti)): ?>
        <h3>Progetti trovati:</h3>
        <ul>
            <?php foreach ($progetti as $progetto): ?>
                <li>
                    <h4><?= htmlspecialchars($progetto['nome']) ?></h4>
                    <p><strong>Tipo:</strong> <?= htmlspecialchars($progetto['tipo']) ?></p>
                    <p><strong>Stato:</strong> <?= htmlspecialchars($progetto['stato']) ?></p>
                    <p><strong>Descrizione:</strong> <?= htmlspecialchars($progetto['descrizione']) ?></p>
                    <p><strong>Budget:</strong> €<?= htmlspecialchars($progetto['budget']) ?></p>
                    <p><strong>Data limite:</strong> <?= htmlspecialchars($progetto['data_limite']) ?></p>
                    <hr>

                    <h4>Commenti:</h4>
                    <ul>
                        <?php if (empty($progetto['commenti'])): ?>
                            <li>Non ci sono commenti ancora.</li>
                        <?php else: ?>
                            <?php foreach ($progetto['commenti'] as $commento): ?>
                                <li>
                                    <strong><?= htmlspecialchars($commento['nickname']) ?></strong> - <?= htmlspecialchars($commento['data']) ?>
                                    <p><?= nl2br(htmlspecialchars($commento['testo'])) ?></p>

                                    <!-- Risposte -->
                                    <?php if (!empty($commento['risposte'])): ?>
                                        <ul style="margin-left: 20px;">
                                            <?php foreach ($commento['risposte'] as $risposta): ?>
                                                <li>
                                                    <strong><?= htmlspecialchars($risposta['nickname']) ?></strong> - <?= htmlspecialchars($risposta['data']) ?>
                                                    <p><?= nl2br(htmlspecialchars($risposta['testo'])) ?></p>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>

                    <h4>Aggiungi un commento</h4>
                    <form method="POST" action="visualizza_progetto.php?id_progetto=<?= $progetto['id_progetto'] ?>&stato=<?= urlencode($statoFiltro) ?>&tipo=<?= urlencode($tipoFiltro) ?>">
                        <textarea name="commento" required placeholder="Scrivi il tuo commento..."></textarea>
                        <br>
                        <button type="submit">Aggiungi commento</button>
                    </form>

                    <hr>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p><strong>Nessun progetto trovato con i filtri selezionati.</strong></p>
    <?php endif; ?>

    <a href="../Autenticazione/home_utente.php">
        <button>🏠 Torna alla Home</button>
    </a>
</body>
</html>
