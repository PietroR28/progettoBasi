
<?php

$host = "127.0.0.1:3306";
$user = "root";
$password = "";
$database = "bostarter_db";

$connessione = new mysqli($host, $user, $password, $database);

if ($connessione->connect_error) {
    die("Errore di connessione: " . $connessione->connect_error);
}



echo "connessione avvenuta con successo: " . $connessione->host_info;

$connessione->close();

?>







