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

    public const COURSES_VIEW   = 'CourseViewing';
    public const COURSES_CREATE = 'CourseCreation';
    public const COURSES_EDIT   = 'CourseModification';
    public const COURSES_DELETE = 'CourseDeletion';

    // Add more modules here...
}
