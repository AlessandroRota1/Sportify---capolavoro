<?php
session_start();
if (!isset($_SESSION['id_utente'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$id_utente = $_SESSION['id_utente'];
$messaggio = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['conferma_submit'])) {
    $nome = $conn->real_escape_string($_POST["nome"]);
    $cognome = $conn->real_escape_string($_POST["cognome"]);
    $email = $conn->real_escape_string($_POST["email"]);
    $telefono = $conn->real_escape_string($_POST["telefono"]);
    $indirizzo = $conn->real_escape_string($_POST["indirizzo"]);
    $paese = $conn->real_escape_string($_POST["paese"]);
    $data_nascita = $conn->real_escape_string($_POST["data_nascita"]);
    $sesso = $_POST["sesso"] === "maschio" ? 1 : 0;

    if ($_POST["password"] === $_POST["conferma_password"]) {
        $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

        $sql = "UPDATE utenti SET 
            nome='$nome', cognome='$cognome', email='$email', telefono='$telefono',
            indirizzo='$indirizzo', paese='$paese', data_nascita='$data_nascita',
            sesso='$sesso', psw='$password_hash'
            WHERE id_utente = $id_utente";

        if ($conn->query($sql) === TRUE) {
            $messaggio = "✅ Dati aggiornati con successo!";
        } else {
            $messaggio = "❌ Errore durante l'aggiornamento: " . $conn->error;
        }
    } else {
        $messaggio = "❌ Le password non coincidono.";
    }
}

$sql = "SELECT * FROM utenti WHERE id_utente = $id_utente";
$result = $conn->query($sql);
$utente = $result->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Area Personale - Sportify</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f4;
            padding: 40px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; margin-bottom: 20px; }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }
        .toggle-password {
            position: relative;
        }
        .toggle-password span {
            position: absolute;
            right: 12px;
            top: 35px;
            cursor: pointer;
            font-size: 1.2rem;
            color: #888;
        }
        .message {
            text-align: center;
            margin: 10px 0;
            color: green;
        }
        .error { color: red; text-align: center; }

        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background-color: #388e3c;
        }

        /* Popup di conferma */
        .popup-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .popup-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            width: 300px;
        }

        .popup-box p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .popup-box button {
            width: 45%;
            margin: 0 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Area Personale</h2>
    <?php if (!empty($messaggio)) echo "<p class='message'>$messaggio</p>"; ?>

    <form id="userForm" method="POST">
        <div class="form-group">
            <label>Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($utente['nome']) ?>" required>
        </div>
        <div class="form-group">
            <label>Cognome</label>
            <input type="text" name="cognome" value="<?= htmlspecialchars($utente['cognome']) ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($utente['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>Telefono</label>
            <input type="tel" name="telefono" value="<?= htmlspecialchars($utente['telefono']) ?>" required>
        </div>
        <div class="form-group">
            <label>Indirizzo</label>
            <input type="text" name="indirizzo" value="<?= htmlspecialchars($utente['indirizzo']) ?>" required>
        </div>
        <div class="form-group">
            <label>Paese</label>
            <input type="text" name="paese" value="<?= htmlspecialchars($utente['paese']) ?>" required>
        </div>
        <div class="form-group">
            <label>Data di nascita</label>
            <input type="date" name="data_nascita" value="<?= $utente['data_nascita'] ?>" required>
        </div>
        <div class="form-group">
            <label>Sesso</label>
            <select name="sesso">
                <option value="maschio" <?= $utente['sesso'] == 1 ? 'selected' : '' ?>>Maschio</option>
                <option value="femmina" <?= $utente['sesso'] == 0 ? 'selected' : '' ?>>Femmina</option>
            </select>
        </div>

        <div class="form-group toggle-password">
            <label>Nuova Password</label>
            <input type="password" id="password" name="password" required>
            <span onclick="toggleVisibility('password')">👁️</span>
        </div>
        <div class="form-group toggle-password">
            <label>Conferma Password</label>
            <input type="password" id="conferma_password" name="conferma_password" required>
            <span onclick="toggleVisibility('conferma_password')">👁️</span>
        </div>

        <button type="button" onclick="mostraPopup()">Salva Modifiche</button>
    </form>
</div>

<!-- POPUP DI CONFERMA -->
<div class="popup-overlay" id="popup">
    <div class="popup-box">
        <p>Confermi di voler salvare le modifiche?</p>
        <button onclick="conferma()">Sì</button>
        <button onclick="annulla()">No</button>
    </div>
</div>

<script>
function toggleVisibility(id) {
    const field = document.getElementById(id);
    field.type = field.type === "password" ? "text" : "password";
}

function mostraPopup() {
    document.getElementById("popup").style.display = "flex";
}

function annulla() {
    document.getElementById("popup").style.display = "none";
}

function conferma() {
    const form = document.getElementById("userForm");

    const hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.name = "conferma_submit";
    hidden.value = "1";
    form.appendChild(hidden);

    form.submit();
}
</script>

</body>
</html>
