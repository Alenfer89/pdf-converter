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
        $fi = array_filter((array) $fi, function ($file) {
            return !in_array($file, ['.', '..', '.gitignore']);
        });
        $fileCount = count($fi);
        // $fileCount = iterator_count($fi); //per cartelle remote
        return $fileCount;
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

    protected function addZipFile($path, $zip)
    {
        $rootPath = realpath('manage') . '/';

        $fi = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        foreach ($fi as $fileinfo) {

            if ($fileinfo->isDir()) {

                $this->addZipFile($fileinfo->getPathname(), $zip);
            } else {

                if ($fileinfo->getExtension() == 'gitignore') {
                    continue;
                }

                $filePath = $fileinfo->getPathname();

                $relativePath = substr($filePath, strlen($rootPath));

                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
