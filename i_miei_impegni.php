<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

$id_utente = $_SESSION['id_utente'] ?? 0;
$oggi = date('Y-m-d H:i:s');

// === CALCETTI ===
$calcetti_futuri = [];
$calcetti_passati = [];

$sql = "
    SELECT c.*, ca.*, 
           CASE WHEN c.id_utente = $id_utente THEN 'Organizzatore' ELSE 'Partecipante' END AS ruolo
    FROM calcetti c
    JOIN campi ca ON c.id_campo = ca.id_campo
    LEFT JOIN calcetto_utente cu ON cu.id_calcetto = c.id_calcetto
    WHERE c.id_utente = $id_utente OR cu.id_utente = $id_utente
    GROUP BY c.id_calcetto
    ORDER BY c.data_ora ASC
";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    if ($row['data_ora'] >= $oggi) {
        $calcetti_futuri[] = $row;
    } else {
        $calcetti_passati[] = $row;
    }
}

// === TORNEI ===
$tornei_futuri = [];
$tornei_passati = [];

$sql = "
    SELECT t.*, s.nome AS squadra_nome, s.Id_squadra
    FROM tornei t
    JOIN squadre s ON s.id_torneo = t.Id_torneo
    JOIN utente_squadra us ON us.id_squadra = s.Id_squadra
    WHERE us.id_utente = $id_utente
";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    if ($row['data_fine'] >= date('Y-m-d')) {
        $tornei_futuri[] = $row;
    } else {
        $tornei_passati[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>I miei impegni</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #1976D2;
        }
        h2 {
            margin-top: 40px;
            color: #0D47A1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #e3f2fd;
        }
        .btn {
            padding: 6px 12px;
            background-color: #1976D2;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            margin: 10px 0;
            display: inline-block;
        }
        .btn:hover {
            background-color: #0D47A1;
        }
        .top-buttons {
            text-align: right;
            margin-bottom: 20px;
        }
        .storico {
            display: none;
        }
        .detail-row {
            display: none;
            background-color: #f8f9fa;
        }
        .detail-row td {
            padding: 15px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .detail-item {
            margin-bottom: 8px;
        }
        .detail-label {
            font-weight: bold;
            color: #1976D2;
        }
        .detail-toggle {
            cursor: pointer;
            color: #1976D2;
            font-weight: bold;
        }
        .detail-toggle:hover {
            text-decoration: underline;
        }
        .icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            margin-right: 5px;
            vertical-align: middle;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .badge-organizer {
            background-color: #4caf50;
            color: white;
        }
        .badge-participant {
            background-color: #03a9f4;
            color: white;
        }
        .yes-no {
            font-weight: bold;
        }
        .yes {
            color: #4caf50;
        }
        .no {
            color: #f44336;
        }
    </style>
    <script>
        function toggleStorico(section) {
            const blocco = document.getElementById(section);
            blocco.style.display = (blocco.style.display === 'none' || blocco.style.display === '') ? 'block' : 'none';
        }
        
        function toggleDetails(id, type) {
            const detailRow = document.getElementById(type + '-details-' + id);
            const currentDisplay = detailRow.style.display;
            detailRow.style.display = currentDisplay === 'none' || currentDisplay === '' ? 'table-row' : 'none';
            
            // Change toggle text
            const toggleBtn = document.getElementById(type + '-toggle-' + id);
            toggleBtn.textContent = currentDisplay === 'none' || currentDisplay === '' ? '▼ Nascondi dettagli' : '▶ Mostra dettagli';
        }
    </script>
</head>
<body>

<div class="container">
    <div class="top-buttons">
        <a href="index.php" class="btn">🏠 Home</a>
        <a href="logout.php" class="btn" style="background-color:#e74c3c;">🚪 Logout</a>
    </div>

    <h1>📅 I miei impegni programmati</h1>

    <h2>👟 Calcetti Futuri</h2>
    <?php if (empty($calcetti_futuri)): ?>
        <p>Nessun calcetto futuro.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Data e ora</th>
                <th>Campo</th>
                <th>Posti</th>
                <th>Ruolo</th>
                <th>Visibilità</th>
                <th>Azioni</th>
            </tr>
            <?php foreach ($calcetti_futuri as $i => $c): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($c['data_ora'])) ?></td>
                    <td><?= htmlspecialchars($c['indirizzo']) ?></td>
                    <td><?= $c['posti_occupati'] ?>/<?= $c['n_giocatori'] ?></td>
                    <td>
                        <span class="badge <?= $c['ruolo'] == 'Organizzatore' ? 'badge-organizer' : 'badge-participant' ?>">
                            <?= $c['ruolo'] ?>
                        </span>
                    </td>
                    <td><?= $c['visibilita'] ? 'Pubblico' : 'Privato' ?></td>
                    <td>
                        <span id="calcetto-toggle-<?= $c['id_calcetto'] ?>" class="detail-toggle" 
                              onclick="toggleDetails(<?= $c['id_calcetto'] ?>, 'calcetto')">
                            ▶ Mostra dettagli
                        </span>
                    </td>
                </tr>
                <tr id="calcetto-details-<?= $c['id_calcetto'] ?>" class="detail-row">
                    <td colspan="6">
                        <div class="detail-grid">
                            <div>
                                <h3>Informazioni Calcetto</h3>
                                <div class="detail-item">
                                    <span class="detail-label">ID Calcetto:</span> <?= $c['id_calcetto'] ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Data e ora:</span> <?= date('d/m/Y H:i', strtotime($c['data_ora'])) ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Posti occupati:</span> <?= $c['posti_occupati'] ?>/<?= $c['n_giocatori'] ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Visibilità:</span> <?= $c['visibilita'] ? 'Pubblico' : 'Privato' ?>
                                </div>
                            </div>
                            <div>
                                <h3>Informazioni Campo</h3>
                                <div class="detail-item">
                                    <span class="detail-label">ID Campo:</span> <?= $c['id_campo'] ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Indirizzo:</span> <?= htmlspecialchars($c['indirizzo']) ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tipo terreno:</span> <?= htmlspecialchars($c['terreno']) ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Numero giocatori:</span> <?= $c['n_giocatori'] ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Costo campo:</span> €<?= $c['costo'] ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Spogliatoi:</span> 
                                    <span class="yes-no <?= $c['spogliatoi'] ? 'yes' : 'no' ?>">
                                        <?= $c['spogliatoi'] ? 'Sì' : 'No' ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Docce:</span> 
                                    <span class="yes-no <?= $c['docce'] ? 'yes' : 'no' ?>">
                                        <?= $c['docce'] ? 'Sì' : 'No' ?>
                                    </span>
                                </div>
                                <?php if ($c['latitudine'] && $c['longitudine']): ?>
                                <div class="detail-item">
                                    <a href="https://www.google.com/maps?q=<?= $c['latitudine'] ?>,<?= $c['longitudine'] ?>" 
                                       target="_blank" class="btn" style="margin-top: 10px;">
                                        🗺️ Vedi su mappa
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top: 15px;">
                            <a href="chat.php?id_calcetto=<?= $c['id_calcetto'] ?>" class="btn">💬 Chat</a>
                            <?php if ($c['ruolo'] == 'Organizzatore'): ?>
                                
                            <?php else: ?>
                                <a href="abbandona_calcetto.php?id=<?= $c['id_calcetto'] ?>" 
                                   class="btn" style="background-color: #e74c3c;"
                                   onclick="return confirm('Sei sicuro di voler abbandonare questo calcetto?');">
                                    🚶 Abbandona
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <button class="btn" onclick="toggleStorico('storico-calcetti')">📜 Visualizza Storico Calcetti</button>
    <div id="storico-calcetti" class="storico">
        <h2>📜 Storico Calcetti</h2>
        <?php if (empty($calcetti_passati)): ?>
            <p>Nessun calcetto passato.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Data e ora</th>
                    <th>Campo</th>
                    <th>Posti</th>
                    <th>Ruolo</th>
                    <th>Visibilità</th>
                    <th>Azioni</th>
                </tr>
                <?php foreach ($calcetti_passati as $c): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($c['data_ora'])) ?></td>
                        <td><?= htmlspecialchars($c['indirizzo']) ?></td>
                        <td><?= $c['posti_occupati'] ?>/<?= $c['n_giocatori'] ?></td>
                        <td>
                            <span class="badge <?= $c['ruolo'] == 'Organizzatore' ? 'badge-organizer' : 'badge-participant' ?>">
                                <?= $c['ruolo'] ?>
                            </span>
                        </td>
                        <td><?= $c['visibilita'] ? 'Pubblico' : 'Privato' ?></td>
                        <td>
                            <span id="calcetto-past-toggle-<?= $c['id_calcetto'] ?>" class="detail-toggle" 
                                  onclick="toggleDetails(<?= $c['id_calcetto'] ?>, 'calcetto-past')">
                                ▶ Mostra dettagli
                            </span>
                        </td>
                    </tr>
                    <tr id="calcetto-past-details-<?= $c['id_calcetto'] ?>" class="detail-row">
                        <td colspan="6">
                            <div class="detail-grid">
                                <div>
                                    <h3>Informazioni Calcetto</h3>
                                    <div class="detail-item">
                                        <span class="detail-label">ID Calcetto:</span> <?= $c['id_calcetto'] ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Data e ora:</span> <?= date('d/m/Y H:i', strtotime($c['data_ora'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Posti occupati:</span> <?= $c['posti_occupati'] ?>/<?= $c['n_giocatori'] ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Visibilità:</span> <?= $c['visibilita'] ? 'Pubblico' : 'Privato' ?>
                                    </div>
                                </div>
                                <div>
                                    <h3>Informazioni Campo</h3>
                                    <div class="detail-item">
                                        <span class="detail-label">ID Campo:</span> <?= $c['id_campo'] ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Indirizzo:</span> <?= htmlspecialchars($c['indirizzo']) ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Tipo terreno:</span> <?= htmlspecialchars($c['terreno']) ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Numero giocatori:</span> <?= $c['n_giocatori'] ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Costo campo:</span> €<?= $c['costo'] ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Spogliatoi:</span> 
                                        <span class="yes-no <?= $c['spogliatoi'] ? 'yes' : 'no' ?>">
                                            <?= $c['spogliatoi'] ? 'Sì' : 'No' ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Docce:</span> 
                                        <span class="yes-no <?= $c['docce'] ? 'yes' : 'no' ?>">
                                            <?= $c['docce'] ? 'Sì' : 'No' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 15px;">
                                <a href="recensione.php?tipo=calcetto&id=<?= $c['id_calcetto'] ?>" class="btn">⭐ Lascia una recensione</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <h2>🏆 Tornei Attivi</h2>
    <?php if (empty($tornei_futuri)): ?>
        <p>Non sei iscritto a tornei futuri.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Nome Torneo</th>
                <th>Periodo</th>
                <th>Squadra</th>
                <th>Tipologia</th>
                <th>Azioni</th>
            </tr>
            <?php foreach ($tornei_futuri as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['nome']) ?></td>
                    <td><?= date('d/m/Y', strtotime($t['data_inizio'])) ?> - <?= date('d/m/Y', strtotime($t['data_fine'])) ?></td>
                    <td><?= htmlspecialchars($t['squadra_nome']) ?></td>
                    <td><?= htmlspecialchars($t['tipologia']) ?></td>
                    <td>
                        <span id="torneo-toggle-<?= $t['Id_torneo'] ?>" class="detail-toggle" 
                              onclick="toggleDetails(<?= $t['Id_torneo'] ?>, 'torneo')">
                            ▶ Mostra dettagli
                        </span>
                    </td>
                </tr>
                <tr id="torneo-details-<?= $t['Id_torneo'] ?>" class="detail-row">
                    <td colspan="5">
                        <div class="detail-grid">
                            <div>
                                <h3>Informazioni Torneo</h3>
                                <div class="detail-item">
                                    <span class="detail-label">ID Torneo:</span> <?= $t['Id_torneo'] ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Nome:</span> <?= htmlspecialchars($t['nome']) ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Periodo:</span> <?= date('d/m/Y', strtotime($t['data_inizio'])) ?> - <?= date('d/m/Y', strtotime($t['data_fine'])) ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Orario:</span> <?= substr($t['ora_inizio'], 0, 5) ?> - <?= substr($t['ora_fine'], 0, 5) ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Tipologia:</span> <?= htmlspecialchars($t['tipologia']) ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Max giocatori per squadra:</span> <?= $t['max_giocatori'] ?>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Max squadre:</span> <?= $t['max_squadre'] ?>
                                </div>
                            </div>
                            <div>
                                <h3>Requisiti e Note</h3>
                                <div class="detail-item">
                                    <span class="detail-label">Certificato medico richiesto:</span> 
                                    <span class="yes-no <?= $t['certificato_medico'] ? 'yes' : 'no' ?>">
                                        <?= $t['certificato_medico'] ? 'Sì' : 'No' ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Docce disponibili:</span> 
                                    <span class="yes-no <?= $t['docce'] ? 'yes' : 'no' ?>">
                                        <?= $t['docce'] ? 'Sì' : 'No' ?>
                                    </span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Note:</span> 
                                    <p><?= htmlspecialchars($t['note']) ?></p>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">La tua squadra:</span> <?= htmlspecialchars($t['squadra_nome']) ?>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 15px;">
                            <a href="mostra_calendario.php?id_torneo=<?= $t['Id_torneo'] ?>" class="btn">📖 Calendario</a>
                            <a href="mostra_classifica.php?id_torneo=<?= $t['Id_torneo'] ?>" class="btn">📊 Classifica</a>
                            <a href="dettagli_squadra.php?id=<?= $t['Id_squadra'] ?>" class="btn">👥 La mia squadra</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <button class="btn" onclick="toggleStorico('storico-tornei')">📜 Visualizza Storico Tornei</button>
    <div id="storico-tornei" class="storico">
        <h2>📜 Storico Tornei</h2>
        <?php if (empty($tornei_passati)): ?>
            <p>Nessun torneo concluso.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Nome Torneo</th>
                    <th>Periodo</th>
                    <th>Squadra</th>
                    <th>Tipologia</th>
                    <th>Azioni</th>
                </tr>
                <?php foreach ($tornei_passati as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['nome']) ?></td>
                        <td><?= date('d/m/Y', strtotime($t['data_inizio'])) ?> - <?= date('d/m/Y', strtotime($t['data_fine'])) ?></td>
                        <td><?= htmlspecialchars($t['squadra_nome']) ?></td>
                        <td><?= htmlspecialchars($t['tipologia']) ?></td>
                        <td>
                            <span id="torneo-past-toggle-<?= $t['Id_torneo'] ?>" class="detail-toggle" 
                                  onclick="toggleDetails(<?= $t['Id_torneo'] ?>, 'torneo-past')">
                                ▶ Mostra dettagli
                            </span>
                        </td>
                    </tr>
                    <tr id="torneo-past-details-<?= $t['Id_torneo'] ?>" class="detail-row">
                        <td colspan="5">
                            <div class="detail-grid">
                                <div>
                                    <h3>Informazioni Torneo</h3>
                                    <div class="detail-item">
                                        <span class="detail-label">ID Torneo:</span> <?= $t['Id_torneo'] ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Nome:</span> <?= htmlspecialchars($t['nome']) ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Periodo:</span> <?= date('d/m/Y', strtotime($t['data_inizio'])) ?> - <?= date('d/m/Y', strtotime($t['data_fine'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Orario:</span> <?= substr($t['ora_inizio'], 0, 5) ?> - <?= substr($t['ora_fine'], 0, 5) ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Tipologia:</span> <?= htmlspecialchars($t['tipologia']) ?>
                                    </div>
                                </div>
                                <div>
                                    <h3>Requisiti e Note</h3>
                                    <div class="detail-item">
                                        <span class="detail-label">Certificato medico richiesto:</span> 
                                        <span class="yes-no <?= $t['certificato_medico'] ? 'yes' : 'no' ?>">
                                            <?= $t['certificato_medico'] ? 'Sì' : 'No' ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Docce disponibili:</span> 
                                        <span class="yes-no <?= $t['docce'] ? 'yes' : 'no' ?>">
                                            <?= $t['docce'] ? 'Sì' : 'No' ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Note:</span> 
                                        <p><?= htmlspecialchars($t['note']) ?></p>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">La tua squadra:</span> <?= htmlspecialchars($t['squadra_nome']) ?>
                                    </div>
                                </div>
                            </div>
                            <div style="margin-top: 15px;">
                                <a href="mostra_calendario.php?id_torneo=<?= $t['Id_torneo'] ?>" class="btn">📖 Calendario finale</a>
                                <a href="mostra_classifica.php?id_torneo=<?= $t['Id_torneo'] ?>" class="btn">📊 Classifica finale</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>