<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connessione fallita: " . $conn->connect_error);

$errore = "";
$successo = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    $cognome = mysqli_real_escape_string($conn, $_POST['cognome']);
    $nickname = mysqli_real_escape_string($conn, $_POST['nickname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $psw = $_POST['psw'];
    $psw2 = $_POST['psw2'];
    $data_nascita = mysqli_real_escape_string($conn, $_POST['data_nascita']);
    $telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
    $indirizzo = mysqli_real_escape_string($conn, $_POST['indirizzo']);
    $paese = mysqli_real_escape_string($conn, $_POST['paese']);
    $sesso = ($_POST['sesso'] == 'maschio') ? 1 : 0;

    if ($psw !== $psw2) {
        $errore = "Le password non corrispondono.";
    } else {
        $checkEmail = "SELECT * FROM utenti WHERE email = '$email'";
        $result = $conn->query($checkEmail);
        if ($result->num_rows > 0) {
            $errore = "Questa email è già registrata.";
        } else {
            $psw_hash = password_hash($psw, PASSWORD_DEFAULT);
            $sql = "INSERT INTO utenti (nome, cognome, nickname, email, psw, data_nascita, telefono, indirizzo, paese, sesso) 
                    VALUES ('$nome', '$cognome', '$nickname', '$email', '$psw_hash', '$data_nascita', '$telefono', '$indirizzo', '$paese', '$sesso')";
            if ($conn->query($sql) === TRUE) $successo = true;
            else $errore = "Errore durante la registrazione: " . $conn->error;
        }
    }
    $conn->close();

    if ($successo) {
        echo '
        <!DOCTYPE html>
        <html lang="it"><head><meta charset="UTF-8">
        <meta http-equiv="refresh" content="3;url=index.php">
        <title>Registrazione completata</title>
        <style>
        body { display:flex; justify-content:center; align-items:center; height:100vh; background:#f4f4f4; }
        .message { background:white; padding:40px; border-radius:10px; text-align:center; font-family:sans-serif; box-shadow:0 0 10px rgba(0,0,0,0.2);}
        .loader { width:40px; height:40px; border:5px solid #f3f3f3; border-top:5px solid #4CAF50; border-radius:50%; animation:spin 1s linear infinite; margin:20px auto;}
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        </style>
        </head><body>
        <div class="message"><h1>Registrazione completata!</h1><p>Verrai reindirizzato alla home tra pochi secondi...</p><div class="loader"></div></div>
        </body></html>';
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Registrazione - Sportify</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .container {
      width: 65%;
      max-width: 700px;
      margin: 50px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.15);
    }
    h1 {
      text-align: center;
    }
    .form-group {
      margin-bottom: 20px;
      position: relative;
    }
    label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }
    input, select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
      font-size: 1rem;
    }
    input[type="submit"] {
      background: #4CAF50;
      color: white;
      border: none;
      cursor: pointer;
      transition: 0.3s;
    }
    input[type="submit"]:hover {
      background: #388e3c;
    }
    .error {
      color: red;
      text-align: center;
      margin-bottom: 20px;
    }
    .toggle-eye {
      position: absolute;
      top: 39px;
      right: 12px;
      width: 24px;
      height: 24px;
      cursor: pointer;
    }
  </style>
  <script>
    function togglePassword(idEye, idInput) {
      const eye = document.getElementById(idEye);
      const input = document.getElementById(idInput);
      if (input.type === "password") {
        input.type = "text";
        eye.src = "occhio_1.png";
      } else {
        input.type = "password";
        eye.src = "occhio_2.png";
      }
    }

    function validateForm(e) {
      const psw = document.getElementById('psw').value;
      const psw2 = document.getElementById('psw2').value;
      if (psw !== psw2) {
        alert("Le password non corrispondono.");
        e.preventDefault();
      }
    }

    window.addEventListener('DOMContentLoaded', () => {
      document.getElementById('regForm').addEventListener('submit', validateForm);
    });
  </script>
</head>
<body>

<div class="container">
  <h1>Registrazione</h1>
  <?php if (!empty($errore)) echo "<p class='error'>$errore</p>"; ?>
  <form id="regForm" action="registrazione.php" method="POST">
    <div class="form-group">
      <label for="nome">Nome:</label>
      <input type="text" id="nome" name="nome" required>
    </div>
    <div class="form-group">
      <label for="cognome">Cognome:</label>
      <input type="text" id="cognome" name="cognome" required>
    </div>
    <div class="form-group">
      <label for="nickname">Nickname:</label>
      <input type="text" id="nickname" name="nickname" required>
    </div>
    <div class="form-group">
      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required>
    </div>
    <div class="form-group">
      <label for="psw">Password:</label>
      <input type="password" id="psw" name="psw" required>
      <img src="occhio_2.png" class="toggle-eye" id="eye1" onclick="togglePassword('eye1','psw')">
    </div>
    <div class="form-group">
      <label for="psw2">Ripeti Password:</label>
      <input type="password" id="psw2" name="psw2" required>
      <img src="occhio_2.png" class="toggle-eye" id="eye2" onclick="togglePassword('eye2','psw2')">
    </div>
    <div class="form-group">
      <label for="data_nascita">Data di Nascita:</label>
      <input type="date" id="data_nascita" name="data_nascita" required>
    </div>
    <div class="form-group">
      <label for="telefono">Telefono:</label>
      <input type="tel" id="telefono" name="telefono" required>
    </div>
    <div class="form-group">
      <label for="indirizzo">Indirizzo:</label>
      <input type="text" id="indirizzo" name="indirizzo" required>
    </div>
    <div class="form-group">
      <label for="paese">Paese:</label>
      <input type="text" id="paese" name="paese" required>
    </div>
    <div class="form-group">
      <label for="sesso">Sesso:</label>
      <select id="sesso" name="sesso" required>
        <option value="femmina">Femmina</option>
        <option value="maschio">Maschio</option>
      </select>
    </div>
    <input type="submit" value="Registrati">
  </form>
</div>

</body>
</html>
