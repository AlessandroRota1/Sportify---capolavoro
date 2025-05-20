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
$id_utente = $_SESSION['id_utente'];

// Verifica che sia il creatore del torneo
$check = $conn->query("SELECT * FROM tornei WHERE Id_torneo = $id_torneo AND id_utente = $id_utente");
if ($check->num_rows === 0) {
    die("Non sei autorizzato a generare il calendario per questo torneo.");
}

$rowTorneo = $check->fetch_assoc();
$tipologia = $rowTorneo['tipologia'];

// Recupera le squadre
$squadre = [];
$res = $conn->query("SELECT Id_squadra, girone FROM squadre WHERE id_torneo = $id_torneo");

$squadre_per_girone = [];

while ($row = $res->fetch_assoc()) {
    $girone = $row['girone'] ?? 'Unico';
    $squadre_per_girone[$girone][] = $row['Id_squadra'];
}

// Per ogni girone, genera partite tra le squadre dello stesso girone
foreach ($squadre_per_girone as $girone => $squadre) {
    $num_squadre = count($squadre);

    for ($i = 0; $i < $num_squadre - 1; $i++) {
        for ($j = $i + 1; $j < $num_squadre; $j++) {
            $id_squadra1 = $squadre[$i];
            $id_squadra2 = $squadre[$j];

            // Inserisci la partita nel DB
            $sql = "INSERT INTO partite (id_torneo, id_squadra1, id_squadra2) VALUES ($id_torneo, $id_squadra1, $id_squadra2)";
            $conn->query($sql);
        }
    }
}
while ($row = $res->fetch_assoc()) {
    $squadre[] = ['id' => $row['Id_squadra'], 'nome' => $row['nome']];
}

if (count($squadre) < 2) {
    die("Servono almeno 2 squadre per generare un calendario.");
}

$partite_inserite = 0;

switch ($tipologia) {
    case 'Girone Unico':
        // Round-robin semplice
        for ($i = 0; $i < count($squadre); $i++) {
            for ($j = $i + 1; $j < count($squadre); $j++) {
                $s1 = $squadre[$i]['id'];
                $s2 = $squadre[$j]['id'];
                $conn->query("INSERT INTO partite (id_squadra1, id_squadra2, gol_1, gol_2, data_partita, orario, id_torneo)
                              VALUES ($s1, $s2, 0, 0, NULL, NULL, $id_torneo)");
                $partite_inserite++;
            }
        }
        break;

    case 'Gironi Multipli':
        // Dividi le squadre in 2 gironi (o più se necessario)
        $numero_gironi = 2;
        $gironi = array_chunk($squadre, ceil(count($squadre) / $numero_gironi));

        foreach ($gironi as $girone) {
            for ($i = 0; $i < count($girone); $i++) {
                for ($j = $i + 1; $j < count($girone); $j++) {
                    $s1 = $girone[$i]['id'];
                    $s2 = $girone[$j]['id'];
                    $conn->query("INSERT INTO partite (id_squadra1, id_squadra2, gol_1, gol_2, data_partita, orario, id_torneo)
                                  VALUES ($s1, $s2, 0, 0, NULL, NULL, $id_torneo)");
                    $partite_inserite++;
                }
            }
        }
        break;

    case 'Eliminazione Diretta':
        // Sorteggia le squadre in accoppiamenti
        shuffle($squadre);
        for ($i = 0; $i < count($squadre) - 1; $i += 2) {
            $s1 = $squadre[$i]['id'];
            $s2 = $squadre[$i + 1]['id'];
            $conn->query("INSERT INTO partite (id_squadra1, id_squadra2, gol_1, gol_2, data_partita, orario, id_torneo)
                          VALUES ($s1, $s2, 0, 0, NULL, NULL, $id_torneo)");
            $partite_inserite++;
        }
        // Gestione del bye se il numero è dispari
        if (count($squadre) % 2 !== 0) {
            $s1 = $squadre[count($squadre) - 1]['id'];
            $conn->query("INSERT INTO partite (id_squadra1, id_squadra2, gol_1, gol_2, data_partita, orario, id_torneo)
                          VALUES ($s1, NULL, 0, 0, NULL, NULL, $id_torneo)");
            $partite_inserite++;
        }
        break;

    default:
        die("Tipologia di torneo non supportata.");
}

$partite = [];
$res = $conn->query("SELECT p.*, s1.nome AS squadra1, s2.nome AS squadra2 
                     FROM partite p
                     LEFT JOIN squadre s1 ON p.id_squadra1 = s1.Id_squadra
                     LEFT JOIN squadre s2 ON p.id_squadra2 = s2.Id_squadra
                     WHERE p.id_torneo = $id_torneo
                     ORDER BY p.id_partita ASC");

while ($row = $res->fetch_assoc()) {
    $partite[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Calendario Generato</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #1976D2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }
        th {
            background-color: #e3f2fd;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #1976D2;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .btn:hover {
            background-color: #0D47A1;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📅 Calendario Generato con Successo</h1>
    <p style="text-align:center;">Sono state create <strong><?= $partite_inserite ?></strong> partite.</p>

    <table>
        <tr>
            <th>#</th>
            <th>Squadra 1</th>
            <th>VS</th>
            <th>Squadra 2</th>
            <th>Data</th>
            <th>Ora</th>
            <th>Risultato</th>
        </tr>
        <?php foreach ($partite as $i => $p): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($p['squadra1']) ?></td>
                <td>vs</td>
                <td><?= htmlspecialchars($p['squadra2'] ?? '-') ?></td>
                <td><?= $p['data_partita'] ?? '-' ?></td>
                <td><?= $p['orario'] ?? '-' ?></td>
                <td><?= $p['gol_1'] . ' - ' . $p['gol_2'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div style="text-align: center;">
        <a href="index.php" class="btn">🏠 Torna alla Home</a>
    </div>
</div>

</body>
</html>
