<?php
require 'vendor/autoload.php';
// require 'my_autoloader.php';

require_once 'Classes/Sorter.php';
require_once 'Classes/PdfGenerator.php';
require_once 'Classes/PdfMerger.php';

use Classes\Sorter;
use Classes\PdfGenerator;
use Classes\PdfMerger;

$config = include('config/config.php');
// var_dump(dirname(__DIR__ . '/manage'));
// var_dump(realpath(__DIR__ . '/manage'));
// var_dump(realpath('/manage'));
// var_dump(realpath('manage'));
// var_dump($config);
// return;


$sorter = (new Sorter());

$fileCount = $sorter->getCount($config['scan_path']);

//SE NON CI SONO FILE: EARLY RETURN
if (!$fileCount) {
    PDFlog('Nessun file in coda');
    return;
}

$file_path = $sorter->getOldest($config['scan_path'] . '*.zip');

//SE NON C'è UN FILE PATH ESCO E BISOGNA CONTROLLARE CARTERLLA
if (!$file_path) {
    PDFLog("ERRORE: ci sono file in coda ma non sono .zip. Check cartella {$config['scan_path']}");
    return;
}

//SE LO SCRIPT STA GIà LAVORANDO ESCO
if (!$sorter->checkJobIsRunning($config['tmp_path'])) {
    PDFlog('Run attualmente in corso, esco e riprovo al prossimo cron');
    return;
}

$zip_original_name = basename($file_path);
$pswd_file = $config['scan_path'] . basename($file_path, '.zip') . '.txt';

//estrazione password
if (!$password = file_get_contents($pswd_file)) {
    PDFlog("ERRORE estrazione password per file: {$zip_original_name}");
    manageCreationError($file_path, $config['error_path'] . $zip_original_name);
    return;
}

PDFlog("INIZIO conversione per file: {$zip_original_name}");

//estrazione zip
$za = new ZipArchive();
$za->open($file_path);
$za->setPassword($password);
$za->extractTo($config['tmp_path']);
$za->close();

memoryInfo('CONSUMO MEMORIA PRIMA DELLA GENERAZIONE');

//creazione pdf
(new PdfGenerator)->handle($config['tmp_path']);

memoryInfo('CONSUMO MEMORIA DOPO GENERAZIONE E PRIMA DEL MERGE');

//merge dei pdf
$merge_check = (new PdfMerger)->handle($config['tmp_path']);

memoryInfo('CONSUMO MEMORIA DOPO MERGE');

//SE MERGE NON VA A BUON FINE, GESTISCO ERRORE
if (!$merge_check) {
    PDFlog("Errore nel merge dei pdf per file: {$zip_original_name}");
    manageCreationError($file_path, $config['error_path'] . $zip_original_name);
}

//creazione zip di output
$zip_check = $sorter->manageZipCreation($config['output_path'], $zip_original_name, $config['tmp_path']);

//SE NUOVO ZIP NON VA A BUON FINE, GFESTISCO ERRORE
if (!$zip_check) {
    PDFlog("Errore nella creazione dello zip per file: {$zip_original_name}");
    manageCreationError($file_path, $config['error_path'] . $zip_original_name);
}

//!pulizia cartella tmp di lavoro
$sorter->subFolderCleaning($config['tmp_path']);

// unlink($file_path)
if (unlink($file_path)) {
    PDFlog("Pulizia: cancellato file {$file_path}");
} else {
    PDFlog("ERRORE nella pulizia per file: {$file_path}");
}

PDFlog("Creazione PDF completata per file: {$zip_original_name}");

//FINE
return;



function manageCreationError($file_path, $error_path)
{
    $old = $file_path;
    // $new = str_replace('/scan', '/error', $file_path);
    $new = $error_path;
    rename($old, $new);
    return;
}

function PDFlog($msg)
{
    $log = "[" . date("Y-m-d H:i:s") . "] -> " . $msg . PHP_EOL;
    file_put_contents('log/log-' . date("Y-m-d") . '.log', $log, FILE_APPEND);
}

function memoryInfo($msg)
{
    PDFlog($msg);
    PDFlog('The script is now using: ' . round(memory_get_usage() / 1024) . ' KB of memory.');
    PDFlog('Peak usage: ' . round(memory_get_peak_usage() / 1024) . ' KB of memory.');
}
