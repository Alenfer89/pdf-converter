<?php

namespace Classes;

use FilesystemIterator;
use ZipArchive;

class Sorter
{
    /**
     * Given an exstension, retruns the oldest file inside
     *
     * @param string $path
     * @return mixed $stringpath or 0 to exit
     */
    public function getOldest(string $path_with_exstension): mixed
    {
        $files = glob($path_with_exstension);
        $exclude_files = array('.', '..', '.gitignore');
        if (!in_array($files, $exclude_files)) {
            // Sort files by modified time, latest to earliest
            // Use SORT_ASC in place of SORT_DESC for earliest to latest
            array_multisort(
                array_map('filemtime', $files),
                SORT_NUMERIC,
                SORT_ASC,
                $files
            );
        }

        if (!$files) {
            return 0;
        }

        return $files[0];
    }

    /**
     * Return the number of files in a folder
     * 
     * @param string $folder_to_scan
     * @return integer $fileCount
     */
    public function getCount(string $folder_to_scan): int
    {
        $fi = new FilesystemIterator($folder_to_scan, FilesystemIterator::SKIP_DOTS);

        /**
         * se c'è gitignore
         */
        $counter = [];

        foreach ($fi as $fileinfo) {
            if (!in_array($fileinfo->getExtension(), ['.', '..', '.gitignore'])) {
                $counter[] = $fileinfo;
            }
        }

        return count($counter);
    }

    /**
     * Given a folder, empties it of all files and subfolders and deletes it
     *
     * @param [type] $path to folder
     * @return void
     */
    public function recursiveFolderCleaning($path)
    {
        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($path . '/' . $object) == 'dir') {
                        $this->recursiveFolderCleaning($path . '/' . $object);
                        // rmdir($path."/".$object);
                    } else {
                        unlink($path . '/' . $object);
                    }
                }
            }
            reset($objects);
            rmdir($path);
        }
    }

    /**
     * Given a folder, empties and deletes all subfolders and files
     *
     * @param [type] $path
     * @return void
     */
    public function subFolderCleaning($path)
    {
        $fi = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        foreach ($fi as $fileinfo) {
            $this->recursiveFolderCleaning($fileinfo->getPathname());
        }
    }

    public function checkJobIsRunning($path)
    {
        $objects = scandir($path);

        $objects = array_filter($objects, function ($obj) {
            return !in_array($obj, ['.gitignore', '.', '..']);
        });

        return count($objects) == 0;
    }

    public function manageZipCreation($zip_path, $folder_to_add)
    {
        $zipOut = new ZipArchive();

        $zipOut->open($zip_path, ZipArchive::CREATE);

        $this->addZipFile($folder_to_add, $zipOut);

        return $zipOut->close();
    }

    protected function addZipFile($workDir, $zip, $path = null)
    {
        $fi = new FilesystemIterator($path ?? $workDir, FilesystemIterator::SKIP_DOTS);

        foreach ($fi as $fileinfo) {

            if ($fileinfo->isDir()) {

                $this->addZipFile($workDir, $zip, $fileinfo->getPathname());
            } else {

                if ($fileinfo->getExtension() == 'gitignore') {
                    continue;
                }

                $filePath = $fileinfo->getPathname();

                $relativePath = substr($filePath, strlen($workDir));

                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Questa funzione gestisce l'errore nelle varie fasi della creazione del pdf. Il comportamento generale è di spostare lo zip nella cartella
     * degli errori, cancellare il file con la password, ripulire la cartella locale di lavoro, loggare il tutto.
     *
     * @param [type] $old_zip_path zip da spostare
     * @param [type] $error_zip_path nuovo percorso zip
     * @param [type] $pswd_path file di password associata che va rimossa dalla cartella di scansione
     * @param [type] $work_path cartella di lavoro temporanea da spostare
     * @param PDFLogger $logger
     * @return void
     */
    function manageError($old_zip_path, $error_zip_path, $pswd_path, $work_path, PDFLogger $logger)
    {
        rename($old_zip_path, $error_zip_path);
        $logger->logAll("File spostato DA: {$old_zip_path} --- A: {$error_zip_path}");
        $this->cleanUp($pswd_path, $logger);
        $this->subFolderCleaning($work_path);
        $logger->logAll("Pulita cartella di lavoro {$work_path}");
        return;
    }

    /**
     * Dato un file, lo cancella e lo logga sia in locale che in remoto
     *
     * @param [type] $ini_zip_path da cancellare
     * @param PDFLogger $logger
     * @return void
     */
    public function cleanUp($ini_zip_path, PDFLogger $logger)
    {
        if (unlink($ini_zip_path)) {
            $logger->logAll("Pulizia: cancellato file {$ini_zip_path}");
        } else {
            $logger->logAll("ERRORE nella pulizia per file: {$ini_zip_path}");
        }
    }
}
