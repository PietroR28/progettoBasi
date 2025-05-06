<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../mamp_xampp.php';
require_once __DIR__ . '/../mongoDB/mongodb.php';

$statoFiltro = $_GET['stato'] ?? '';
$tipoFiltro = $_GET['tipo'] ?? '';
$progetti = [];

// Inserimento commento con stored procedure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commento'], $_POST['nome_progetto'])) {
    $commento = trim($_POST['commento']);
    $nome_progetto = $_POST['nome_progetto'];
    $email_utente = $_SESSION['email_utente'] ?? '';

    if (!empty($commento) && !empty($email_utente)) {
        $stmt = $conn->prepare("CALL InserisciCommento(?, ?, ?)");
        $stmt->bind_param("sss", $commento, $nome_progetto, $email_utente);
        $stmt->execute();
        $stmt->close();

        log_event('COMMENTO_INSERITO', $email_utente, "L'utente '$email_utente' ha inserito un commento al progetto '$nome_progetto'", [
            'email_utente' => $email_utente,
            'nome_progetto' => $nome_progetto,
            'testo_commento' => $commento
        ]);

        header("Location: risposta_commento.php?stato=" . urlencode($statoFiltro) . "&tipo=" . urlencode($tipoFiltro));
        exit;
    }
}

// Filtro progetti
if (($statoFiltro !== 'tutti' && !empty($statoFiltro)) || ($tipoFiltro !== 'tutti' && !empty($tipoFiltro))) {
    $query = "SELECT * FROM progetto WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($statoFiltro) && $statoFiltro !== 'tutti') {
        $query .= " AND stato_progetto = ?";
        $params[] = $statoFiltro;
        $types .= 's';
    }

    if (!empty($tipoFiltro) && $tipoFiltro !== 'tutti') {
        $query .= " AND tipo_progetto = ?";
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
        $stmtFoto = $conn->prepare("SELECT percorso FROM foto_progetto WHERE nome_progetto = ? LIMIT 1");
        $stmtFoto->bind_param('s', $row['nome_progetto']);
        $stmtFoto->execute();
        $resFoto = $stmtFoto->get_result();
        $row['foto'] = ($foto = $resFoto->fetch_assoc()) ? $foto['percorso'] : null;
        $stmtFoto->close();

        $progetti[] = $row;
    }
    $stmt->close();
} elseif ($statoFiltro === 'tutti' && $tipoFiltro === 'tutti') {
    $result = $conn->query("SELECT * FROM progetto");
    while ($row = $result->fetch_assoc()) {
        $stmtFoto = $conn->prepare("SELECT percorso FROM foto_progetto WHERE nome_progetto = ? LIMIT 1");
        $stmtFoto->bind_param('s', $row['nome_progetto']);
        $stmtFoto->execute();
        $resFoto = $stmtFoto->get_result();
        $row['foto'] = ($foto = $resFoto->fetch_assoc()) ? $foto['percorso'] : null;
        $stmtFoto->close();

        $progetti[] = $row;
    }
}

// Recupera commenti e risposte per ogni progetto
foreach ($progetti as $index => $progetto) {
    $commenti = [];
    $stmt = $conn->prepare("SELECT c.id_commento, c.testo_commento AS testo, c.data_commento AS data, u.nickname_utente AS nickname 
                            FROM commento c 
                            JOIN utente u ON c.email_utente = u.email_utente 
                            WHERE c.nome_progetto = ? 
                            ORDER BY c.data_commento DESC");
    $stmt->bind_param('s', $progetto['nome_progetto']);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $substmt = $conn->prepare("SELECT r.testo_risposta AS testo, r.data_risposta AS data, u.nickname_utente AS nickname 
                                   FROM risposta_commento r
                                   JOIN utente u ON r.email_creatore_commento = u.email_utente
                                   WHERE r.id_commento = ?");
        $substmt->bind_param("i", $row['id_commento']);
        $substmt->execute();
        $subres = $substmt->get_result();
        if ($r = $subres->fetch_assoc()) {
            $row['risposta_creatore'] = $r;
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
    <title>Risposta ai Commenti</title>
    <link rel="stylesheet" href="../Stile/risposta_commento.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="filtro-box mb-5">
        <h2 class="mb-4">Filtra i progetti disponibili</h2>
        <form method="GET" action="risposta_commento.php" class="row g-3">
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
                    <div class="card shadow-sm p-4 mb-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-9">
                                <h4><?= htmlspecialchars($progetto['nome_progetto']) ?></h4>
                                <p><strong>Tipo:</strong> <?= htmlspecialchars($progetto['tipo_progetto']) ?></p>
                                <p><strong>Stato:</strong> <?= htmlspecialchars($progetto['stato_progetto']) ?></p>
                                <p><strong>Descrizione:</strong> <?= htmlspecialchars($progetto['descrizione_progetto']) ?></p>
                                <p><strong>Budget:</strong> €<?= htmlspecialchars($progetto['budget_progetto']) ?></p>
                                <p><strong>Data inserimento:</strong> <?= htmlspecialchars($progetto['data_inserimento_progetto']) ?></p>
                                <h5>Commenti:</h5>
                                <?php if (empty($progetto['commenti'])): ?>
                                    <p class="text-muted">Non ci sono commenti ancora.</p>
                                <?php else: ?>
                                    <ul class="list-group mb-3">
                                        <?php foreach ($progetto['commenti'] as $commento): ?>
                                            <li class="list-group-item">
                                                <strong><?= htmlspecialchars($commento['nickname']) ?></strong> - <?= htmlspecialchars($commento['data']) ?>
                                                <p><?= nl2br(htmlspecialchars($commento['testo'])) ?></p>

                                                <?php if (!empty($commento['risposta_creatore'])): ?>
                                                    <div class="bg-light border-start ps-3 mt-3 mb-2">
                                                        <strong class="text-success">
                                                            <?= htmlspecialchars($commento['risposta_creatore']['nickname']) ?> (Creatore)</strong>
                                                        <span class="text-muted"> - <?= htmlspecialchars($commento['risposta_creatore']['data']) ?></span>
                                                        <p class="mt-1">
                                                            <?= nl2br(htmlspecialchars($commento['risposta_creatore']['testo'])) ?>
                                                        </p>
                                                    </div>
                                                <?php else: ?>
                                                    <a href="rispondi_commento.php?nome_progetto=<?= urlencode($progetto['nome_progetto']) ?>&id_commento=<?= $commento['id_commento'] ?>" class="btn btn-outline-primary btn-sm mt-2">Rispondi</a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="nome_progetto" value="<?= $progetto['nome_progetto'] ?>">
                                    <div class="mb-3">
                                        <textarea name="commento" class="form-control" placeholder="Scrivi un nuovo commento..." required></textarea>
                                    </div>
                                    <button class="btn btn-danger">Aggiungi commento</button>
                                </form>
                            </div>

                            <div class="col-md-3 text-center">
                                <?php if (!empty($progetto['foto'])): ?>
                                    <img src="../<?= htmlspecialchars($progetto['foto']) ?>" alt="Foto Progetto" class="img-fluid rounded" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="../uploads/placeholder.png" alt="Nessuna Immagine" class="img-fluid rounded" style="max-width: 200px; max-height: 200px; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-danger"><strong>Nessun progetto trovato con i filtri selezionati.</strong></p>
    <?php endif; ?>

    <div class="text-center mt-5">
        <a href="../Autenticazione/home_creatore.php" class="btn btn-success">Torna alla Home</a>
    </div>