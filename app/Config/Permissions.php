<?php
// /app/Config/Permissions.php
namespace App\Config;

/**
 * Central permission keys (map to your DB permission_name values)
 * Keep these names stable across the app.
 */
final class Permissions
{
    public const ACCOUNTS_VIEW   = 'AccountViewing';
    public const ACCOUNTS_CREATE = 'AccountCreation';
    public const ACCOUNTS_EDIT   = 'AccountModification';
    public const ACCOUNTS_DELETE = 'AccountDeletion';

    public const COLLEGES_VIEW   = 'CollegeViewing';
    public const COLLEGES_CREATE = 'CollegeCreation';
    public const COLLEGES_EDIT   = 'CollegeModification';
    public const COLLEGES_DELETE = 'CollegeDeletion';

    public const COURSES_VIEW   = 'CourseViewing';
    public const COURSES_CREATE = 'CourseCreation';
    public const COURSES_EDIT   = 'CourseModification';
    public const COURSES_DELETE = 'CourseDeletion';

    public const PROGRAMS_VIEW   = 'ProgramViewing';
    public const PROGRAMS_CREATE = 'ProgramCreation';
    public const PROGRAMS_EDIT   = 'ProgramModification';
    public const PROGRAMS_DELETE = 'ProgramDeletion';
    // Add more modules here...
}
