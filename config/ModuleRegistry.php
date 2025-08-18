<?php
// root/config/ModuleRegistry.php

return [
    'accounts' => [
        'label' => 'Accounts',
        'controller' => \App\Controllers\AccountsController::class,
    ],
    'courses' => [
        'label' => 'Courses',
        'controller' => \App\Controllers\CoursesController::class,
    ],
    // add more modules here
];
