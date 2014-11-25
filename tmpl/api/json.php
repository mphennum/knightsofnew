<?php header('Content-Type: application/json; charset=UTF-8'); ?>
<?= json_encode($response, KON\Config::DEVMODE ? JSON_PRETTY_PRINT : 0); ?>
