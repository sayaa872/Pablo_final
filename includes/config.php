<?php
// Informations de connexion à la base de données
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // À modifier selon votre configuration
define('DB_PASSWORD', ''); // À modifier selon votre configuration
define('DB_NAME', 'pablo_memorial');

// Tentative de connexion à la base de données MySQL
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Vérifier la connexion
if($conn === false){
    die("ERREUR : Impossible de se connecter à la base de données. " . mysqli_connect_error());
}

// Définir l'encodage des caractères
mysqli_set_charset($conn, "utf8");
?>