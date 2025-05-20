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
$id_utente = $_SESSION['id_utente'] ?? 0;

// Gestione inserimento/modifica risultato + data/ora
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salva_risultato'])) {
    $id_partita = (int)$_POST['id_partita'];
    $gol_1 = (int)$_POST['gol_1'];
    $gol_2 = (int)$_POST['gol_2'];
    $data_partita = $_POST['data_partita'] ?? null;
    $orario = $_POST['orario'] ?? null;

    $stmt = $conn->prepare("UPDATE partite SET gol_1 = ?, gol_2 = ?, data_partita = ?, orario = ? WHERE id_partita = ?");
    $stmt->bind_param("iissi", $gol_1, $gol_2, $data_partita, $orario, $id_partita);
    
    if ($stmt->execute()) {
        $success_message = "Dati partita aggiornati con successo!";
    } else {
        $error_message = "Errore nell'aggiornamento: " . $conn->error;
    }
}

// Verifica se l'utente è il creatore del torneo
$resCheck = $conn->query("SELECT id_utente, nome FROM tornei WHERE Id_torneo = $id_torneo");
$torneo = $resCheck->fetch_assoc();
$isCreatore = $torneo && $torneo['id_utente'] == $id_utente;
$nome_torneo = $torneo['nome'] ?? "Torneo";

// Recupera partite
$partite = [];
$res = $conn->query("SELECT p.*, s1.nome AS squadra1, s2.nome AS squadra2 
                     FROM partite p
                     JOIN squadre s1 ON p.id_squadra1 = s1.Id_squadra
                     JOIN squadre s2 ON p.id_squadra2 = s2.Id_squadra
                     WHERE p.id_torneo = $id_torneo
                     ORDER BY p.data_partita IS NULL, p.data_partita ASC, p.orario ASC");
while ($row = $res->fetch_assoc()) {
    $partite[] = $row;
}

// Recupera informazioni sul torneo
$resTorneo = $conn->query("SELECT nome, data_inizio, data_fine FROM tornei WHERE Id_torneo = $id_torneo");
$torneoInfo = $resTorneo->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - <?= htmlspecialchars($nome_torneo) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-light: #f1f5f9;
            --gray: #64748b;
            --gray-dark: #334155;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: var(--gray-dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1100px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .header h1 {
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 32px;
        }
        
        .torneo-info {
            color: var(--gray);
            font-size: 16px;
            text-align: center;
            background-color: var(--primary-light);
            padding: 8px 16px;
            border-radius: 8px;
            margin-top: 8px;
        }
        
        .message {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        
        .error {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 25px 0;
            box-shadow: 0 2px 3px rgba(0,0,0,0.02);
            border-radius: 10px;
            overflow: hidden;
        }
        
        th, td {
            padding: 14px 18px;
            text-align: center;
            vertical-align: middle;
        }
        
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        tr:hover {
            background-color: #f1f5f9;
        }
        
        td {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .team-name {
            font-weight: 600;
            color: var(--gray-dark);
        }
        
        form.inline {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        
        input[type="number"],
        input[type="date"],
        input[type="time"] {
            padding: 10px;
            text-align: center;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            transition: border-color 0.15s ease-in-out;
            width: 70px;
        }
        
        input[type="date"],
        input[type="time"] {
            width: 140px;
        }
        
        input[type="number"]:focus,
        input[type="date"]:focus,
        input[type="time"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .vs {
            font-weight: bold;
            font-size: 18px;
            margin: 0 8px;
            color: var(--gray);
        }
        
        .btn {
            padding: 10px 16px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .back {
            text-align: center;
            margin-top: 30px;
        }
        
        .back a {
            text-decoration: none;
        }
        
        .score-box {
            display: inline-flex;
            align-items: center;
            background-color: #f8fafc;
            padding: 8px 12px;
            border-radius: 8px;
            min-width: 90px;
            justify-content: center;
            border: 1px solid #e2e8f0;
        }
        
        .no-result {
            color: var(--gray);
            font-style: italic;
        }

        .date-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
        }
        
        .date-info i {
            margin-right: 5px;
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px 10px;
                margin: 20px 10px;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 14px;
            }
            
            input[type="date"],
            input[type="time"] {
                width: 120px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-calendar-alt"></i> Calendario <?= htmlspecialchars($nome_torneo) ?></h1>
        
        <?php if (!empty($torneoInfo)): ?>
        <div class="torneo-info">
            <i class="fas fa-trophy"></i> <?= htmlspecialchars($torneoInfo['nome']) ?> 
            <i class="fas fa-calendar-day"></i> Dal <?= date('d/m/Y', strtotime($torneoInfo['data_inizio'])) ?> 
            al <?= date('d/m/Y', strtotime($torneoInfo['data_fine'])) ?>
        </div>
        <div style="text-align: center; margin-top: 20px;">
    <a href="genera_pdf_calendario.php?id_torneo=<?= $id_torneo ?>" class="btn" target="_blank">
        <i class="fas fa-file-pdf"></i> Esporta in PDF
    </a>
</div>

        <?php endif; ?>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> <?= $success_message ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
        </div>
    <?php endif; ?>

    <?php if (empty($partite)): ?>
        <div class="message">
            <i class="fas fa-info-circle"></i> Nessuna partita trovata per questo torneo.
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Squadra 1</th>
                    <th>Risultato</th>
                    <th>Squadra 2</th>
                    <th>Data</th>
                    <th>Ora</th>
                    <?php if ($isCreatore): ?><th>Azioni</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($partite as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="team-name"><?= htmlspecialchars($p['squadra1']) ?></td>
                        <td>
                            <?php if ($isCreatore): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="id_partita" value="<?= $p['id_partita'] ?>">
                                    <input type="number" name="gol_1" min="0" value="<?= $p['gol_1'] ?? 0 ?>" required>
                                    <span class="vs">-</span>
                                    <input type="number" name="gol_2" min="0" value="<?= $p['gol_2'] ?? 0 ?>" required>
                            <?php else: ?>
                                <div class="score-box">
                                    <?php if (isset($p['gol_1']) && isset($p['gol_2'])): ?>
                                        <span class="result"><?= $p['gol_1'] ?> - <?= $p['gol_2'] ?></span>
                                    <?php else: ?>
                                        <span class="no-result">- - -</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="team-name"><?= htmlspecialchars($p['squadra2']) ?></td>
                        <td>
                            <?php if ($isCreatore): ?>
                                <input type="date" name="data_partita" value="<?= $p['data_partita'] ?>">
                            <?php else: ?>
                                <div class="date-info">
                                    <?php if (!empty($p['data_partita'])): ?>
                                        <span><i class="far fa-calendar"></i> <?= date('d/m/Y', strtotime($p['data_partita'])) ?></span>
                                    <?php else: ?>
                                        <span class="no-result"><i class="far fa-calendar"></i> Da definire</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isCreatore): ?>
                                <input type="time" name="orario" value="<?= $p['orario'] ?>">
                            <?php else: ?>
                                <div class="date-info">
                                    <?php if (!empty($p['orario'])): ?>
                                        <span><i class="far fa-clock"></i> <?= date('H:i', strtotime($p['orario'])) ?></span>
                                    <?php else: ?>
                                        <span class="no-result"><i class="far fa-clock"></i> Da definire</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php if ($isCreatore): ?>
                            <td>
                                <button type="submit" name="salva_risultato" class="btn btn-success">
                                    <i class="fas fa-save"></i> Salva
                                </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="back">
        <a href="index.php" class="btn">
            <i class="fas fa-home"></i> Torna alla Home
        </a>
    </div>
</div>

</body>
</html>