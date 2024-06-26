<?php

return [
    // 'scan_path' => str_replace('\\', '/', __DIR__ . '..\scan'),
    // 'tmp_path' => str_replace('\\', '/', __DIR__ . '..\manage'),
    // 'output_path' => str_replace('\\', '/', __DIR__ . '..\output'),
    // 'error_path' => str_replace('\\', '/', __DIR__ . '..\error'),

    //remote (o anche no)
    'scan_path'          => realpath('scan'),
    'error_path'         => realpath('error'),
    'output_path'        => realpath('output'),
    'remote_log_path'    => realpath('log/remote'),
    'successfull_path'   => realpath('successful'),
    //interne
    'work_path'          => realpath('manage'),
    'tmp_path'           => realpath('tmp'),
    'local_log_path'     => realpath('log'),
];
