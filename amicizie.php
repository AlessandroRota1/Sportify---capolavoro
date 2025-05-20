<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

$id_utente = $_SESSION['id_utente'] ?? 0;
$messaggio = "";

// INVIO richiesta amicizia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nickname'])) {
    $nickname = $conn->real_escape_string($_POST['nickname']);

    $res = $conn->query("SELECT id_utente FROM utenti WHERE nickname = '$nickname'");
    if ($res->num_rows) {
        $row = $res->fetch_assoc();
        $destinatario = $row['id_utente'];

        if ($destinatario == $id_utente) {
            $messaggio = "Non puoi inviare una richiesta a te stesso.";
        } else {
            $esiste = $conn->query("
                SELECT 1 FROM amicizie
                WHERE 
                    (id_mittente = $id_utente AND id_destinatario = $destinatario)
                    OR
                    (id_mittente = $destinatario AND id_destinatario = $id_utente)
            ");
            if ($esiste->num_rows) {
                $messaggio = "Richiesta già esistente o utente già amico.";
            } else {
                $data = date('Y-m-d');
                $conn->query("INSERT INTO amicizie (id_mittente, id_destinatario, stato, data_richiesta)
                              VALUES ($id_utente, $destinatario, 'in_attesa', '$data')");
                $messaggio = "Richiesta inviata!";
            }
        }
    } else {
        $messaggio = "Utente non trovato.";
    }
}

// ACCETTA o RIFIUTA richiesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['azione'], $_POST['richiesta_id'])) {
    $azione = $_POST['azione'];
    $idRichiesta = (int)$_POST['richiesta_id'];
    $nuovoStato = ($azione === 'accetta') ? 'accettata' : 'rifiutata';

    $conn->query("UPDATE amicizie SET stato = '$nuovoStato' WHERE id = $idRichiesta AND id_destinatario = $id_utente");
}

// Elenco richieste ricevute
$richieste = $conn->query("
    SELECT a.id, u.nickname, u.nome, u.cognome
    FROM amicizie a
    JOIN utenti u ON u.id_utente = a.id_mittente
    WHERE a.id_destinatario = $id_utente AND a.stato = 'in_attesa'
");

// Elenco amici
$amici = $conn->query("
    SELECT u.id_utente, u.nickname, u.nome, u.cognome
    FROM amicizie a
    JOIN utenti u ON 
        (u.id_utente = a.id_mittente AND a.id_destinatario = $id_utente)
        OR (u.id_utente = a.id_destinatario AND a.id_mittente = $id_utente)
    WHERE a.stato = 'accettata' AND u.id_utente != $id_utente
");

// Recupero calcetti creati dall'utente
$calcetti = $conn->query("
    SELECT id_calcetto, data_ora 
    FROM calcetti 
    WHERE id_utente = $id_utente
");

$lista_calcetti = [];
while ($c = $calcetti->fetch_assoc()) {
    $lista_calcetti[] = $c;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Amicizie</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --secondary-dark: #27ae60;
            --danger: #e74c3c;
            --danger-dark: #c0392b;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #2ecc71;
            --warning: #f39c12;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f8fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        h1 {
            color: var(--primary);
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        h2 {
            color: var(--dark);
            margin: 1.5rem 0 1rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-links a {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary);
            color: white;
            padding: 0.6rem 1rem;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }

        .top-links a:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .message {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success);
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--success);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .search-form {
            background-color: var(--light);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        input[type="text"], 
        select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        input[type="text"]:focus, 
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
        }

        .btn,
        button[type="submit"] {
            display: inline-block;
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn:hover,
        button[type="submit"]:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-success {
            background: var(--secondary);
        }

        .btn-success:hover {
            background: var(--secondary-dark);
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger-dark);
        }

        .section {
            margin: 2rem 0;
        }

        .friends-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-light);
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        .friend-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .friend-avatar {
            width: 50px;
            height: 50px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        .friend-details {
            flex: 1;
        }

        .friend-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .friend-username {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .friend-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .request-card {
            border-left: 4px solid var(--warning);
        }

        .inline-form {
            margin-top: 1rem;
        }

        .divider {
            height: 1px;
            background-color: var(--gray-light);
            margin: 1rem 0;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
                margin: 20px 10px;
            }
            
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .top-links {
                width: 100%;
                margin-top: 1rem;
            }
            
            .friends-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-users"></i> Gestione Amicizie</h1>
            <div class="top-links">
                <a href="index.php"><i class="fas fa-home"></i> Torna alla Home</a>
            </div>
        </header>

        <?php if ($messaggio): ?>
            <div class="message">
                <i class="fas fa-info-circle"></i> <?= $messaggio ?>
            </div>
        <?php endif; ?>

        <div class="search-form">
            <form method="POST">
                <div class="form-group">
                    <label for="nickname"><i class="fas fa-search"></i> Cerca un amico per nickname:</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="nickname" id="nickname" placeholder="Inserisci un nickname" required>
                        <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Invia Richiesta</button>
                    </div>
                </div>
            </form>
        </div>

        <section class="section">
            <h2><i class="fas fa-envelope"></i> Richieste ricevute</h2>
            
            <?php if ($richieste->num_rows): ?>
                <div class="friends-list">
                    <?php while ($r = $richieste->fetch_assoc()): ?>
                        <div class="card request-card">
                            <div class="friend-info">
                                <div class="friend-avatar">
                                    <?= strtoupper(substr($r['nome'], 0, 1)) ?>
                                </div>
                                <div class="friend-details">
                                    <div class="friend-name"><?= htmlspecialchars($r['nome'] . ' ' . $r['cognome']) ?></div>
                                    <div class="friend-username">@<?= htmlspecialchars($r['nickname']) ?></div>
                                </div>
                            </div>
                            
                            <form method="POST" class="friend-actions">
                                <input type="hidden" name="richiesta_id" value="<?= $r['id'] ?>">
                                <button type="submit" name="azione" value="accetta" class="btn btn-small btn-success">
                                    <i class="fas fa-check"></i> Accetta
                                </button>
                                <button type="submit" name="azione" value="rifiuta" class="btn btn-small btn-danger">
                                    <i class="fas fa-times"></i> Rifiuta
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    <p>Non hai richieste di amicizia in attesa</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="section">
            <h2><i class="fas fa-user-friends"></i> I tuoi amici</h2>
            
            <?php if ($amici->num_rows): ?>
                <div class="friends-list">
                    <?php while ($a = $amici->fetch_assoc()): ?>
                        <div class="card">
                            <div class="friend-info">
                                <div class="friend-avatar">
                                    <?= strtoupper(substr($a['nome'], 0, 1)) ?>
                                </div>
                                <div class="friend-details">
                                    <div class="friend-name"><?= htmlspecialchars($a['nome'] . ' ' . $a['cognome']) ?></div>
                                    <div class="friend-username">@<?= htmlspecialchars($a['nickname']) ?></div>
                                </div>
                            </div>

                            <?php if (count($lista_calcetti)): ?>
                                <div class="divider"></div>
                                <form action="invia_invito.php" method="POST" class="inline-form">
                                    <input type="hidden" name="id_destinatario" value="<?= $a['id_utente'] ?>">
                                    <div class="form-group">
                                        <label for="id_calcetto_<?= $a['id_utente'] ?>"><i class="fas fa-futbol"></i> Invita a un tuo calcetto:</label>
                                        <div style="display: flex; gap: 10px;">
                                            <select name="id_calcetto" id="id_calcetto_<?= $a['id_utente'] ?>" required>
                                                <option value="">-- Seleziona --</option>
                                                <?php foreach ($lista_calcetti as $c): ?>
                                                    <option value="<?= $c['id_calcetto'] ?>"><?= $c['data_ora'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-small btn-success">
                                                <i class="fas fa-envelope"></i> Invita
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="divider"></div>
                                <p style="color: var(--gray); font-style: italic;">
                                    <i class="fas fa-info-circle"></i> Non hai creato nessun calcetto da condividere
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-plus" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    <p>Non hai ancora amici nella tua lista</p>
                    <p>Cerca qualcuno per iniziare!</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>