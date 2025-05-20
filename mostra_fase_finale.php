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
$messaggio_vittoria = "";

// Salva risultati se inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST['partita'] as $id_partita => $dati) {
        $gol1 = (int)$dati['gol1'];
        $gol2 = (int)$dati['gol2'];
        $data = $conn->real_escape_string($dati['data']);
        $orario = $conn->real_escape_string($dati['orario']);

        $conn->query("UPDATE partite 
                      SET gol_1 = $gol1, gol_2 = $gol2, data_partita = '$data', orario = '$orario' 
                      WHERE id_partita = $id_partita");
    }
}
// Calcola e avanza vincitori al prossimo turno SOLO se non esiste già
$turni = $conn->query("SELECT MAX(turno) as max_turno FROM partite WHERE id_torneo = $id_torneo AND fase_finale = 1");
$max_turno = (int)($turni->fetch_assoc()['max_turno'] ?? 1);

// Prendi l'ultimo turno completo
$last_turn = $conn->query("SELECT * FROM partite 
    WHERE id_torneo = $id_torneo AND fase_finale = 1 
    AND turno = $max_turno AND gol_1 IS NOT NULL AND gol_2 IS NOT NULL");

$vincitori = [];
while ($row = $last_turn->fetch_assoc()) {
    if ($row['gol_1'] > $row['gol_2']) $vincitori[] = $row['id_squadra1'];
    elseif ($row['gol_2'] > $row['gol_1']) $vincitori[] = $row['id_squadra2'];
}

// Se c'è solo un vincitore e una sola partita → è la finale → NON generare altri turni
if (count($vincitori) === 1 && $last_turn->num_rows === 1) {
    $res_nome = $conn->query("SELECT nome FROM squadre WHERE Id_squadra = {$vincitori[0]}");
    $nome_vincente = $res_nome->fetch_assoc()['nome'];
    $messaggio_vittoria = "🏆 Complimenti, <strong>$nome_vincente</strong> ha vinto il torneo!";
} elseif (count($vincitori) >= 2) {
    // Verifica che il turno successivo NON esista già
    $prossimo_turno = $max_turno + 1;
    $check = $conn->query("SELECT 1 FROM partite WHERE id_torneo = $id_torneo AND fase_finale = 1 AND turno = $prossimo_turno");
    if ($check->num_rows === 0) {
        for ($i = 0; $i < count($vincitori); $i += 2) {
            if (!isset($vincitori[$i + 1])) break;
            $s1 = $vincitori[$i];
            $s2 = $vincitori[$i + 1];
            $conn->query("INSERT INTO partite (id_torneo, id_squadra1, id_squadra2, fase_finale, turno)
                          VALUES ($id_torneo, $s1, $s2, 1, $prossimo_turno)");
        }
    }
}


// Ottieni informazioni sul torneo
$res_torneo = $conn->query("SELECT nome FROM tornei WHERE Id_torneo = $id_torneo");
$nome_torneo = "Torneo";
if ($row_torneo = $res_torneo->fetch_assoc()) {
    $nome_torneo = $row_torneo['nome'];
}

// Mostra partite fase finale
$res = $conn->query("SELECT p.*, s1.nome AS nome1, s2.nome AS nome2 
                     FROM partite p
                     JOIN squadre s1 ON p.id_squadra1 = s1.Id_squadra
                     JOIN squadre s2 ON p.id_squadra2 = s2.Id_squadra
                     WHERE p.id_torneo = $id_torneo AND p.fase_finale = 1
                     ORDER BY p.turno ASC, p.id_partita ASC");

$partite = [];
$ultimi_vincitori = [];

while ($row = $res->fetch_assoc()) {
    $partite[$row['turno']][] = $row;
    if ($row['gol_1'] !== null && $row['gol_2'] !== null) {
        if ($row['gol_1'] > $row['gol_2']) $ultimi_vincitori[] = $row['id_squadra1'];
        elseif ($row['gol_2'] > $row['gol_1']) $ultimi_vincitori[] = $row['id_squadra2'];
    }
}

// Rileva finale completata e blocca
if (!empty($partite)) {
    $last_turn = max(array_keys($partite));
    if (count($partite[$last_turn]) === 1) {
        $finale = $partite[$last_turn][0];
        if ($finale['gol_1'] !== null && $finale['gol_2'] !== null) {
            $id_vincitore = ($finale['gol_1'] > $finale['gol_2']) ? $finale['id_squadra1'] : $finale['id_squadra2'];
            if ($finale['gol_1'] != $finale['gol_2']) { // Assicurati che ci sia un vincitore
                $res_nome = $conn->query("SELECT nome FROM squadre WHERE Id_squadra = $id_vincitore");
                $nome_vincente = $res_nome->fetch_assoc()['nome'];
                $messaggio_vittoria = "🏆 Complimenti, <strong>$nome_vincente</strong> ha vinto il torneo!";
            }
        }
    }
}

// Determina il nome del turno
function getNomeTurno($turno, $max_turno) {
    if ($turno == $max_turno) return "Finale";
    if ($turno == $max_turno - 1) return "Semifinali";
    if ($turno == $max_turno - 2) return "Quarti di finale";
    return "Turno " . $turno;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fase Finale - <?= htmlspecialchars($nome_torneo) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #388E3C;
            --accent: #FFC107;
            --light: #f9f9f9;
            --dark: #2c3e50;
            --gray: #e0e0e0;
            --text: #333;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background-color: var(--light);
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: var(--primary);
            color: white;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            margin-bottom: 2rem;
            position: relative;
        }
        
        .back-button {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .back-button:hover {
            color: var(--accent);
        }
        
        h1 {
            font-size: 2rem;
            margin: 0;
        }
        
        .victory-message {
            background-color: #e8f5e9;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border-left: 5px solid var(--primary);
            color: var(--primary-dark);
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            animation: fade-in 0.5s ease-in-out;
        }
        
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .tournament-round {
            margin-bottom: 3rem;
        }
        
        .round-title {
            background-color: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0;
        }
        
        .matches {
            background-color: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden; 
        }
        
        .match {
            display: grid;
                grid-template-columns: 1fr 50px 100px 1fr auto;
;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--gray);
            gap: 1rem;
        }
        
        .match:last-child {
            border-bottom: none;
        }
        
        .team {
            font-weight: 500;
            padding: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .team-1 {
            text-align: right;
            justify-self: end;
        }
        
        .team-2 {
            text-align: left;
            justify-self: start;
        }
        
        .vs {
            font-weight: bold;
            color: var(--accent);
            margin: 0 1rem;
        }
        
        .score-input {
            width: 60px;
            padding: 0.5rem;
            border: 1px solid var(--gray);
            border-radius: var(--border-radius);
            text-align: center;
            font-size: 1rem;
        }
        
        .date-time-inputs {
            display: flex;
            gap: 0.5rem;
        }
        
        input[type="date"], 
        input[type="time"] {
            padding: 0.5rem;
            border: 1px solid var(--gray);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }
        
        .submit-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            display: block;
            margin: 2rem auto;
            box-shadow: var(--box-shadow);
        }
        
        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .winner {
            font-weight: bold;
            color: var(--primary-dark);
        }
        
        .lock-notice {
            background-color: #fff3cd;
            padding: 1rem;
            border-radius: var(--border-radius);
            text-align: center;
            margin-bottom: 2rem;
            border-left: 5px solid var(--accent);
        }
        
        .no-matches {
            background-color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--box-shadow);
        }
        
        .readonly-value {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            width: 60px;
            display: inline-block;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .match {
                grid-template-columns: 1fr auto 1fr;
                grid-template-rows: auto auto;
                gap: 0.5rem;
            }
            
            .date-time-inputs {
                grid-column: 1 / -1;
                justify-content: center;
                margin-top: 0.5rem;
            }
            
            .team-1, .team-2 {
                max-width: 120px;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i>&nbsp; Indietro
        </a>
        <h1>Fase Finale - <?= htmlspecialchars($nome_torneo) ?></h1>
    </header>

    <div class="container">
        <?php if ($messaggio_vittoria): ?>
            <div class="victory-message">
                <i class="fas fa-trophy"></i> <?= $messaggio_vittoria ?>
            </div>
            <?php if (!empty($partite)): ?>
                <div class="lock-notice">
                    <i class="fas fa-lock"></i> Il torneo è concluso. I risultati sono visualizzati in sola lettura.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($partite)): ?>
            <div class="no-matches">
                <p>Non ci sono ancora partite programmate per la fase finale.</p>
            </div>
        <?php else: ?>
            <?php if (!$messaggio_vittoria): ?>
                <form method="post">
            <?php endif; ?>
            
            <?php 
            $max_turno = max(array_keys($partite)); 
            foreach ($partite as $turno => $match): 
                $nome_turno = getNomeTurno($turno, $max_turno);
            ?>
                <div class="tournament-round">
                    <h2 class="round-title">
                        <i class="fas fa-futbol"></i> <?= $nome_turno ?>
                    </h2>
                    
                    <div class="matches">
                        <?php foreach ($match as $p): ?>
                            <div class="match">
                                <div class="team team-1 <?= ($p['gol_1'] > $p['gol_2'] && $p['gol_1'] !== null && $p['gol_2'] !== null) ? 'winner' : '' ?>">
                                    <?= htmlspecialchars($p['nome1']) ?>
                                </div>
                                
                                <?php if ($messaggio_vittoria): ?>
                                    <div class="readonly-value"><?= $p['gol_1'] ?? '-' ?></div>
                                <?php else: ?>
                                    <input type="number" min="0" class="score-input" 
                                           name="partita[<?= $p['id_partita'] ?>][gol1]" 
                                           value="<?= $p['gol_1'] ?? '' ?>">
                                <?php endif; ?>
                                       
                                <div class="team team-2 <?= ($p['gol_2'] > $p['gol_1'] && $p['gol_1'] !== null && $p['gol_2'] !== null) ? 'winner' : '' ?>">
                                    <?= htmlspecialchars($p['nome2']) ?>
                                </div>
                                
                                <?php if ($messaggio_vittoria): ?>
                                    <div class="readonly-value"><?= $p['gol_2'] ?? '-' ?></div>
                                <?php else: ?>
                                    <input type="number" min="0" class="score-input" 
                                           name="partita[<?= $p['id_partita'] ?>][gol2]" 
                                           value="<?= $p['gol_2'] ?? '' ?>">
                                <?php endif; ?>
                                
                                <div class="date-time-inputs">
                                    <?php if ($messaggio_vittoria): ?>
                                        <span><?= $p['data_partita'] ? date('d/m/Y', strtotime($p['data_partita'])) : '-' ?></span>
                                        <span><?= $p['orario'] ?: '-' ?></span>
                                    <?php else: ?>
                                        <input type="date" name="partita[<?= $p['id_partita'] ?>][data]" 
                                               value="<?= $p['data_partita'] ?>">
                                        <input type="time" name="partita[<?= $p['id_partita'] ?>][orario]" 
                                               value="<?= $p['orario'] ?>">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!$messaggio_vittoria): ?>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Salva Risultati e Avanza
                </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>