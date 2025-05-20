<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

if (!isset($_GET['id_torneo'])) {
    die("Torneo non specificato.");
}

$id_torneo = (int)$_GET['id_torneo'];

// Recupera nome e tipologia del torneo
$resTorneo = $conn->query("SELECT nome, tipologia FROM tornei WHERE Id_torneo = $id_torneo");
$torneo_data = $resTorneo->fetch_assoc();
$nome_torneo = $torneo_data['nome'] ?? 'Torneo';
$tipologia = $torneo_data['tipologia'] ?? 'Girone Unico';

if ($tipologia === "Eliminazione Diretta") {
    echo "<h2 style='text-align:center;color:#e74c3c;'>Questo torneo è a eliminazione diretta. Non è prevista una classifica.</h2>";
    echo "<div style='text-align:center; margin-top: 20px;'><a href='mostra_calendario.php?id_torneo=$id_torneo' class='btn'>Visualizza Tabellone</a></div>";
    exit;
}

// Parametri di ordinamento
$order_by = $_GET['order_by'] ?? 'punti';
$direction = ($_GET['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

// Inizializza struttura per gironi
$gironi = [];

// Recupera squadre con gironi
$sql = "SELECT Id_squadra, nome, girone FROM squadre WHERE id_torneo = $id_torneo ORDER BY girone ASC, nome ASC";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $girone = $row['girone'] ?: 'Girone Unico';
    $id_squadra = $row['Id_squadra'];

    $gironi[$girone][$id_squadra] = [
        'nome' => $row['nome'],
        'giocate' => 0,
        'vittorie' => 0,
        'pareggi' => 0,
        'sconfitte' => 0,
        'punti' => 0,
        'gol_fatti' => 0,
        'gol_subiti' => 0,
        'diff' => 0
    ];
}

// Recupera partite giocate
$sql = "SELECT * FROM partite WHERE id_torneo = $id_torneo AND gol_1 IS NOT NULL AND gol_2 IS NOT NULL";
$resPartite = $conn->query($sql);
while ($p = $resPartite->fetch_assoc()) {
    $s1 = $p['id_squadra1'];
    $s2 = $p['id_squadra2'];
    $g1 = $p['gol_1'];
    $g2 = $p['gol_2'];

    foreach ($gironi as &$squadre) {
        if (isset($squadre[$s1]) && isset($squadre[$s2])) {
            $squadre[$s1]['giocate']++;
            $squadre[$s2]['giocate']++;

            $squadre[$s1]['gol_fatti'] += $g1;
            $squadre[$s1]['gol_subiti'] += $g2;
            $squadre[$s2]['gol_fatti'] += $g2;
            $squadre[$s2]['gol_subiti'] += $g1;

            if ($g1 > $g2) {
                $squadre[$s1]['vittorie']++;
                $squadre[$s1]['punti'] += 3;
                $squadre[$s2]['sconfitte']++;
            } elseif ($g2 > $g1) {
                $squadre[$s2]['vittorie']++;
                $squadre[$s2]['punti'] += 3;
                $squadre[$s1]['sconfitte']++;
            } else {
                $squadre[$s1]['pareggi']++;
                $squadre[$s2]['pareggi']++;
                $squadre[$s1]['punti']++;
                $squadre[$s2]['punti']++;
            }
        }
    }
}
unset($squadre); // buon uso della memoria

// Calcola differenza reti
foreach ($gironi as &$squadre) {
    foreach ($squadre as &$s) {
        $s['diff'] = $s['gol_fatti'] - $s['gol_subiti'];
    }
}
unset($s);

// Funzione di ordinamento classifica
function ordina_classifica($a, $b, $campo, $direction) {
    if ($a[$campo] == $b[$campo]) {
        if ($campo !== 'punti' && $a['punti'] != $b['punti']) {
            return ($direction === 'asc') ? $a['punti'] - $b['punti'] : $b['punti'] - $a['punti'];
        }
        if ($a['diff'] != $b['diff']) {
            return ($direction === 'asc') ? $a['diff'] - $b['diff'] : $b['diff'] - $a['diff'];
        }
        return ($direction === 'asc') ? $a['gol_fatti'] - $b['gol_fatti'] : $b['gol_fatti'] - $a['gol_fatti'];
    }
    return ($direction === 'asc') ? $a[$campo] - $b[$campo] : $b[$campo] - $a[$campo];
}

// Ordina squadre per ogni girone
foreach ($gironi as &$squadre) {
    uasort($squadre, function ($a, $b) use ($order_by, $direction) {
        return ordina_classifica($a, $b, $order_by, $direction);
    });
}
unset($squadre);

// Generazione URL e frecce
function sort_url($campo, $current_order, $current_direction) {
    $direction = ($current_order === $campo && $current_direction === 'desc') ? 'asc' : 'desc';
    return "?id_torneo=" . $_GET['id_torneo'] . "&order_by=" . $campo . "&direction=" . $direction;
}

function get_sort_arrow($campo, $current_order, $current_direction) {
    if ($current_order !== $campo) return '<i class="fas fa-sort"></i>';
    return ($current_direction === 'asc') ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classifica - <?= htmlspecialchars($nome_torneo) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --text-color: #333;
            --text-light: #6c757d;
            --border-color: #dee2e6;
            --success-bg: #d4edda;
            --success-text: #155724;
            --hover-row: #f1f8ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            text-align: center;
            border-bottom: 5px solid var(--secondary-color);
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header p {
            font-size: 1.1rem;
            margin-top: 0.5rem;
            opacity: 0.9;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: var(--light-bg);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.4rem;
            color: var(--primary-color);
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--light-bg);
            font-weight: 600;
            color: var(--text-color);
            position: sticky;
            top: 0;
        }

        th a {
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: color 0.2s;
        }

        th a:hover {
            color: var(--primary-color);
        }

        tr:hover {
            background-color: var(--hover-row);
        }

        .position {
            font-weight: bold;
            width: 40px;
        }

        .top-3 {
            background-color: var(--success-bg);
            color: var(--success-text);
        }

        .team-name {
            text-align: left;
            font-weight: 500;
        }
        
        .stats {
            font-weight: 500;
        }

        .highlight {
            font-weight: bold;
            color: var(--primary-color);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
            font-weight: 500;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: var(--secondary-color);
        }

        .btn i {
            margin-right: 5px;
        }

        .actions {
            text-align: center;
            margin: 2rem 0;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 0.9rem;
            }
            
            .hide-mobile {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-trophy"></i> <?= htmlspecialchars($nome_torneo) ?></h1>
            <p>Classifica aggiornata del torneo</p>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Classifica</h2>
                <div class="legend">
                    <small>PG: Partite giocate | V: Vittorie | P: Pareggi | S: Sconfitte | GF: Gol fatti | GS: Gol subiti | DR: Differenza reti</small>
                </div>
            </div>
            
            <?php foreach ($gironi as $nome_girone => $squadre): ?>
    <div class="card">
        <div class="card-header">
            <h2>Classifica - <?= htmlspecialchars($nome_girone) ?></h2>
            <div class="legend">
                <small>PG: Partite giocate | V: Vittorie | P: Pareggi | S: Sconfitte | GF: Gol fatti | GS: Gol subiti | DR: Differenza reti</small>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="position">Pos</th>
                        <th class="team-name">Squadra</th>
                        <th>PG</th>
                        <th class="hide-mobile">V</th>
                        <th class="hide-mobile">P</th>
                        <th class="hide-mobile">S</th>
                        <th class="highlight">Punti</th>
                        <th>GF</th>
                        <th>GS</th>
                        <th>DR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pos = 1; foreach ($squadre as $s): ?>
                        <tr class="<?= ($pos <= 2) ? 'top-3' : '' ?>">
                            <td class="position"><?= $pos++ ?></td>
                            <td class="team-name"><?= htmlspecialchars($s['nome']) ?></td>
                            <td><?= $s['giocate'] ?></td>
                            <td class="hide-mobile"><?= $s['vittorie'] ?></td>
                            <td class="hide-mobile"><?= $s['pareggi'] ?></td>
                            <td class="hide-mobile"><?= $s['sconfitte'] ?></td>
                            <td class="stats highlight"><?= $s['punti'] ?></td>
                            <td class="stats"><?= $s['gol_fatti'] ?></td>
                            <td class="stats"><?= $s['gol_subiti'] ?></td>
                            <td class="stats"><?= $s['diff'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

        </div>

        <div class="actions">
            <a href="index.php" class="btn"><i class="fas fa-home"></i> Torna alla Home</a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Evidenzia la colonna ordinata
        const currentOrder = '<?= $order_by ?>';
        const headers = document.querySelectorAll('th a');
        
        headers.forEach(header => {
            const headerUrl = header.getAttribute('href');
            if (headerUrl.includes(`order_by=${currentOrder}`)) {
                header.classList.add('highlight');
            }
        });
    });
    </script>
</body>
</html>