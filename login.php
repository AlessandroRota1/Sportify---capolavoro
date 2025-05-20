<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sportify";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errore = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $psw = mysqli_real_escape_string($conn, $_POST['psw']);

    $sql = "SELECT * FROM utenti WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($psw, $row['psw'])) {
            $_SESSION['id_utente'] = $row['id_utente'];
            $_SESSION['nome'] = $row['nome'];
            $_SESSION['cognome'] = $row['cognome'];
            $_SESSION['email'] = $row['email'];

            header("Location: index.php");
            exit();
        } else {
            $errore = "Password errata.";
        }
    } else {
        $errore = "Email non trovata.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - Sportify</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 95%;
            max-width: 500px;
            margin: 60px auto;
            background-color: white;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0px 0px 12px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 1.05rem;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 42px 12px 12px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .toggle-password {
            position: absolute;
            top: 39px;
            right: 12px;
            cursor: pointer;
            width: 24px;
            height: 24px;
            object-fit: contain;
        }

        input[type="submit"] {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #1976D2;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <?php if (!empty($errore)) echo "<p class='error'>$errore</p>"; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="psw">Password:</label>
                <input type="password" id="psw" name="psw" required>
                <img src="occhio_2.png" alt="Mostra password" class="toggle-password" onclick="togglePassword(this)">
            </div>

            <input type="submit" value="Accedi">
        </form>

        <div class="register-link">
            Non hai un account? <a href="registrazione.php">Registrati ora</a>
        </div>
    </div>

    <script>
        function togglePassword(img) {
            const pswInput = document.getElementById('psw');
            const isVisible = pswInput.type === 'text';
            pswInput.type = isVisible ? 'password' : 'text';
            img.src = isVisible ? 'occhio_2.png' : 'occhio_1.png';
        }
    </script>
</body>
</html>
