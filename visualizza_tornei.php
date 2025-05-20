<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$messaggio = "";

// Creazione squadra con controllo su max_squadre
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['unisciti_squadra'])) {
    $id_torneo = (int)$_POST['id_torneo'];
    $id_utente = $_SESSION['id_utente'];
    $nome_squadra = mysqli_real_escape_string($conn, $_POST['nome_squadra']);

    // 1. Verifica se ha già creato una squadra in questo torneo
    $verifica = $conn->query("SELECT * FROM squadre WHERE id_torneo = $id_torneo AND id_creatore = $id_utente");
    if ($verifica->num_rows > 0) {
        $messaggio = "Hai già creato una squadra per questo torneo.";
    } else {
        // 2. Verifica max squadre
        $check_sql = "
            SELECT 
                (SELECT COUNT(*) FROM squadre WHERE id_torneo = $id_torneo) AS squadre_attuali,
                t.max_squadre
            FROM tornei t
            WHERE t.Id_torneo = $id_torneo
        ";
        $res_check = $conn->query($check_sql);
        $row = $res_check->fetch_assoc();

        if ($row['squadre_attuali'] >= $row['max_squadre']) {
            $messaggio = "Il numero massimo di squadre per questo torneo è stato raggiunto.";
        } else {
            // 3. Crea squadra
// Recupera tipologia del torneo
$res_tip = $conn->query("SELECT tipologia FROM tornei WHERE Id_torneo = $id_torneo");
$tipologia_torneo = $res_tip->fetch_assoc()['tipologia'] ?? 'Girone Unico';

// Assegna girone se necessario
if ($tipologia_torneo === "Gironi Multipli") {
    $max_per_girone = 4;

// Recupera numero di squadre per girone
$res_gironi = $conn->query("
    SELECT girone, COUNT(*) AS tot 
    FROM squadre 
    WHERE id_torneo = $id_torneo AND girone IS NOT NULL 
    GROUP BY girone
");

$gironi_count = [];
while ($row = $res_gironi->fetch_assoc()) {
    $gironi_count[$row['girone']] = $row['tot'];
}

// Trova il primo girone disponibile con meno di $max_per_girone squadre
$girone = null;
for ($i = 0; $i < 26; $i++) {
    $letter = chr(65 + $i); // A, B, C, ...
    if (!isset($gironi_count[$letter]) || $gironi_count[$letter] < $max_per_girone) {
        $girone = $letter;
        break;
    }
}

// Fallback di sicurezza
if (!$girone) {
    $girone = 'Z'; // Tutto pieno, metti in Z temporaneamente
}

} else {
    $girone = null;
}
$conn->begin_transaction();
try {
$sqlSquadra = "INSERT INTO squadre (nome, id_torneo, id_creatore, girone) 
               VALUES ('$nome_squadra', '$id_torneo', '$id_utente', " . ($girone ? "'$girone'" : "NULL") . ")";
            if ($conn->query($sqlSquadra) === TRUE) {
                $id_squadra = $conn->insert_id;
                $sqlUtenteSquadra = "INSERT INTO utente_squadra (id_utente, id_squadra) VALUES ('$id_utente', '$id_squadra')";
                if ($conn->query($sqlUtenteSquadra) === TRUE) {
                    $messaggio = "Sei stato aggiunto alla squadra con successo!";
                } else {
                    $messaggio = "Errore durante l'iscrizione alla squadra: " . $conn->error;
                }
            } else {
                $messaggio = "Errore durante la creazione della squadra: " . $conn->error;
            }     $conn->commit();

        } catch (Exception $e) {
    $conn->rollback();
}
        }
    }
}


// Recupera tornei e campi associati
$sqlTornei = "SELECT t.id_torneo, t.nome, t.data_inizio, t.data_fine, t.tipologia, t.note, t.certificato_medico, t.docce, t.ora_inizio, t.ora_fine, t.max_squadre, c.indirizzo 
              FROM tornei t
              JOIN campo_torneo ct ON t.id_torneo = ct.id_torneo
              JOIN campi c ON ct.id_campo = c.id_campo";

$resultTornei = $conn->query($sqlTornei);

$tornei = [];
while ($row = $resultTornei->fetch_assoc()) {
    $id = $row['id_torneo'];
    if (!isset($tornei[$id])) {
        $tornei[$id] = [
            'nome' => $row['nome'],
            'data_inizio' => $row['data_inizio'],
            'data_fine' => $row['data_fine'],
            'tipologia' => $row['tipologia'],
            'note' => $row['note'],
            'certificato_medico' => $row['certificato_medico'],
            'docce' => $row['docce'],
            'ora_inizio' => $row['ora_inizio'],
            'ora_fine' => $row['ora_fine'],
            'max_squadre' => $row['max_squadre'],
            'campi' => [],
        ];
    }
    $tornei[$id]['campi'][] = $row['indirizzo'];
}
$conn->close();

// Squadre create dall'utente
$conn2 = new mysqli($servername, $username, $password, $dbname);
$squadre_utente = [];
$res = $conn2->query("SELECT id_torneo FROM squadre WHERE id_creatore = " . $_SESSION['id_utente']);
while ($row = $res->fetch_assoc()) {
    $squadre_utente[] = $row['id_torneo'];
}
$conn2->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Tornei - Sportify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2196F3;
            --primary-dark: #1976D2;
            --accent-color: #FF9800;
            --light-bg: #f5f9ff;
            --dark-text: #333333;
            --light-text: #ffffff;
            --success: #4CAF50;
            --danger: #F44336;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
        }
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        min-height: 100vh;
        padding: 20px 0;
        color: var(--dark-text);
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        background-color: white;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eaeaea;
    }

    .header h1 {
        font-size: 28px;
        color: var(--primary-dark);
        margin: 0;
    }

    .nav-buttons {
        display: flex;
        gap: 10px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        background-color: var(--primary-color);
        color: var(--light-text);
        text-decoration: none;
        border-radius: var(--border-radius);
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .btn:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-logout {
        background-color: var(--danger);
    }

    .btn-logout:hover {
        background-color: #d32f2f;
    }

    .btn-accent {
        background-color: var(--accent-color);
    }

    .btn-accent:hover {
        background-color: #FB8C00;
    }

    .btn-success {
        background-color: var(--success);
    }

    .btn-success:hover {
        background-color: #388E3C;
    }

    .message {
        text-align: center;
        margin: 20px 0;
        padding: 12px;
        border-radius: var(--border-radius);
        background-color: #e8f5e9;
        color: #2e7d32;
        font-weight: 500;
        box-shadow: var(--shadow);
    }

    .tornei-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
    }

    .torneo-card {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        transition: transform 0.3s ease;
        border: 1px solid #e0e0e0;
    }

    .torneo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
    }

    .torneo-header {
        background-color: var(--primary-color);
        padding: 15px;
        color: white;
    }

    .torneo-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .torneo-content {
        padding: 15px;
    }

    .torneo-dates {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .torneo-date {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .torneo-action {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .info-toggle {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        background-color: #e3f2fd;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: var(--primary-dark);
        margin-bottom: 15px;
        transition: background-color 0.2s ease;
    }

    .info-toggle:hover {
        background-color: #bbdefb;
    }

    .info-extra {
        display: none;
        margin: 15px 0;
        padding: 15px;
        background-color: var(--light-bg);
        border-radius: var(--border-radius);
        border-left: 4px solid var(--primary-color);
        font-size: 14px;
    }

    .info-extra p {
        margin-bottom: 8px;
    }

    .info-extra ul {
        padding-left: 20px;
        margin-bottom: 8px;
    }

    .create-team-form {
        margin-top: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .form-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-control {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        font-family: inherit;
        font-size: 14px;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 15px;
    }

    .progress-container {
        margin-top: 15px;
        width: 100%;
        background-color: #e0e0e0;
        border-radius: 4px;
        height: 8px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background-color: var(--accent-color);
        border-radius: 4px;
    }

    .squadre-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        margin-top: 5px;
    }

    .divider {
        height: 1px;
        background-color: #eaeaea;
        margin: 15px 0;
    }

    /* Stile per la barra di ricerca */
    .search-container {
        margin-bottom: 20px;
        display: flex;
        gap: 10px;
    }
    
    .search-container input {
        flex-grow: 1;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: var(--border-radius);
        font-size: 14px;
        box-shadow: var(--shadow);
    }
    
    .search-container input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
    }
    
    .search-container button {
        padding: 12px 15px;
        background-color: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .search-container button:hover {
        background-color: var(--primary-dark);
    }
    
    .no-results {
        text-align: center;
        padding: 20px;
        background-color: var(--light-bg);
        border-radius: var(--border-radius);
        font-weight: 500;
        color: var(--dark-text);
        grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
        .container {
            width: 95%;
            padding: 20px;
        }

        .header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .tornei-container {
            grid-template-columns: 1fr;
        }

        .torneo-action {
            flex-direction: column;
        }

        .form-group {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-container {
            flex-direction: column;
        }
    }
</style>
<script>
    function toggleInfo(id) {
        const section = document.getElementById('info-' + id);
        const btn = document.getElementById('toggle-btn-' + id);
        if (section.style.display === 'block') {
            section.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-chevron-down"></i> Mostra Info';
        } else {
            section.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-chevron-up"></i> Nascondi Info';
        }
    }
    
    function searchTournaments() {
        const searchValue = document.getElementById('search-input').value.toLowerCase();
        const torneiCards = document.querySelectorAll('.torneo-card');
        let visibleCount = 0;
        
        torneiCards.forEach(card => {
            const torneoName = card.querySelector('.torneo-header h3').textContent.toLowerCase();
            if (torneoName.includes(searchValue)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Mostra messaggio se non ci sono risultati
        const noResultsMsg = document.getElementById('no-results');
        if (visibleCount === 0) {
            if (!noResultsMsg) {
                const msg = document.createElement('div');
                msg.id = 'no-results';
                msg.className = 'no-results';
                msg.innerHTML = '<i class="fas fa-search"></i> Nessun torneo trovato con questo nome';
                document.querySelector('.tornei-container').appendChild(msg);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    }
</script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-trophy"></i> Tornei Disponibili</h1>
        <div class="nav-buttons">
            <a href="index.php" class="btn"><i class="fas fa-home"></i> Home</a>
            <a href="logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
<?php if (!empty($messaggio)): ?>
    <div class="message">
        <i class="fas fa-info-circle"></i> <?= $messaggio ?>
    </div>
<?php endif; ?>

<!-- Barra di ricerca -->
<div class="search-container">
    <input type="text" id="search-input" placeholder="Cerca torneo per nome..." oninput="searchTournaments()">
    <button type="button" onclick="searchTournaments()"><i class="fas fa-search"></i> Cerca</button>
</div>

<div class="tornei-container">
    <?php foreach ($tornei as $id_torneo => $torneo): ?>
        <?php
        $conn_temp = new mysqli($servername, $username, $password, $dbname);
        $count_sql = "SELECT COUNT(*) as tot FROM squadre WHERE id_torneo = $id_torneo";
        $res = $conn_temp->query($count_sql);
        $row = $res->fetch_assoc();
        $tot_squadre = $row['tot'];
        $percentuale = ($tot_squadre / $torneo['max_squadre']) * 100;
        $conn_temp->close();

        $conn3 = new mysqli($servername, $username, $password, $dbname);
        $check_partite = $conn3->query("SELECT 1 FROM partite WHERE id_torneo = $id_torneo LIMIT 1");
        $calendario_esiste = $check_partite->num_rows > 0;
        $conn3->close();
        // Verifica se è torneo a gironi multipli e se la fase finale è già stata generata
$fase_finale_esiste = false;
if ($torneo['tipologia'] === "Gironi Multipli") {
    $conn4 = new mysqli($servername, $username, $password, $dbname);
    $res_finale = $conn4->query("SELECT 1 FROM partite WHERE id_torneo = $id_torneo AND fase_finale = 1 LIMIT 1");
    $fase_finale_esiste = $res_finale->num_rows > 0;
    $conn4->close();
}

        ?>
        <div class="torneo-card">
            <div class="torneo-header">
                <h3><?= htmlspecialchars($torneo['nome']) ?></h3>
            </div>
            
            <div class="torneo-content">
                <div class="torneo-dates">
                    <div class="torneo-date">
                        <i class="fas fa-calendar-alt"></i> Inizio: <?= $torneo['data_inizio'] ?>
                    </div>
                    <div class="torneo-date">
                        <i class="fas fa-calendar-check"></i> Fine: <?= $torneo['data_fine'] ?>
                    </div>
                </div>
                
                <button id="toggle-btn-<?= $id_torneo ?>" class="info-toggle" onclick="toggleInfo(<?= $id_torneo ?>)">
                    <i class="fas fa-chevron-down"></i> Mostra Info
                </button>

                <div class="info-extra" id="info-<?= $id_torneo ?>">
                    <p><i class="fas fa-tag"></i> <strong>Tipologia:</strong> <?= htmlspecialchars($torneo['tipologia']) ?></p>
                    <p><i class="fas fa-sticky-note"></i> <strong>Note:</strong> <?= htmlspecialchars($torneo['note']) ?></p>
                    <p><i class="fas fa-clock"></i> <strong>Orario:</strong> <?= htmlspecialchars($torneo['ora_inizio']) ?> - <?= htmlspecialchars($torneo['ora_fine']) ?></p>
                    <p><i class="fas fa-file-medical"></i> <strong>Certificato medico:</strong> <?= $torneo['certificato_medico'] ? 'Richiesto' : 'Non richiesto' ?></p>
                    <p><i class="fas fa-shower"></i> <strong>Docce disponibili:</strong> <?= $torneo['docce'] ? 'Sì' : 'No' ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Campi associati:</strong></p>
                    <ul>
                        <?php foreach ($torneo['campi'] as $campo): ?>
                            <li><?= htmlspecialchars($campo) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= $percentuale ?>%;"></div>
                </div>
                <div class="squadre-info">
                    <span><i class="fas fa-users"></i> Squadre iscritte:</span>
                    <span><strong><?= $tot_squadre ?></strong> / <?= $torneo['max_squadre'] ?></span>
                </div>

                <div class="divider"></div>

                <form class="create-team-form" action="visualizza_tornei.php" method="POST">
                    <input type="hidden" name="id_torneo" value="<?= $id_torneo ?>">
                    <div class="form-group">
                        <input type="text" class="form-control" name="nome_squadra" placeholder="Nome della squadra" required>
                        <button type="submit" name="unisciti_squadra" class="btn">
                            <i class="fas fa-plus-circle"></i> Crea squadra
                        </button>
                    </div>
                </form>

                <div class="action-buttons">
    <a href="visualizza_squadre.php?id_torneo=<?= $id_torneo ?>" class="btn btn-accent">
        <i class="fas fa-user-plus"></i> Unisciti a una squadra
    </a>

    <?php if (in_array($id_torneo, $squadre_utente)): ?>
        <a href="gestisci_richieste.php?id_torneo=<?= $id_torneo ?>" class="btn">
            <i class="fas fa-tasks"></i> Gestisci Richieste
        </a>
    <?php endif; ?>

    <?php if (!$calendario_esiste && in_array($id_torneo, $squadre_utente)): ?>
        <?php if ($torneo['tipologia'] === "Eliminazione Diretta"): ?>
            <a href="genera_tabellone.php?id_torneo=<?= $id_torneo ?>" class="btn btn-success">
                <i class="fas fa-sitemap"></i> Genera Tabellone
            </a>
        <?php else: ?>
            <a href="genera_calendario.php?id_torneo=<?= $id_torneo ?>" class="btn btn-success">
                <i class="fas fa-calendar-plus"></i> Genera Calendario
            </a>
        <?php endif; ?>
    <?php elseif ($calendario_esiste): ?>
        <a href="mostra_calendario.php?id_torneo=<?= $id_torneo ?>" class="btn">
            <i class="fas fa-calendar-alt"></i> Mostra Calendario
        </a>

        <?php if ($torneo['tipologia'] === "Eliminazione Diretta"): ?>
            <a href="mostra_tabellone.php?id_torneo=<?= $id_torneo ?>" class="btn">
                <i class="fas fa-sitemap"></i> Mostra Tabellone
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <a href="mostra_classifica.php?id_torneo=<?= $id_torneo ?>" class="btn">
        <i class="fas fa-chart-bar"></i> Mostra Classifica
    </a>

    <?php
    if ($torneo['tipologia'] === "Gironi Multipli" && in_array($id_torneo, $squadre_utente)) {
        if (!$fase_finale_esiste) {
            echo '<a href="genera_fase_finale.php?id_torneo=' . $id_torneo . '" class="btn btn-success">
                    <i class="fas fa-sitemap"></i> Genera Fase Finale
                  </a>';
        } else {
            echo '<a href="mostra_fase_finale.php?id_torneo=' . $id_torneo . '" class="btn">
                    <i class="fas fa-sitemap"></i> Mostra Fase Finale
                  </a>';
        }
    }
    ?>
</div>

            </div>
        </div>
    <?php endforeach; ?>
</div>
</div>
</body>
</html>