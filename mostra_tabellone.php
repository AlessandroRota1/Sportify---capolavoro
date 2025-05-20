<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

if (!isset($_GET['id_torneo'])) die("ID torneo non fornito.");
$id_torneo = (int)$_GET['id_torneo'];

$res_torneo = $conn->query("SELECT nome, id_utente FROM tornei WHERE Id_torneo = $id_torneo");
$torneo_row = $res_torneo->fetch_assoc();
$nome_torneo = $torneo_row['nome'] ?? 'Torneo';
$isCreatore = isset($_SESSION['id_utente']) && $_SESSION['id_utente'] == $torneo_row['id_utente'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salva_risultato']) && isset($_SESSION['id_utente'])) {
    $id_partita = (int)$_POST['id_partita'];
    $gol_1 = (int)$_POST['gol_1'];
    $gol_2 = (int)$_POST['gol_2'];

    // Controlla se l'utente è davvero il creatore del torneo
    $res_check = $conn->query("SELECT id_utente FROM tornei WHERE Id_torneo = $id_torneo");
    $row_check = $res_check->fetch_assoc();
    if ($row_check && $row_check['id_utente'] == $_SESSION['id_utente']) {
        $stmt = $conn->prepare("UPDATE partite SET gol_1 = ?, gol_2 = ? WHERE id_partita = ?");
        $stmt->bind_param("iii", $gol_1, $gol_2, $id_partita);
        $stmt->execute();
    }
    header("Location: mostra_tabellone.php?id_torneo=$id_torneo");
    exit;
}


// Recupera tutte le partite della fase finale (eliminazione diretta)
$res = $conn->query("
    SELECT p.*, s1.nome AS nome1, s2.nome AS nome2 
    FROM partite p
    JOIN squadre s1 ON p.id_squadra1 = s1.Id_squadra
    JOIN squadre s2 ON p.id_squadra2 = s2.Id_squadra
    WHERE p.id_torneo = $id_torneo AND p.fase_finale = 1
    ORDER BY p.turno ASC, p.id_partita ASC
");

$partite = [];
while ($row = $res->fetch_assoc()) {
    $partite[$row['turno']][] = $row;
}

function getNomeTurno($turno, $max) {
    if ($turno == $max) return "Finale";
    if ($turno == $max - 1) return "Semifinali";
    if ($turno == $max - 2) return "Quarti";
    return "Turno $turno";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabellone - <?= htmlspecialchars($nome_torneo) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f766e;
            --primary-light: #14b8a6;
            --secondary: #0d9488;
            --background: #f0f9ff;
            --text: #0f172a;
            --text-light: #64748b;
            --white: #ffffff;
            --card-bg: #ffffff;
            --border: #cbd5e1;
            --success: #059669;
            --pending: #ca8a04;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background);
            color: var(--text);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }

        .header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        .header h1 {
            font-size: 2.4rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .header .subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .tournament-wrapper {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .round {
            margin-bottom: 25px;
        }

        .round-title {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 10px 10px 0 0;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .round-title i {
            margin-right: 10px;
        }

        .matches-container {
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 10px 10px;
            overflow: hidden;
        }

        .match {
            padding: 15px 20px;
            background: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .match:hover {
            background-color: #f8fafc;
        }

        .match + .match {
            border-top: 1px solid var(--border);
        }

        .team {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .team-left {
            justify-content: flex-start;
            text-align: left;
        }

        .team-right {
            justify-content: flex-end;
            text-align: right;
        }

        .team-name {
            font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .winner .team-name {
            font-weight: 700;
            color: var(--success);
        }

        .score {
            display: inline-block;
            min-width: 35px;
            padding: 5px 10px;
            border-radius: 6px;
            background-color: #f1f5f9;
            text-align: center;
            margin: 0 10px;
            font-weight: 600;
            position: relative;
        }

        .winner .score {
            background-color: #d1fae5;
            color: var(--success);
        }

        .versus {
            padding: 0 15px;
            color: var(--text-light);
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .match-date {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
            display: block;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border);
            margin-bottom: 20px;
            display: block;
        }

        .empty-state p {
            font-size: 1.1rem;
        }

        .back-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-align: center;
        }

        .back-btn:hover {
            background-color: var(--primary-light);
        }

        /* Stili migliorati per i punteggi e il form */
        .score-input {
            width: 45px;
            height: 38px;
            padding: 0 8px;
            border: 2px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            background-color: #f8fafc;
            margin: 0 8px;
            transition: all 0.2s ease;
        }

        .score-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.2);
        }

        .score-form {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .save-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-left: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .save-btn:hover {
            background-color: var(--primary-light);
        }

        .match-details {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .match-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin-bottom: 8px;
        }

        .match-actions {
            display: flex;
            justify-content: center;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }
            
            .match-teams {
                flex-direction: column;
                gap: 15px;
            }
            
            .versus {
                margin: 10px 0;
            }
            
            .team {
                width: 100%;
            }
            
            .team-left, .team-right {
                justify-content: space-between;
            }

            .team-name {
                max-width: 120px;
            }

            .score-form {
                flex-direction: column;
                align-items: center;
                gap: 10px;
                margin-top: 10px;
            }

            .save-btn {
                width: 100%;
                justify-content: center;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trophy"></i> Tabellone Torneo</h1>
            <div class="subtitle"><?= htmlspecialchars($nome_torneo) ?></div>
        </div>

        <div class="tournament-wrapper">
            <?php if (empty($partite)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-xmark"></i>
                    <p>Nessuna partita trovata per la fase finale.</p>
                    <p>Le partite verranno visualizzate qui una volta programmate.</p>
                </div>
            <?php else: ?>
                <?php 
                $max_turno = max(array_keys($partite)); 
                $icone = [
                    $max_turno => 'fa-trophy',
                    $max_turno - 1 => 'fa-medal',
                    $max_turno - 2 => 'fa-ranking-star',
                ];
                ?>
                
                <?php foreach ($partite as $turno => $matches): ?>
                    <div class="round">
                        <div class="round-title">
                            <span>
                                <i class="fas <?= $icone[$turno] ?? 'fa-futbol' ?>"></i>
                                <?= getNomeTurno($turno, $max_turno) ?>
                            </span>
                            <span><?= count($matches) ?> partite</span>
                        </div>
                        <div class="matches-container">
                            <?php foreach ($matches as $p): ?>
                                <div class="match">
                                    <div class="match-details">
                                        <div class="match-teams">
                                            <div class="team team-left <?= ($p['gol_1'] > $p['gol_2'] && $p['gol_1'] !== null) ? 'winner' : '' ?>">
                                                <span class="team-name"><?= htmlspecialchars($p['nome1']) ?></span>
                                                <?php if (!$isCreatore): ?>
                                                    <span class="score"><?= ($p['gol_1'] !== null) ? $p['gol_1'] : '-' ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="versus">VS</div>
                                            
                                            <div class="team team-right <?= ($p['gol_2'] > $p['gol_1'] && $p['gol_2'] !== null) ? 'winner' : '' ?>">
                                                <?php if (!$isCreatore): ?>
                                                    <span class="score"><?= ($p['gol_2'] !== null) ? $p['gol_2'] : '-' ?></span>
                                                <?php endif; ?>
                                                <span class="team-name"><?= htmlspecialchars($p['nome2']) ?></span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($isCreatore): ?>
                                        <div class="match-actions">
                                            <form method="post" class="score-form">
                                                <input type="hidden" name="id_partita" value="<?= $p['id_partita'] ?>">
                                                <div style="display: flex; align-items: center;">
                                                    <span class="team-name team-left" style="max-width: 100px;"><?= htmlspecialchars($p['nome1']) ?></span>
                                                    <input type="number" name="gol_1" value="<?= $p['gol_1'] ?? 0 ?>" min="0" class="score-input" required>
                                                    <span style="margin: 0 5px;">-</span>
                                                    <input type="number" name="gol_2" value="<?= $p['gol_2'] ?? 0 ?>" min="0" class="score-input" required>
                                                    <span class="team-name team-right" style="max-width: 100px;"><?= htmlspecialchars($p['nome2']) ?></span>
                                                    <button type="submit" name="salva_risultato" class="save-btn">
                                                        <i class="fas fa-save"></i> Salva
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if(isset($p['data_partita']) && $p['data_partita']): ?>
                                        <span class="match-date">
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= date('d/m/Y', strtotime($p['data_partita'])) ?>
                                            <?= isset($p['orario']) ? ' - ' . substr($p['orario'], 0, 5) : '' ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center;">
            <a href="javascript:history.back()" class="back-btn">
                <i class="fas fa-arrow-left"></i> Torna indietro
            </a>
        </div>
    </div>
    
</body>
</html>