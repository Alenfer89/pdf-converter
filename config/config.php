<?php

return [
    // 'scan_path' => str_replace('\\', '/', __DIR__ . '..\scan'),
    // 'tmp_path' => str_replace('\\', '/', __DIR__ . '..\manage'),
    // 'output_path' => str_replace('\\', '/', __DIR__ . '..\output'),
    // 'error_path' => str_replace('\\', '/', __DIR__ . '..\error'),
    'scan_path' => realpath('scan'),
    'tmp_path' => realpath('manage'),
    'output_path' => realpath('output'),
    'error_path' => realpath('error'),
    'test' => realpath('manage'),
];
