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

    // Associa i parametri solo se esistono
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
    // Se entrambi i filtri sono impostati su "tutti", mostra tutti i progetti
    $query = "SELECT * FROM progetto";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $progetti[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Filtra Progetti</title>
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
                    <p><strong>Tipo:</strong> <?= $progetto['tipo'] ?></p>
                    <p><strong>Stato:</strong> <?= $progetto['stato'] ?></p>
                    <p><strong>Descrizione:</strong> <?= $progetto['descrizione'] ?></p>
                    <p><strong>Budget:</strong> €<?= $progetto['budget'] ?></p>
                    <p><strong>Data limite:</strong> <?= $progetto['data_limite'] ?></p>
                    <hr>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php elseif ($_SERVER["REQUEST_METHOD"] === "GET" && (!empty($statoFiltro) || !empty($tipoFiltro))): ?>
        <p><strong>Nessun progetto trovato con i filtri selezionati.</strong></p>
    <?php endif; ?>
</body>
</html>
