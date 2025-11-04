<?php
// /app/Config/Permissions.php
namespace App\Config;

/**
 * Central permission keys mapped to DB permission_name values.
 * Keep these constants stable across the app. They are the single source of truth.
 *
 * Source (permissions table):
 * - Accounts: AccountCreation/Viewing/Modification/Deletion
 * - Programs: ProgramCreation/Viewing/Modification/Deletion
 * - Courses:  CourseCreation/Viewing/Modification/Deletion
 * - Faculty:  FacultyViewing/FacultyModification
 * - Templates: SyllabusTemplateCreation/Viewing/Modification/Deletion
 * - Syllabus:  SyllabusCreation/Viewing/Modification/Deletion/Allocation
 * - Curricula: CurriculaCreation/Viewing/Modification/Deletion
 * - Departments: DepartmentCreation/Viewing/Modification/Deletion
 * - Editor:   EDITOR_CREATE/EDITOR_VIEW/EDITOR_EDIT
 */
final class Permissions
{
    // Accounts
    public const ACCOUNTS_CREATE = 'AccountCreation';
    public const ACCOUNTS_VIEW   = 'AccountViewing';
    public const ACCOUNTS_EDIT   = 'AccountModification';
    public const ACCOUNTS_DELETE = 'AccountDeletion';

    // Departments (a.k.a. Colleges in your UI labels)
    public const DEPARTMENTS_CREATE = 'DepartmentCreation';
    public const DEPARTMENTS_VIEW   = 'DepartmentViewing';
    public const DEPARTMENTS_EDIT   = 'DepartmentModification';
    public const DEPARTMENTS_DELETE = 'DepartmentDeletion';

    // --- Backward-compatibility aliases for older code that referenced COLLEGES_* ---
    public const COLLEGES_CREATE = self::DEPARTMENTS_CREATE;
    public const COLLEGES_VIEW   = self::DEPARTMENTS_VIEW;
    public const COLLEGES_EDIT   = self::DEPARTMENTS_EDIT;
    public const COLLEGES_DELETE = self::DEPARTMENTS_DELETE;

    // Programs
    public const PROGRAMS_CREATE = 'ProgramCreation';
    public const PROGRAMS_VIEW   = 'ProgramViewing';
    public const PROGRAMS_EDIT   = 'ProgramModification';
    public const PROGRAMS_DELETE = 'ProgramDeletion';

    // Curricula
    public const CURRICULA_CREATE = 'CurriculaCreation';
    public const CURRICULA_VIEW   = 'CurriculaViewing';
    public const CURRICULA_EDIT   = 'CurriculaModification';
    public const CURRICULA_DELETE = 'CurriculaDeletion';

    // Courses
    public const COURSES_CREATE  = 'CourseCreation';
    public const COURSES_VIEW    = 'CourseViewing';
    public const COURSES_EDIT    = 'CourseModification';
    public const COURSES_DELETE  = 'CourseDeletion';

    // Syllabus Templates (Template Builder)
    public const TEMPLATEBUILDER_CREATE = 'SyllabusTemplateCreation';
    public const TEMPLATEBUILDER_VIEW   = 'SyllabusTemplateViewing';
    public const TEMPLATEBUILDER_EDIT   = 'SyllabusTemplateModification';
    public const TEMPLATEBUILDER_DELETE = 'SyllabusTemplateDeletion';

    // Aliases for the renamed module (Syllabus Templates)
    public const SYLLABUSTEMPLATES_CREATE = self::TEMPLATEBUILDER_CREATE;
    public const SYLLABUSTEMPLATES_VIEW   = self::TEMPLATEBUILDER_VIEW;
    public const SYLLABUSTEMPLATES_EDIT   = self::TEMPLATEBUILDER_EDIT;
    public const SYLLABUSTEMPLATES_DELETE = self::TEMPLATEBUILDER_DELETE;

    // Syllabi
    public const SYLLABI_CREATE   = 'SyllabusCreation';
    public const SYLLABI_VIEW     = 'SyllabusViewing';
    public const SYLLABI_EDIT     = 'SyllabusModification';
    public const SYLLABI_DELETE   = 'SyllabusDeletion';
    public const SYLLABI_ALLOCATE = 'SyllabusAllocation';

    // Editor
    public const EDITOR_CREATE = 'EDITOR_CREATE';
    public const EDITOR_VIEW   = 'EDITOR_VIEW';
    public const EDITOR_EDIT   = 'EDITOR_EDIT';
    // Add more modules here...
}
