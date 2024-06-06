<?php

namespace Classes;

class PDFLogger
{
    protected string $local_path;
    protected string $remote_path;

    /**
     * Classe per loggare
     *
     * @param [type] $local_log_path
     * @param [type] $remote_log_path
     */
    public function __construct($local_log_path, $remote_log_path)
    {
        $this->local_path = $local_log_path;
        $this->remote_path = $remote_log_path;
    }

    /**
     * Logga sia in locale che in remoto
     *
     * @param [type] $msg
     * @return void
     */
    public function logAll($msg)
    {
        $this->PDFlog($msg);
        $this->serviceLog($msg);
    }
    /**
     * Logga in locale
     *
     * @param [type] $msg
     * @return void
     */
    function PDFlog($msg)
    {
        $log = "[" . date("Y-m-d H:i:s") . "] -> " . $msg . PHP_EOL;
        file_put_contents($this->local_path . 'log-' . date("Y-m-d") . '.log', $log, FILE_APPEND);
    }

    /**
     * Logga in remoto
     *
     * @param [type] $msg
     * @return void
     */
    function serviceLog($msg)
    {
        $log = "[" . date("Y-m-d H:i:s") . "] -> " . $msg . PHP_EOL;
        file_put_contents($this->remote_path . 'log-' . date("Y-m-d") . '.log', $log, FILE_APPEND);
    }

    /**
     * Logga i consum idell'applicativo
     *
     * @param [type] $msg
     * @return void
     */
    function memoryInfo($msg)
    {
        $this->PDFlog($msg);
        $this->PDFlog('The script is now using: ' . round(memory_get_usage() / 1024) . ' KB of memory.');
        $this->PDFlog('Peak usage: ' . round(memory_get_peak_usage() / 1024) . ' KB of memory.');
    }

    /**
     * Rimuove i file di log più vecchi di 15 giorni. Parte solo la prima volta nel giorno
     *
     * @return void
     */
    public function clearLogFolders()
    {
        $start = date("Y-m-d 00:00:00");
        $end = date("Y-m-d 00:15:00");
        $between = date("Y-m-d H:i:s");
        $threshold = strtotime('-15 days');

        if ((strtotime($start) < strtotime($between)) && (strtotime($between) < strtotime($end))) {
            $local_log_files = glob($this->local_path . "*.log");

            $remote_log_files = glob($this->remote_path . "*.log");

            $global_log_files = array_merge($local_log_files, $remote_log_files);

            foreach ($global_log_files as $index => $file) {
                if (is_file($file)) {
                    if ($threshold >= filemtime($file)) {
                        unlink($file);
                    }
                }
            }
        }
    }
}
