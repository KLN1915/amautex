<?php
/**
 * Compatibilidad: entrega la conexión PDO ya configurada en core/Db.php.
 * Se mantiene esta carpeta para scripts sueltos (crons, exportadores, reportes).
 */
require_once __DIR__ . '/../core/bootstrap.php';

$pdo = Db::conn();
