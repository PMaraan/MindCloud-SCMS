<?php
// /config/ModuleRegistry.php

use App\Config\Permissions;
use App\Modules\Accounts\Controllers\AccountsController;
use App\Modules\Departments\Controllers\DepartmentsController;
use App\Modules\Courses\Controllers\CoursesController;
use App\Modules\Programs\Controllers\ProgramsController;
use App\Modules\Curricula\Controllers\CurriculaController;
use App\Modules\SyllabusTemplates\Controllers\SyllabusTemplatesController;
use App\Modules\RTEditor\Controllers\RTEditorController;
use App\Modules\Notifications\Controllers\NotificationsController;
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
    'departments' => [
        'label'      => 'Departments/Colleges',
        'permission' => Permissions::DEPARTMENTS_VIEW,
        'controller' => DepartmentsController::class,
        'actions'    => [
            'create' => Permissions::DEPARTMENTS_CREATE,
            'edit'   => Permissions::DEPARTMENTS_EDIT,
            'delete' => Permissions::DEPARTMENTS_DELETE,
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
    'syllabus-templates' => [
        'label'      => 'Syllabus Templates',
        'controller' => SyllabusTemplatesController::class,
        'permission' => \App\Config\Permissions::SYLLABUSTEMPLATES_VIEW, // alias to old string        
        'actions'    => [
            'create' => \App\Config\Permissions::SYLLABUSTEMPLATES_CREATE// alias to old string
            // 'edit'   => Permissions::TEMPLATES_EDIT,
            // 'delete' => Permissions::TEMPLATES_DELETE,
        ], 
    ],
    'rteditor' => [
        'label'      => 'RT Editor',
        'permission' => \App\Config\Permissions::EDITOR_VIEW,
        'controller' => RTEditorController::class,
        'actions'    => ['index', 'create', 'saveMeta', 'snapshot', 'openTemplate'],
    ],
    'notifications' => [
        'label'      => 'Notifications',
        'permission' => null, // no special permission; uses session user
        'controller' => NotificationsController::class,
        'actions'    => [],
    ],
    
    // add more...
];
