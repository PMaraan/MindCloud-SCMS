<?php
// root/config/ModuleRegistry.php

use App\Controllers\AccountsController;
use App\Controllers\CoursesController;
// use App\Controllers\ProgramsController;
// use App\Controllers\CollegesController;
// use App\Controllers\RolesController; // might be deprecated. remove for production ...
// use App\Controllers\TemplatesController;
// use App\Controllers\SyllabusController;
// use App\Controllers\FacultyController;

return [
    'accounts' => [
        'label'      => 'Accounts',
        'controller' => AccountsController::class,
        'permission' => 'AccountViewing',
    ],
    'courses' => [
        'label'      => 'Courses',
        'controller' => CoursesController::class,
        'permission' => 'CourseViewing',
    ],
    // add more...
    // 'programs' => ['label'=>'Programs','controller'=>ProgramsController::class,'permission'=>'ProgramViewing'],
    // 'colleges' => ['label'=>'Colleges','controller'=>CollegesController::class,'permission'=>'CollegeViewing'],
    // 'roles'    => ['label'=>'Roles','controller'=>RolesController::class,'permission'=>'RoleViewing'],
    // 'templates'=> ['label'=>'Templates','controller'=>TemplatesController::class,'permission'=>'SyllabusTemplateViewing'],
    // 'syllabus' => ['label'=>'Syllabus','controller'=>SyllabusController::class,'permission'=>'SyllabusViewing'],
    // 'faculty'  => ['label'=>'Faculty','controller'=>FacultyController::class,'permission'=>'FacultyViewing'],
];
