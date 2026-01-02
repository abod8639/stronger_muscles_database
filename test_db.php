<?php
$credentials = [
    ['root', ''],
    ['root', 'root'],
    ['laravel', 'secret'],
    ['forge', ''],
];

foreach ($credentials as $cred) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1", $cred[0], $cred[1]);
        echo "SUCCESS: Connected with user '{$cred[0]}' and password '{$cred[1]}'\n";
        exit(0);
    } catch (PDOException $e) {
        // continue
    }
}
echo "FAILURE: Could not connect with common credentials.\n";
exit(1);
