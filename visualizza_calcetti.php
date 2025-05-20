<?php
session_start();

// Controllo accesso
if (!isset($_SESSION['id_utente'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sportify";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

$id_utente = $_SESSION['id_utente'];
$messaggio = "";

// Gestione unione al calcetto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['unisciti'])) {
    $id_calcetto = (int)$_POST['id_calcetto'];

    $resC = $conn->query("SELECT id_utente FROM calcetti WHERE id_calcetto = $id_calcetto");
    if ($rowC = $resC->fetch_assoc()) {
        if ($rowC['id_utente'] == $id_utente) {
            $messaggio = "Non puoi unirti a un calcetto che hai creato.";
        } else {
            $resV = $conn->query("SELECT 1 FROM calcetto_utente WHERE id_calcetto = $id_calcetto AND id_utente = $id_utente");
            if ($resV->num_rows) {
                $messaggio = "Sei già iscritto a questo calcetto!";
            } else {
                $resP = $conn->query("
                    SELECT c.posti_occupati, ca.n_giocatori
                    FROM calcetti c
                    JOIN campi ca ON c.id_campo = ca.id_campo
                    WHERE c.id_calcetto = $id_calcetto
                ");
                if ($rowP = $resP->fetch_assoc()) {
                    $posti = $rowP['posti_occupati'];
                    $capacity = $rowP['n_giocatori'] * 2;
                    if ($posti < $capacity) {
                        $conn->query("INSERT INTO calcetto_utente (id_calcetto, id_utente) VALUES ($id_calcetto, $id_utente)");
                        $conn->query("UPDATE calcetti SET posti_occupati = posti_occupati + 1 WHERE id_calcetto = $id_calcetto");
                        $messaggio = "Ti sei unito al calcetto con successo!";
                    } else {
                        $messaggio = "Il calcetto è già completo ($capacity giocatori).";
                    }
                } else {
                    $messaggio = "Errore nel recupero dei posti.";
                }
            }
        }
    }
}

// Recupero lista calcetti
$resCalc = $conn->query("
    SELECT c.id_calcetto, c.data_ora, ca.indirizzo, c.posti_occupati, ca.n_giocatori
    FROM calcetti c
    JOIN campi ca ON c.id_campo = ca.id_campo
    WHERE c.visibilita = 1
    ORDER BY c.data_ora ASC
");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Calcetti | Sportify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --success-color: #2ecc71;
            --success-dark: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --gray-light: #f8f9fa;
            --gray-medium: #e9ecef;
            --gray-dark: #343a40;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 8px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .container {
            width: 90%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .header {
            background-color: white;
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            position: relative;
        }
        
        .header h1 {
            text-align: center;
            color: var(--gray-dark);
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .header p {
            text-align: center;
            color: #777;
            font-size: 1rem;
        }
        
        .nav-btn {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            background-color: white;
            color: var(--primary-color);
            padding: 10px 15px;
            border-radius: var(--radius);
            font-weight: 500;
            transition: var(--transition);
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .nav-btn i {
            margin-right: 8px;
        }
        
        .nav-btn:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .message {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-color);
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            color: #2c7a51;
            font-weight: 500;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .calcetto-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-top: 30px;
        }
        
        .calcetto-item {
            background-color: white;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .calcetto-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .calcetto-header {
            padding: 20px;
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-medium);
        }
        
        .calcetto-title {
            display: flex;
            flex-direction: column;
        }
        
        .calcetto-title h3 {
            font-size: 1.2rem;
            color: var(--gray-dark);
            margin-bottom: 5px;
        }
        
        .calcetto-date {
            display: flex;
            align-items: center;
            color: #666;
            font-size: 0.9rem;
        }
        
        .calcetto-date i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .calcetto-actions {
            display: flex;
            gap: 10px;
        }
        
        .calcetto-body {
            padding: 15px 20px;
            background-color: #fbfbfb;
        }
        
        .capacity {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .capacity i {
            color: var(--primary-color);
            margin-right: 8px;
        }
        
        .capacity-bar {
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .capacity-progress {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: var(--radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            white-space: nowrap;
            font-size: 0.9rem;
        }
        
        .btn i {
            margin-right: 6px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: var(--success-dark);
        }
        
        .btn-info {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #ced4da;
        }
        
        .btn-info:hover {
            background-color: #e9ecef;
        }
        
        .iscritto {
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            color: var(--success-color);
            padding: 8px 12px;
            border-radius: var(--radius);
            background-color: rgba(46, 204, 113, 0.1);
        }
        
        .iscritto i {
            margin-right: 6px;
        }
        
        .info-section {
            background-color: white;
            border-top: 1px dashed #ddd;
            padding: 20px;
            display: none;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .info-section h4 {
            font-size: 1.1rem;
            color: var(--gray-dark);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--gray-medium);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-col {
            padding-right: 15px;
        }
        
        .participant-list {
            list-style: none;
            margin-bottom: 15px;
        }
        
        .participant-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }
        
        .participant-item:last-child {
            border-bottom: none;
        }
        
        .admin-badge {
            background-color: var(--warning-color);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 8px;
        }
        
        .detail-list {
            list-style: none;
        }
        
        .detail-item {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            min-width: 120px;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .detail-value i {
            margin-right: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        
        .empty-state p {
            color: #777;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .calcetto-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .calcetto-actions {
                margin-top: 15px;
                width: 100%;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
        function toggleInfo(id) {
            const section = document.getElementById('info-' + id);
            if (section.style.display === 'block') {
                section.style.display = 'none';
                document.getElementById('toggle-btn-' + id).innerHTML = '<i class="fas fa-info-circle"></i> Mostra Info';
            } else {
                section.style.display = 'block';
                document.getElementById('toggle-btn-' + id).innerHTML = '<i class="fas fa-chevron-up"></i> Nascondi Info';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Calcetti Disponibili</h1>
            <p>Trova e unisciti ai prossimi eventi di calcetto</p>
        </div>
        
        <a href="index.php" class="nav-btn">
            <i class="fas fa-home"></i> Torna alla Home
        </a>
        
        <?php if ($messaggio): ?>
            <div class="message">
                <?= $messaggio ?>
            </div>
        <?php endif; ?>

        <div class="calcetto-list">
            <?php if ($resCalc->num_rows): ?>
                <?php while ($c = $resCalc->fetch_assoc()):
                    $capacity = $c['n_giocatori'] * 2;
                    $id_calcetto = $c['id_calcetto'];
                    $percentuale = ($c['posti_occupati'] / $capacity) * 100;

                    // Controllo iscrizione
                    $iscritto = $conn->query("
                        SELECT 1 FROM calcetto_utente 
                        WHERE id_calcetto = $id_calcetto AND id_utente = $id_utente
                    ")->num_rows > 0;
                    
                    // Formatta data e ora
                    $data_ora = new DateTime($c['data_ora']);
                    $data = $data_ora->format('d/m/Y');
                    $ora = $data_ora->format('H:i');
                ?>
                    <div class="calcetto-item">
                        <div class="calcetto-header">
                            <div class="calcetto-title">
                                <h3><?= htmlspecialchars($c['indirizzo']) ?></h3>
                                <div class="calcetto-date">
                                    <i class="fas fa-calendar-alt"></i> <?= $data ?> alle <?= $ora ?>
                                </div>
                            </div>
                            <div class="calcetto-actions">
                                <?php if (!$iscritto): ?>
                                    <form method="POST">
                                        <input type="hidden" name="id_calcetto" value="<?= $id_calcetto ?>">
                                        <button class="btn btn-success" name="unisciti">
                                            <i class="fas fa-plus-circle"></i> Unisciti
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="iscritto">
                                        <i class="fas fa-check-circle"></i> Iscritto
                                    </span>
                                    <a href="chat.php?id_calcetto=<?= $id_calcetto ?>" class="btn btn-primary">
                                        <i class="fas fa-comments"></i> Chat
                                    </a>
                                <?php endif; ?>
                                <button id="toggle-btn-<?= $id_calcetto ?>" class="btn btn-info" onclick="toggleInfo(<?= $id_calcetto ?>)">
                                    <i class="fas fa-info-circle"></i> Mostra Info
                                </button>
                            </div>
                        </div>
                        
                        <div class="calcetto-body">
                            <div class="capacity">
                                <i class="fas fa-users"></i>
                                <span>Posti: <?= $c['posti_occupati'] ?> / <?= $capacity ?></span>
                            </div>
                            <div class="capacity-bar">
                                <div class="capacity-progress" style="width: <?= $percentuale ?>%"></div>
                            </div>
                        </div>

                        <div class="info-section" id="info-<?= $id_calcetto ?>">
                            <div class="info-grid">
                                <div class="info-col">
                                    <h4><i class="fas fa-users"></i> Partecipanti</h4>
                                    <?php
                                    $resP = $conn->query("
                                        SELECT u.nome, u.cognome, u.id_utente,
                                               CASE WHEN u.id_utente = c.id_utente THEN 1 ELSE 0 END AS is_creatore
                                        FROM (
                                            SELECT id_utente, id_calcetto FROM calcetto_utente
                                            UNION
                                            SELECT id_utente, id_calcetto FROM calcetti
                                        ) cu
                                        JOIN utenti u ON cu.id_utente = u.id_utente
                                        JOIN calcetti c ON cu.id_calcetto = c.id_calcetto
                                        WHERE cu.id_calcetto = $id_calcetto
                                    ");
                                    
                                    if ($resP->num_rows) {
                                        echo '<ul class="participant-list">';
                                        while ($p = $resP->fetch_assoc()) {
                                            echo '<li class="participant-item">';
                                            echo '<i class="fas fa-user" style="margin-right: 10px; color: #666;"></i>';
                                            echo htmlspecialchars($p['nome'] . ' ' . $p['cognome']);
                                            if ($p['is_creatore']) echo '<span class="admin-badge">Admin</span>';
                                            echo '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        echo '<p>Nessun partecipante.</p>';
                                    }
                                    ?>
                                </div>
                                <div class="info-col">
                                    <h4><i class="fas fa-info-circle"></i> Dettagli Campo</h4>
                                    <?php
                                    $resD = $conn->query("
                                        SELECT ca.indirizzo, ca.n_giocatori, c.data_ora, c.posti_occupati, ca.terreno, ca.spogliatoi, ca.docce
                                        FROM calcetti c
                                        JOIN campi ca ON c.id_campo = ca.id_campo
                                        WHERE c.id_calcetto = $id_calcetto
                                    ");
                                    
                                    if ($d = $resD->fetch_assoc()) {
                                        echo '<ul class="detail-list">';
                                        echo '<li class="detail-item">';
                                        echo '<span class="detail-label">Terreno:</span>';
                                        echo '<span class="detail-value"><i class="fas fa-futbol"></i> ' . htmlspecialchars($d['terreno']) . '</span>';
                                        echo '</li>';
                                        
                                        echo '<li class="detail-item">';
                                        echo '<span class="detail-label">Spogliatoi:</span>';
                                        echo '<span class="detail-value">';
                                        if ($d['spogliatoi']) {
                                            echo '<i class="fas fa-check" style="color: var(--success-color);"></i> Disponibili';
                                        } else {
                                            echo '<i class="fas fa-times" style="color: var(--danger-color);"></i> Non disponibili';
                                        }
                                        echo '</span>';
                                        echo '</li>';
                                        
                                        echo '<li class="detail-item">';
                                        echo '<span class="detail-label">Docce:</span>';
                                        echo '<span class="detail-value">';
                                        if ($d['docce']) {
                                            echo '<i class="fas fa-check" style="color: var(--success-color);"></i> Disponibili';
                                        } else {
                                            echo '<i class="fas fa-times" style="color: var(--danger-color);"></i> Non disponibili';
                                        }
                                        echo '</span>';
                                        echo '</li>';
                                        
                                        $data_ora_oggetto = new DateTime($d['data_ora']);
                                        $giorno_settimana = [
                                            'Monday' => 'Lunedì',
                                            'Tuesday' => 'Martedì',
                                            'Wednesday' => 'Mercoledì',
                                            'Thursday' => 'Giovedì',
                                            'Friday' => 'Venerdì',
                                            'Saturday' => 'Sabato',
                                            'Sunday' => 'Domenica'
                                        ][$data_ora_oggetto->format('l')];
                                        
                                        echo '<li class="detail-item">';
                                        echo '<span class="detail-label">Giorno:</span>';
                                        echo '<span class="detail-value"><i class="fas fa-calendar-day"></i> ' . $giorno_settimana . '</span>';
                                        echo '</li>';
                                        
                                        echo '<li class="detail-item">';
                                        echo '<span class="detail-label">Formato:</span>';
                                        echo '<span class="detail-value"><i class="fas fa-users"></i> ' . $d['n_giocatori'] . ' contro ' . $d['n_giocatori'] . '</span>';
                                        echo '</li>';
                                        echo '</ul>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Non ci sono calcetti disponibili al momento.</p>
                    <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc; margin: 15px 0;"></i>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php $conn->close(); ?>
</body>
</html>