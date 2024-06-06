<?php

namespace Classes;

use Dompdf\Dompdf;
use Dompdf\Options;
use FilesystemIterator;

class PdfGenerator
{
    public function handle($path)
    {
        $fi = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        foreach ($fi as $fileinfo) {

            if ($fileinfo->isDir()) {

                if ($fileinfo->getFilename() !== 'assets') {
                    $this->handle($fileinfo->getFilename());
                }

                //TODO: in caso di implemetnazione doi un file di rules in formato json
                // if($fileinfo->getFilename() == 'rules'){
                //     $instructions = file_get_contents($fileinfo->getPathname() . '/rules/instructions.json');
                //     $instructions = json_decode($instructions);
                // }

            } else {

                if ($fileinfo->getExtension() == 'gitignore') {
                    continue;
                }

                $folder = dirname($fileinfo->getPathname());
                $filename = $fileinfo->getBasename('.html');
                $htmlPath = $fileinfo->getPathname();

                gc_enable();

                $this->generatePdf($htmlPath, $filename, $folder);
            }
        }
    }

    public function generatePdf($htmlPath, $filename, $folder)
    {
        $html = file_get_contents($htmlPath);

        $dompdf = new Dompdf(array('enable_remote' => true));

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('dpi', 110);

        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);

        $path = $folder . '/' . $filename . '.pdf';

        $dompdf->render();

        $pdf = $dompdf->output();

        file_put_contents($path, $pdf);

        unlink($htmlPath);
        unset($dompdf);
        unset($html);
        gc_collect_cycles();
    }
}
