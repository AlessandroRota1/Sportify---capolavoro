<?php
session_start();

// Connessione al database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$messaggio = "";

// Se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera i dati dal form
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $data_inizio = mysqli_real_escape_string($conn, $_POST['data_inizio']);
    $data_fine = mysqli_real_escape_string($conn, $_POST['data_fine']);
    $ora_inizio = mysqli_real_escape_string($conn, $_POST['ora_inizio']);
    $ora_fine = mysqli_real_escape_string($conn, $_POST['ora_fine']);
    $certificato_medico = (int)$_POST['certificato_medico'];
    $docce = (int)$_POST['docce'];
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $tipologia = mysqli_real_escape_string($conn, $_POST['tipologia']);
    $max_squadre = (int)$_POST['max_squadre'];
    $max_giocatori = (int)$_POST['max_giocatori'];
    $id_utente = $_SESSION['id_utente'];

    // Query per inserire il torneo
    $sql = "INSERT INTO tornei 
            (nome, data_inizio, data_fine, ora_inizio, ora_fine, certificato_medico, docce, note, tipologia, id_utente, max_squadre, max_giocatori) 
            VALUES 
            ('$nome', '$data_inizio', '$data_fine', '$ora_inizio', '$ora_fine', '$certificato_medico', '$docce', '$note', '$tipologia', '$id_utente', '$max_squadre', '$max_giocatori')";

    if ($conn->query($sql) === TRUE) {
        $id_torneo = $conn->insert_id;
        $messaggio = "Torneo creato con successo!";

        if (isset($_POST['campi']) && !empty($_POST['campi'])) {
            foreach ($_POST['campi'] as $id_campo) {
                $sqlCampoTorneo = "INSERT INTO campo_torneo (id_campo, id_torneo) VALUES ('$id_campo', '$id_torneo')";
                $conn->query($sqlCampoTorneo);
            }
        }
    } else {
        $messaggio = "Errore durante la creazione del torneo: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crea Torneo - Sportify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4CAF50;
            --primary-dark: #3e8e41;
            --primary-light: #8bc34a;
            --secondary: #2196F3;
            --secondary-dark: #0b7dda;
            --text-dark: #333;
            --text-light: #666;
            --background: #f5f7fa;
            --card-bg: #fff;
            --success: #4CAF50;
            --danger: #f44336;
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .app-header {
            background: var(--primary);
            color: white;
            text-align: center;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .app-title {
            font-size: 2.2rem;
            font-weight: 600;
            margin: 0;
        }

        .container {
            width: 90%;
            max-width: 900px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary);
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--primary);
            color: white;
            padding: 0.7rem 1.2rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s ease;
        }

        .btn-home:hover {
            background-color: var(--primary-dark);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .form-column {
            flex: 1;
            min-width: 250px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        input[type="text"],
        input[type="date"],
        input[type="time"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
            transition: border 0.3s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .checkbox-group {
            margin-top: 0.5rem;
        }

        input[type="submit"] {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
            margin-top: 1.5rem;
        }

        input[type="submit"]:hover {
            background-color: var(--primary-dark);
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .form-section-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .table-container {
            margin-top: 1rem;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }

        thead {
            background-color: var(--primary);
            color: white;
        }

        th, td {
            padding: 0.8rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        tr:nth-child(even) {
            background-color: rgba(0,0,0,0.02);
        }

        tr:hover {
            background-color: rgba(0,0,0,0.04);
        }

        .form-help {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 0.3rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 50px;
            background: #e9ecef;
        }

        .badge-success {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success);
        }

        .badge-danger {
            background: rgba(244, 67, 54, 0.2);
            color: var(--danger);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 1.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .btn-home {
                align-self: flex-start;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header class="app-header">
        <h1 class="app-title">Sportify</h1>
    </header>

    <div class="container">
        <div class="page-header">
            <h2 class="page-title">Crea un nuovo Torneo</h2>
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Home
            </a>
        </div>

        <?php if (!empty($messaggio)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $messaggio; ?>
            </div>
        <?php endif; ?>

        <form action="aggiungi_torneo.php" method="POST">
            <div class="form-section">
                <h3 class="form-section-title">Informazioni Generali</h3>
                <div class="form-group">
                    <label for="nome">Nome Torneo</label>
                    <input type="text" id="nome" name="nome" placeholder="Inserisci il nome del torneo" required>
                </div>

                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="data_inizio">Data di Inizio</label>
                            <input type="date" id="data_inizio" name="data_inizio" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="data_fine">Data di Fine</label>
                            <input type="date" id="data_fine" name="data_fine" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="ora_inizio">Ora di Inizio</label>
                            <input type="time" id="ora_inizio" name="ora_inizio" required>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="ora_fine">Ora di Fine</label>
                            <input type="time" id="ora_fine" name="ora_fine" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Dettagli Torneo</h3>
                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="tipologia">Tipologia Torneo</label>
                            <select id="tipologia" name="tipologia" required>
                                <option value="Girone Unico">Girone Unico</option>
                                <option value="Gironi Multipli">Gironi Multipli</option>
                                <option value="Eliminazione Diretta">Eliminazione Diretta</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="certificato_medico">Certificato Medico Obbligatorio</label>
                            <select id="certificato_medico" name="certificato_medico" required>
                                <option value="1">Sì</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-column">
                        <div class="form-group">
                            <label for="max_squadre">Numero massimo di squadre</label>
                            <input type="number" id="max_squadre" name="max_squadre" min="2" max="64" value="16" required>
                            <p class="form-help">Minimo 2, massimo 64 squadre</p>
                        </div>
                    </div>
                    <div class="form-column">
                        <div class="form-group">
                            <label for="max_giocatori">Numero massimo di giocatori per squadra</label>
                            <input type="number" id="max_giocatori" name="max_giocatori" min="1" max="30" value="11" required>
                            <p class="form-help">Minimo 1, massimo 30 giocatori</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="docce">Docce Disponibili</label>
                    <select id="docce" name="docce" required>
                        <option value="1">Sì</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="note">Note Aggiuntive</label>
                    <textarea id="note" name="note" rows="4" placeholder="Inserisci eventuali note o informazioni aggiuntive sul torneo"></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Selezione Campi</h3>
                <p class="form-help">Seleziona uno o più campi dove si svolgerà il torneo</p>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 70px;"><i class="fas fa-check-square"></i></th>
                                <th>Indirizzo</th>
                                <th>Terreno</th>
                                <th>N. Giocatori</th>
                                <th>Spogliatoi</th>
                                <th>Docce</th>
                                <th>Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $conn = new mysqli($servername, $username, $password, $dbname);
                            $sqlCampi = "SELECT * FROM campi";
                            $resultCampi = $conn->query($sqlCampi);

                            if ($resultCampi && $resultCampi->num_rows > 0) {
                                while ($campo = $resultCampi->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td style="text-align:center;">
                                            <input type="checkbox" name="campi[]" value="' . $campo['id_campo'] . '">
                                          </td>';
                                    echo '<td>' . htmlspecialchars($campo['indirizzo']) . '</td>';
                                    echo '<td>' . htmlspecialchars($campo['terreno']) . '</td>';
                                    echo '<td>' . $campo['n_giocatori'] . '</td>';
                                    echo '<td>' . ($campo['spogliatoi'] ? 
                                        '<span class="badge badge-success">Sì</span>' : 
                                        '<span class="badge badge-danger">No</span>') . '</td>';
                                    echo '<td>' . ($campo['docce'] ? 
                                        '<span class="badge badge-success">Sì</span>' : 
                                        '<span class="badge badge-danger">No</span>') . '</td>';
                                    echo '<td>€' . number_format($campo['costo'], 2) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="7" style="text-align:center;">Nessun campo disponibile</td></tr>';
                            }

                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <input type="submit" value="Crea Torneo">
        </form>
    </div>
</body>
</html>