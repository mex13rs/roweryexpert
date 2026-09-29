<?php
declare(strict_types=1);

/* Wczytuje konfigurację panelu (stałe + funkcje wspólne).
   config.php nie jest w gicie (sekrety) - w świeżym klonie używamy
   wzorca config.example.php; funkcje w obu plikach są identyczne.
   Wczytywanie nie łączy się z bazą (db() jest wywoływane dopiero
   przy zapytaniu), więc testy czystych funkcji działają bez MySQL. */

$config = is_file(__DIR__ . '/../config.php')
    ? __DIR__ . '/../config.php'
    : __DIR__ . '/../config.example.php';

require $config;

require __DIR__ . '/Support/RunPhpProcess.php';
