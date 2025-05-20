<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    header('Location: login.php');
    exit();
}

// Connessione al DB
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$messaggio = "";
$tipoMessaggio = "success";

// Gestione POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'], $_POST['valutazione'], $_POST['commento'])) {
    $type           = $_POST['type'];            // "campo" o "calcetto"
    $valutazione    = (int)$_POST['valutazione'];
    $commento       = $conn->real_escape_string($_POST['commento']);
    $id_utente      = (int)$_SESSION['id_utente'];
    $data_rec       = date('Y-m-d');

    // Determino i campi
    $id_campo    = $type === 'campo'    ? (int)$_POST['id_campo']    : 'NULL';
    $id_calcetto = $type === 'calcetto' ? (int)$_POST['id_calcetto'] : 'NULL';

    $sql = "INSERT INTO recensioni
            (valutazione, commento, data_recensione, id_utente, id_campo, id_calcetto)
            VALUES
            ($valutazione, '$commento', '$data_rec', $id_utente, $id_campo, $id_calcetto)";

    if ($conn->query($sql)) {
        $messaggio = "Recensione salvata con successo!";
        $tipoMessaggio = "success";
    } else {
        $messaggio = "Errore: " . $conn->error;
        $tipoMessaggio = "danger";
    }
}

// Recupero liste per i select
$campi    = $conn->query("SELECT id_campo, indirizzo FROM campi ORDER BY indirizzo ASC");
$calcetti = $conn->query("
    SELECT c.id_calcetto, ca.indirizzo, c.data_ora
    FROM calcetti c
    JOIN campi ca ON c.id_campo = ca.id_campo
    ORDER BY c.data_ora ASC
");

// Recupero tutte le recensioni
$recRes = $conn->query("
    SELECT r.*, u.nome, u.cognome,
           c.indirizzo AS campo_indirizzo,
           CONCAT(ca.indirizzo, ' - ', DATE_FORMAT(cal.data_ora,'%d/%m/%Y %H:%i')) AS calcetto_descr
    FROM recensioni r
    JOIN utenti u ON r.id_utente = u.id_utente
    LEFT JOIN campi c ON r.id_campo = c.id_campo
    LEFT JOIN calcetti cal ON r.id_calcetto = cal.id_calcetto
    LEFT JOIN campi ca ON cal.id_campo = ca.id_campo
    ORDER BY r.data_recensione DESC
");

$conn->close();

// Funzione per generare le stelle
function generaStelle($valutazione) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $valutazione) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recensioni - Sportify</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --secondary-color: #1976D2;
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: var(--dark-text);
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            letter-spacing: 1px;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-bottom: none;
        }
        
        .section-title {
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: var(--secondary-color);
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25);
        }
        
        .table {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #e9ecef;
            border-top: none;
            font-weight: 600;
        }
        
        .recensione-card {
            transition: transform 0.2s;
        }
        
        .recensione-card:hover {
            transform: translateY(-3px);
        }
        
        .tipo-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        textarea {
            min-height: 120px;
        }
        
        /* Animazione messaggio */
        .alert-fade {
            animation: fadeOut 5s forwards;
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-futbol me-2"></i>Sportify
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Messaggio di conferma -->
        <?php if ($messaggio): ?>
        <div class="alert alert-<?= $tipoMessaggio ?> alert-dismissible fade show alert-fade" role="alert">
            <?= $messaggio ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <h1 class="text-center mb-4">Recensioni</h1>
        
        <div class="row">
            <!-- Recensisci un Campo -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <span>Recensisci un Campo</span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="type" value="campo">
                            <div class="mb-3">
                                <label for="id_campo" class="form-label">Seleziona Campo:</label>
                                <select id="id_campo" name="id_campo" class="form-select" required>
                                    <option value="">-- Scegli --</option>
                                    <?php while ($r = $campi->fetch_assoc()): ?>
                                        <option value="<?= $r['id_campo'] ?>">
                                            <?= htmlspecialchars($r['indirizzo']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="valutazione" class="form-label">Valutazione:</label>
                                <div class="rating-select">
                                    <select id="valutazione" name="valutazione" class="form-select" required>
                                        <option value="">-- Scegli --</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>">
                                                <?= $i ?> <?= $i===1 ? 'stella' : 'stelle' ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="commento" class="form-label">Commento:</label>
                                <textarea id="commento" name="commento" class="form-control" required></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Invia Recensione
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Recensisci un Calcetto -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-users me-2"></i>
                        <span>Recensisci un Calcetto</span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="type" value="calcetto">
                            <div class="mb-3">
                                <label for="id_calcetto" class="form-label">Seleziona Calcetto:</label>
                                <select id="id_calcetto" name="id_calcetto" class="form-select" required>
                                    <option value="">-- Scegli --</option>
                                    <?php while ($r = $calcetti->fetch_assoc()): ?>
                                        <option value="<?= $r['id_calcetto'] ?>">
                                            <?= htmlspecialchars($r['indirizzo']) ?> —
                                            <?= date('d/m/Y H:i', strtotime($r['data_ora'])) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="valutazione2" class="form-label">Valutazione:</label>
                                <div class="rating-select">
                                    <select id="valutazione2" name="valutazione" class="form-select" required>
                                        <option value="">-- Scegli --</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>">
                                                <?= $i ?> <?= $i===1 ? 'stella' : 'stelle' ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="commento2" class="form-label">Commento:</label>
                                <textarea id="commento2" name="commento" class="form-control" required></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Invia Recensione
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ELENCO RECENSIONI -->
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white d-flex align-items-center">
                <i class="fas fa-comments me-2"></i>
                <span>Tutte le Recensioni</span>
            </div>
            <div class="card-body p-0">
                <?php if ($recRes->num_rows): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Target</th>
                                    <th>Utente</th>
                                    <th>Valutazione</th>
                                    <th>Commento</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $recRes->fetch_assoc()): ?>
                                    <tr class="recensione-card">
                                        <td>
                                            <?php if ($r['id_campo']): ?>
                                                <span class="badge bg-info tipo-badge">
                                                    <i class="fas fa-map-marker-alt me-1"></i> Campo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success tipo-badge">
                                                    <i class="fas fa-users me-1"></i> Calcetto
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $r['id_campo']
                                                ? htmlspecialchars($r['campo_indirizzo'])
                                                : htmlspecialchars($r['calcetto_descr']);
                                            ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-user-circle me-1"></i>
                                            <?= htmlspecialchars($r['nome'] . ' ' . $r['cognome']) ?>
                                        </td>
                                        <td class="text-nowrap">
                                            <?= generaStelle($r['valutazione']) ?>
                                        </td>
                                        <td><?= nl2br(htmlspecialchars($r['commento'])) ?></td>
                                        <td>
                                            <i class="far fa-calendar-alt me-1"></i>
                                            <?= date('d/m/Y', strtotime($r['data_recensione'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center">
                        <i class="far fa-comment-dots fa-3x mb-3 text-muted"></i>
                        <p class="mb-0">Nessuna recensione disponibile.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Sportify - Tutti i diritti riservati</p>
        </div>
    </footer>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>