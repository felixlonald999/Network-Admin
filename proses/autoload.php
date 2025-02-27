<?php
// load config
foreach (glob("../config/*.php") as $filename)
{
    require_once $filename;
}

// load helper
foreach (glob("../helper/*.php") as $filename)
{
    require_once $filename;
}

// load PhpSpreadsheet
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../library/PhpSpreadsheet/src/';

    // Konversi namespace PhpOffice\PhpSpreadsheet\ ke path file
    if (strpos($class, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
        $class = str_replace('PhpOffice\\PhpSpreadsheet\\', '', $class);
        $file = $baseDir . str_replace('\\', '/', $class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
});

?>