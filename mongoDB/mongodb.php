<?php
require __DIR__ . '/../vendor/autoload.php'; // Composer autoload

// Connessione a MongoDB Atlas
$mongoClient = new MongoDB\Client("mongodb+srv://tallaricoalessandro02:Talla200210@cluster0.ly8gp9z.mongodb.net/?retryWrites=true&w=majority&tls=true&tlsInsecure=true");

// Selezione del database e collezione
$mongoDB = $mongoClient->bostarter_db;
$eventLog = $mongoDB->event_log;

// Funzione di logging
function log_event($event_type, $user_email, $description, $extra_data = []) {
    global $eventLog;

    $event = [
        'timestamp' => new MongoDB\BSON\UTCDateTime(),
        'event_type' => $event_type,
        'user_email' => $user_email,
        'description' => $description,
        'extra_data' => $extra_data
    ];

    $eventLog->insertOne($event);
}
?>
