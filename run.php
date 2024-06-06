<?php
require 'vendor/autoload.php';

use Classes\Sorter;
use Classes\PDFLogger;
use Classes\PdfMerger;
use Classes\PdfGenerator;

$config = include('config/config.php');
// $config = include('config/local_config.php');

$sorter = (new Sorter());
$logger = new PDFLogger($config['local_log_path'], $config['remote_log_path']);

$logger->clearLogFolders();

$fileCount = $sorter->getCount($config['scan_path']);

//SE NON CI SONO FILE: EARLY RETURN
if (!$fileCount) {
    $logger->logAll('Nessun file in coda');
    return;
}

$logger->logAll("File trovati in cartella {$congig['scan_path']}: {$fileCount}");

$ini_zip_path = $sorter->getOldest($config['scan_path'] . '*.zip');

//SE NON C'è UN FILE PATH ESCO E BISOGNA CONTROLLARE CARTERLLA
if (!$ini_zip_path) {
    $logger->logAll("ERRORE: ci sono file in coda ma non sono .zip. Check cartella {$config['scan_path']}");
    return;
}

//SE LO SCRIPT STA GIà LAVORANDO ESCO
if (!$sorter->checkJobIsRunning($config['work_path'])) {
    $logger->logAll('Run attualmente in corso, esco e riprovo al prossimo cron');
    return;
}

$zip_original_name = basename($ini_zip_path);
$pswd_file = $config['scan_path'] . basename($ini_zip_path, '.zip') . '.txt';

//estrazione password
if (!$password = file_get_contents($pswd_file)) {
    $logger->logAll("ERRORE estrazione password per file: {$zip_original_name}");
    $sorter->manageError($ini_zip_path, $config['error_path'] . $zip_original_name, $pswd_file, $config['work_path'], $logger);
    return;
}

$logger->logAll("INIZIO conversione per file: {$zip_original_name}");

//estrazione zip
$za = new ZipArchive();
$za->open($ini_zip_path);
$za->setPassword($password);
$za->extractTo($config['work_path']);
$za->close();

$logger->memoryInfo('CONSUMO MEMORIA PRIMA DELLA GENERAZIONE');

//creazione pdf
(new PdfGenerator)->handle($config['work_path']);

$logger->memoryInfo('CONSUMO MEMORIA DOPO GENERAZIONE E PRIMA DEL MERGE');

//merge dei pdf
$merge_check = (new PdfMerger)->handle($config['work_path']);

$logger->memoryInfo('CONSUMO MEMORIA DOPO MERGE');

//SE MERGE NON VA A BUON FINE, GESTISCO ERRORE
if (!$merge_check) {
    $logger->logAll("Errore nel merge dei pdf per file: {$zip_original_name}");
    $sorter->manageError($ini_zip_path, $config['error_path'] . $zip_original_name, $pswd_file, $config['work_path'], $logger);
    return;
}

$tmp_zip_path = $config['tmp_path'] . $zip_original_name;
$err_zip_path = $config['error_path'] . $zip_original_name;
$out_zip_path = $config['output_path'] . $zip_original_name;
$suc_zip_path = $config['successful_path'] . $zip_original_name;

//creazione zip di output
$zip_check = $sorter->manageZipCreation($tmp_zip_path, $config['work_path']);

//SE NUOVO ZIP NON VA A BUON FINE, GFESTISCO ERRORE
if (!$zip_check) {
    $logger->logAll("Errore nella creazione dello zip per file: {$zip_original_name}. File da spostare in {$config['error_path']}");
    $sorter->manageError($ini_zip_path, $err_zip_path, $pswd_file, $config['work_path'], $logger);
    return;
}

if (!copy($tmp_zip_path, $out_zip_path)) {
    $logger->logAll("Errore nella copia dello zip {$zip_original_name} da {$config['tmp_path']} a {$config['output_path']}. File da spostare in {$config['error_path']}");
    $sorter->manageError($ini_zip_path, $err_zip_path, $pswd_file, $config['work_path'], $logger);
    return;
} else {
    $logger->logAll("Copia dello zip {$zip_original_name} da {$config['tmp_path']} a {$config['output_path']}");
}

//!pulizia cartella tmp di lavoro
$sorter->subFolderCleaning($config['work_path']);
$sorter->cleanUp($tmp_zip_path, $logger);

copy($ini_zip_path, $suc_zip_path);
$sorter->cleanUp($ini_zip_path, $logger);
$sorter->cleanUp($pswd_file, $logger);

$logger->logAll("Creazione PDF completata per file: {$zip_original_name}");

//FINE
return;
