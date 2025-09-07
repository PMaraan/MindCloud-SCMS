<?php
// /config/ModuleRegistry.php

use App\Config\Permissions;
use App\Modules\Accounts\Controllers\AccountsController;
use App\Modules\Colleges\Controllers\CollegesController;
use App\Modules\Courses\Controllers\CoursesController;
use App\Modules\Programs\Controllers\ProgramsController;
// use App\Controllers\RolesController; // might be deprecated. remove for production ...
// use App\Controllers\TemplatesController;
// use App\Controllers\SyllabusController;
// use App\Controllers\FacultyController;

return [
    'dashboard' => [
        'label'      => 'Dashboard',
        'permission' => null,
        'controller' => null, // your render() can show a welcome partial by default
    ],
    'accounts' => [
        'label'      => 'Accounts',
        'permission' => Permissions::ACCOUNTS_VIEW,
        'controller' => AccountsController::class,
        'actions'    => [
            'create' => Permissions::ACCOUNTS_CREATE,
            'edit'   => Permissions::ACCOUNTS_EDIT,
            'delete' => Permissions::ACCOUNTS_DELETE,
        ],
    ],
    'colleges' => [
        'label'      => 'Colleges',
        'permission' => Permissions::COLLEGES_VIEW,
        'controller' => CollegesController::class,
        'actions'    => [
            'create' => Permissions::COLLEGES_CREATE,
            'edit'   => Permissions::COLLEGES_EDIT,
            'delete' => Permissions::COLLEGES_DELETE,
        ],
    ],
    'programs' => [
        'label'      => 'Programs',
        'controller' => ProgramsController::class,
        'permission' => Permissions::PROGRAMS_VIEW,
        'actions'    => [
            'create' => Permissions::PROGRAMS_CREATE,
            'edit'   => Permissions::PROGRAMS_EDIT,
            'delete' => Permissions::PROGRAMS_DELETE,
        ],
    ],
    'curricula' => [
        'label'      => 'Curricula',
        'permission' => Permissions::CURRICULA_VIEW,
        'controller' => CurriculaController::class,
        'actions'    => [
            'create' => Permissions::CURRICULA_CREATE,
            'edit'   => Permissions::CURRICULA_EDIT,
            'delete' => Permissions::CURRICULA_DELETE,
        ],
    ],
    'courses' => [
        'label'      => 'Courses',
        'controller' => CoursesController::class,
        'permission' => Permissions::COURSES_VIEW,
        'actions'    => [
            'create' => Permissions::COURSES_CREATE,
            'edit'   => Permissions::COURSES_EDIT,
            'delete' => Permissions::COURSES_DELETE,
        ],
    ],
    
    // add more...
];
