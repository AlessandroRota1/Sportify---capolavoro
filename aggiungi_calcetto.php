<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    header('Location: login.php');
    exit();
}

// Parametro pre-selezionato (da GET o da POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campo'])) {
    $id_campo_selezionato = (int)$_POST['campo'];
} else {
    $id_campo_selezionato = isset($_GET['id_campo']) ? (int)$_GET['id_campo'] : null;
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$messaggio = "";

// Se arrivo in POST, inserisco il calcetto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_ora    = $conn->real_escape_string($_POST['data_ora']);
    $visibilita  = (int)$_POST['visibilita'];
    $id_utente   = (int)$_SESSION['id_utente'];
    $id_campo    = (int)$_POST['campo'];

    // Il creatore è già conteggiato nel posti_occupati iniziali
    $posti_occupati = 1;

    $sql = "
        INSERT INTO calcetti 
          (id_campo, data_ora, posti_occupati, visibilita, id_utente)
        VALUES
          ($id_campo, '$data_ora', $posti_occupati, $visibilita, $id_utente)
    ";
    if ($conn->query($sql) === TRUE) {
        $messaggio = "✅ Calcetto creato con successo!";
        // Dopo creazione, faccio sparire la selezione
        $id_campo_selezionato = null;
    } else {
        $messaggio = "❌ Errore durante la creazione: " . $conn->error;
    }
}

// Prelevo tutti i campi per la select
$sqlCampi   = "SELECT id_campo, indirizzo FROM campi";
$resultCampi = $conn->query($sqlCampi);

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea un Calcetto - Sportify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2E7D32;
            --primary-hover: #1B5E20;
            --secondary-color: #1976D2;
            --light-bg: #F5F9F5;
            --dark-text: #333;
            --light-text: #fff;
            --border-radius: 12px;
            --box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            --input-shadow: 0 2px 5px rgba(0,0,0,0.08);
            --success-color: #4CAF50;
            --error-color: #F44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 550px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h1 {
            color: var(--secondary-color);
            font-size: 24px;
            font-weight: 600;
        }
        
        .message {
            text-align: center;
            margin: 20px 0;
            padding: 12px;
            border-radius: var(--border-radius);
            font-weight: 500;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .message.success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .message.error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .form-container {
            background: #fff;
            border-radius: var(--border-radius);
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
            font-size: 15px;
        }
        
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: var(--input-shadow);
        }
        
        input[type="datetime-local"]:focus,
        select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
            background-color: #fff;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 20px;
            background-color: var(--primary-color);
            color: var(--light-text);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--input-shadow);
            width: 100%;
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-home {
            background-color: #f5f5f5;
            color: #555;
            padding: 10px;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: auto;
        }
        
        .btn-home:hover {
            background-color: #e9e9e9;
        }
        
        .icon {
            margin-right: 5px;
        }
        
        .campo-icon {
            font-size: 24px;
            color: var(--secondary-color);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive styles */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
                margin: 20px auto;
            }
            
            h1 {
                font-size: 20px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .btn-home {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">
                <i class="fas fa-futbol campo-icon"></i>
                <h1>Crea un nuovo Calcetto</h1>
            </div>
            <a href="index.php" class="btn btn-home">
                <i class="fas fa-home"></i>
                Torna alla Home
            </a>
        </div>

        <?php if ($messaggio): ?>
            <?php
            $messageClass = strpos($messaggio, "✅") !== false ? "success" : "error";
            ?>
            <div class="message <?= $messageClass ?>">
                <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="aggiungi_calcetto.php" method="POST">
                <div class="form-group">
                    <label for="data_ora">
                        <i class="fas fa-calendar-alt icon"></i> Data e Ora:
                    </label>
                    <input type="datetime-local" id="data_ora" name="data_ora" required>
                </div>

                <div class="form-group">
                    <label for="campo">
                        <i class="fas fa-map-marker-alt icon"></i> Seleziona il campo:
                    </label>
                    <select id="campo" name="campo" required>
                        <option value="">-- Scegli un campo --</option>
                        <?php if ($resultCampi && $resultCampi->num_rows): ?>
                            <?php while ($c = $resultCampi->fetch_assoc()): ?>
                                <option value="<?= $c['id_campo'] ?>"
                                    <?= $c['id_campo'] === $id_campo_selezionato ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['indirizzo']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">Nessun campo disponibile</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="visibilita">
                        <i class="fas fa-eye icon"></i> Visibilità:
                    </label>
                    <select id="visibilita" name="visibilita" required>
                        <option value="1">Pubblico</option>
                        <option value="0">Privato</option>
                    </select>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-plus-circle"></i> Crea Calcetto
                </button>
            </form>
        </div>
    </div>

    <script>
        // Imposta la data minima al giorno corrente
        const today = new Date();
        const formattedDate = today.toISOString().slice(0, 16);
        document.getElementById('data_ora').min = formattedDate;
        
        // Focus sul primo campo
        window.onload = function() {
            document.getElementById('data_ora').focus();
        };
    </script>
</body>
</html>