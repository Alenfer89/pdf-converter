<?php

namespace Classes;

use FilesystemIterator;
use Karriere\PdfMerge\PdfMerge;

class PdfMerger
{
    protected $merge_check = 1;

    public function handle($path)
    {
        $fi = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
        $sorter = new Sorter();

        foreach ($fi as $fileinfo) {

            if ($fileinfo->isDir()) {

                if ($fileinfo->getFilename() !== 'assets') {

                    $sorter->recursiveFolderCleaning($fileinfo->getPathname());

                    continue;
                }

                $pdf_files = glob($fileinfo->getPathname() . '/*.pdf');

                if (count($pdf_files)) {

                    if (count($pdf_files) !== $sorter->getCount($fileinfo->getPathname())) {

                        echo 'Errore';

                        $this->merge_check = 0;

                        return;
                    }

                    gc_enable();

                    $folder = dirname($fileinfo->getPathname());
                    $name = $fileinfo->getFilename();

                    $this->mergePdf($pdf_files, $folder, $name);

                    $sorter->recursiveFolderCleaning($fileinfo->getPathname());
                } else {

                    $this->handle($fileinfo->getPathname());
                }
            }
        }

        return $this->merge_check;
    }

    protected function mergePdf($files, $folderPath, $fileName)
    {
        $pdfMerge = new PdfMerge();

        foreach ($files as $pdf) {
            $pdfMerge->add($pdf);
        }

        $pdfMerge->merge($folderPath . "/" . $fileName . ".pdf");
        gc_collect_cycles();
    }
}
