<?php
session_start();

// Connessione al database
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$messaggio = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1) Indirizzo "pulito" per il DB
    $indirizzo_raw = mysqli_real_escape_string($conn, $_POST['posizione']);

    // 2) Altri campi
    $terreno     = mysqli_real_escape_string($conn, $_POST['terreno']);
    $spogliatoi  = (int) $_POST['spogliatoi'];
    $n_giocatori = (int) $_POST['n_giocatori'];
    $docce       = (int) $_POST['docce'];
    $costo       = (float) $_POST['costo'];
    $id_utente   = (int) $_SESSION['id_utente'];

    // 3) URL‑encode solo per geocoding
    $indirizzo_encoded = urlencode($indirizzo_raw);
    $geocodeUrl = "https://nominatim.openstreetmap.org/search?format=json&q={$indirizzo_encoded}";

    // Intestazione richiesta a Nominatim
    $options = [
        "http" => [
            "header" => "User-Agent: SportifyApp/1.0 (contatto@tuodominio.com)"
        ]
    ];
    $context         = stream_context_create($options);
    $geocodeResponse = @file_get_contents($geocodeUrl, false, $context);
    $geocodeData     = $geocodeResponse ? json_decode($geocodeResponse) : null;

    if ($geocodeData && isset($geocodeData[0])) {
        $lat = $geocodeData[0]->lat;
        $lng = $geocodeData[0]->lon;

        // 4) Inserimento nel DB con l'indirizzo "pulito"
        $sql = "INSERT INTO campi
                (indirizzo, terreno, spogliatoi, n_giocatori, docce, costo, id_utente, latitudine, longitudine)
                VALUES
                ('$indirizzo_raw', '$terreno', $spogliatoi, $n_giocatori, $docce, $costo, $id_utente, '$lat', '$lng')";

        if ($conn->query($sql) === TRUE) {
            $messaggio = "Campo aggiunto con successo!";
        } else {
            $messaggio = "Errore durante l'aggiunta del campo: " . $conn->error;
        }
    } else {
        $messaggio = "Indirizzo non valido o non geocodificabile.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Campo - Sportify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #38b000;
            --primary-hover: #2d9200;
            --secondary-color: #3a86ff;
            --light-bg: #f8f9fa;
            --dark-text: #333;
            --light-text: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            width: 90%;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }
        
        .btn-home {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-home:hover {
            background-color: #2a75e6;
            transform: translateY(-2px);
        }
        
        .btn-home i {
            margin-right: 8px;
        }
        
        h1 {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 30px;
            color: var(--dark-text);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .form-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .form-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .form-body {
            padding: 25px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .form-group {
            flex: 1 0 calc(50% - 20px);
            margin: 0 10px 20px;
        }
        
        @media (max-width: 768px) {
            .form-group {
                flex: 1 0 calc(100% - 20px);
            }
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-text);
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(56, 176, 0, 0.25);
        }
        
        .form-icon {
            position: relative;
        }
        
        .form-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
        }
        
        .form-icon input, .form-icon select {
            padding-left: 45px;
        }
        
        .submit-btn {
            display: block;
            width: 100%;
            padding: 15px;
            margin-top: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }
        
        .submit-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .submit-btn i {
            margin-right: 8px;
        }
        
        footer {
            text-align: center;
            margin-top: 30px;
            color: var(--light-text);
            font-size: 14px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-futbol"></i> Sportify
            </div>
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Home
            </a>
        </header>
        
        <h1>Aggiungi Campo da Calcio</h1>
        
        <?php if ($messaggio): ?>
            <div class="message <?= strpos($messaggio, 'successo') !== false ? 'success' : 'error' ?>">
                <i class="<?= strpos($messaggio, 'successo') !== false ? 'fas fa-check-circle' : 'fas fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($messaggio) ?>
            </div>
        <?php endif; ?>
        
        <div class="form-card">
            <div class="form-header">
                <i class="fas fa-plus-circle"></i> Inserisci Dettagli Campo
            </div>
            <div class="form-body">
                <form action="aggiungi_campo.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="posizione">Posizione (Via, Paese):</label>
                            <div class="form-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" id="posizione" name="posizione" placeholder="es. Via Roma 1, Milano" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="terreno">Tipo di terreno:</label>
                            <div class="form-icon">
                                <i class="fas fa-leaf"></i>
                                <select id="terreno" name="terreno" required>
                                    <option value="">Seleziona terreno</option>
                                    <option value="Erba">Erba naturale</option>
                                    <option value="Sintetico">Sintetico</option>
                                    <option value="Palestra">Palestra</option>
                                    <option value="Ghiaia">Ghiaia</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="spogliatoi">Spogliatoi disponibili:</label>
                            <div class="form-icon">
                                <i class="fas fa-door-open"></i>
                                <select id="spogliatoi" name="spogliatoi" required>
                                    <option value="">Seleziona</option>
                                    <option value="1">Sì</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="docce">Docce disponibili:</label>
                            <div class="form-icon">
                                <i class="fas fa-shower"></i>
                                <select id="docce" name="docce" required>
                                    <option value="">Seleziona</option>
                                    <option value="1">Sì</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="n_giocatori">Numero giocatori per squadra:</label>
                            <div class="form-icon">
                                <i class="fas fa-users"></i>
                                <select id="n_giocatori" name="n_giocatori" required>
                                    <option value="">Seleziona formato</option>
                                    <option value="5">5 (Calcetto)</option>
                                    <option value="7">7 (Calcio a 7)</option>
                                    <option value="8">8 (Calcio a 8)</option>
                                    <option value="9">9 (Calcio a 9)</option>
                                    <option value="11">11 (Calcio a 11)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="costo">Costo orario (€):</label>
                            <div class="form-icon">
                                <i class="fas fa-euro-sign"></i>
                                <input type="number" step="0.01" id="costo" name="costo" placeholder="es. 60.00" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Salva Campo
                    </button>
                </form>
            </div>
        </div>
        
        <footer>
            © <?= date('Y') ?> Sportify - Trova il tuo campo da calcio
        </footer>
    </div>
</body>
</html>