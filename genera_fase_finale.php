<?php 
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

if (!isset($_GET['id_torneo'])) {
    die("ID torneo non fornito.");
}

$id_torneo = (int)$_GET['id_torneo'];

// Recupera info torneo
$sql_torneo = "SELECT nome FROM tornei WHERE id = $id_torneo";
$res_torneo = $conn->query($sql_torneo);
$nome_torneo = ($res_torneo && $res_torneo->num_rows > 0) ? $res_torneo->fetch_assoc()['nome'] : "Torneo #$id_torneo";

// Recupera le squadre con i gironi
$sql = "SELECT
    s.Id_squadra,
    s.nome,
    s.girone,
    COUNT(p.id_partita) AS giocate,
    SUM(
        CASE 
            WHEN (s.Id_squadra = p.id_squadra1 AND p.gol_1 > p.gol_2) OR
                 (s.Id_squadra = p.id_squadra2 AND p.gol_2 > p.gol_1)
            THEN 3
            WHEN p.gol_1 = p.gol_2 THEN 1
            ELSE 0
        END
    ) AS punti,
    SUM(CASE WHEN s.Id_squadra = p.id_squadra1 THEN p.gol_1 ELSE p.gol_2 END) AS gol_fatti,
    SUM(CASE WHEN s.Id_squadra = p.id_squadra1 THEN p.gol_2 ELSE p.gol_1 END) AS gol_subiti
FROM squadre s
JOIN partite p ON (s.Id_squadra = p.id_squadra1 OR s.Id_squadra = p.id_squadra2)
WHERE s.id_torneo = $id_torneo 
  AND p.gol_1 IS NOT NULL AND p.gol_2 IS NOT NULL
GROUP BY s.Id_squadra
ORDER BY
    punti DESC,
    (SUM(CASE WHEN s.Id_squadra = p.id_squadra1 THEN p.gol_1 ELSE p.gol_2 END) - 
     SUM(CASE WHEN s.Id_squadra = p.id_squadra1 THEN p.gol_2 ELSE p.gol_1 END)) DESC,
    SUM(CASE WHEN s.Id_squadra = p.id_squadra1 THEN p.gol_1 ELSE p.gol_2 END) DESC";

$res = $conn->query($sql);
if (!$res) die("Errore nella query: " . $conn->error);

// Organizza le squadre per girone
$gironi = [];
while ($row = $res->fetch_assoc()) {
    $girone = $row['girone'] ?: 'Unico';
    $row['diff'] = $row['gol_fatti'] - $row['gol_subiti'];
    $gironi[$girone][] = $row;
}

// Prendi le prime due di ogni girone
$qualificate = [];
$terze = [];
foreach ($gironi as $girone => $squadre) {
    if (count($squadre) >= 1) $qualificate[] = $squadre[0]; // prima
    if (count($squadre) >= 2) $qualificate[] = $squadre[1]; // seconda
    if (count($squadre) >= 3) $terze[] = $squadre[2];       // terza
}

// Verifica quante servono per completare il tabellone
function nextPowerOfTwo($x) {
    return pow(2, ceil(log($x, 2)));
}

$totali = count($qualificate);
$target = nextPowerOfTwo($totali);
$da_aggiungere = $target - $totali;

// Ordina le terze per punti, differenza reti, gol fatti
usort($terze, function($a, $b) {
    if ($a['punti'] !== $b['punti']) return $b['punti'] - $a['punti'];
    if ($a['diff'] !== $b['diff']) return $b['diff'] - $a['diff'];
    return $b['gol_fatti'] - $a['gol_fatti'];
});

// Aggiungi le migliori terze
$qualificate = array_merge($qualificate, array_slice($terze, 0, $da_aggiungere));

// Shuffle per abbinamenti casuali o ordinamento
shuffle($qualificate);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fase Finale - <?php echo htmlspecialchars($nome_torneo); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --border-radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f4f7f9;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        h1 {
            color: var(--secondary);
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        h2 {
            color: var(--primary);
            font-size: 1.8rem;
            margin: 25px 0 15px;
            position: relative;
            padding-left: 15px;
        }
        
        h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background: var(--primary);
            border-radius: 3px;
        }
        
        .summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }
        
        .summary p {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .summary strong {
            color: var(--dark);
        }
        
        .matches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .match-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e9ecef;
        }
        
        .match-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .match-header {
            background: var(--primary);
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            text-align: center;
        }
        
        .team {
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .team:first-of-type {
            border-bottom: 1px solid #eee;
        }
        
        .team-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }
        
        .team-stats {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .vs {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 40px;
            background: #f8f9fa;
            font-weight: bold;
            color: var(--secondary);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            margin-top: 20px;
            transition: background 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 15px;
            background: #f8d7da;
            color: #721c24;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .matches-grid {
                grid-template-columns: 1fr;
            }
            
            body {
                padding: 10px;
            }
            
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($nome_torneo); ?></h1>
            <p>Fase a eliminazione diretta</p>
        </header>
        
        <div class="summary">
            <p><strong>Squadre qualificate:</strong> <?php echo count($qualificate); ?></p>
            <p><strong>Gironi:</strong> <?php echo count($gironi); ?></p>
            <?php if ($da_aggiungere > 0 && count($terze) > 0): ?>
            <p><strong>Migliori terze qualificate:</strong> <?php echo min($da_aggiungere, count($terze)); ?></p>
            <?php endif; ?>
        </div>

        <h2>Abbinamenti fase finale</h2>
        
        <div class="matches-grid">
            <?php
            // Genera partite 
            $matchCount = 0;
            $insertedMatches = [];
            
            for ($i = 0; $i < count($qualificate); $i += 2) {
                if (!isset($qualificate[$i + 1])) break;
                
                $s1 = $qualificate[$i];
                $s2 = $qualificate[$i + 1];
                $matchCount++;
                
                // Inserisci partita nel DB
                $stmt = $conn->prepare("INSERT INTO partite (id_torneo, id_squadra1, id_squadra2, fase_finale) 
                                       VALUES (?, ?, ?, 1)");
                $stmt->bind_param("iii", $id_torneo, $s1['Id_squadra'], $s2['Id_squadra']);
                $stmt->execute();
                $insertedMatches[] = $conn->insert_id;
                
                // Visualizza la partita
                echo '<div class="match-card">';
                echo '<div class="match-header">Match #' . $matchCount . '</div>';
                echo '<div class="team">';
                echo '<div class="team-info">';
                echo '<div class="team-name">' . htmlspecialchars($s1['nome']) . '</div>';
                echo '<div class="team-stats">Punti: ' . $s1['punti'] . ' | GF: ' . $s1['gol_fatti'] . ' | GS: ' . $s1['gol_subiti'] . '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="vs"><i class="fas fa-trophy"></i> VS <i class="fas fa-trophy"></i></div>';
                
                echo '<div class="team">';
                echo '<div class="team-info">';
                echo '<div class="team-name">' . htmlspecialchars($s2['nome']) . '</div>';
                echo '<div class="team-stats">Punti: ' . $s2['punti'] . ' | GF: ' . $s2['gol_fatti'] . ' | GS: ' . $s2['gol_subiti'] . '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            
            if ($matchCount == 0) {
                echo '<div class="alert">Non ci sono abbastanza squadre per creare abbinamenti.</div>';
            }
            ?>
        </div>
        
        <div style="text-align: center;">
            <a href="mostra_calendario.php?id_torneo=<?php echo $id_torneo; ?>" class="btn">
                <i class="fas fa-calendar-alt"></i> Vai al tabellone completo
            </a>
        </div>
        
        <div class="footer">
            <p>Sistema generazione fase finale | Sportify</p>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>