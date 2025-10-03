<?php
// app/Repositories/DepartmentsRepositoryInterface.php
// PURPOSE: Describe the data operations the app needs for Departments,
// without committing to a specific database/driver.
// Controllers/Services use *this* interface (portable, testable).

declare(strict_types=1);

namespace App\Repositories;

interface DepartmentsRepositoryInterface
{
    /**
     * Check if a department row exists.
     */
    public function exists(int $departmentId): bool;

    /**
     * Check if a department row is marked as a college (is_college=TRUE).
     */
    public function isCollege(int $departmentId): bool;

    /**
     * Assign a dean to a department (atomic):
     * - Clear any existing dean mapping for this dean or department
     * - Insert the new mapping
     * - Optionally set the dean's user_roles.department_id for the Dean role
     */
    public function assignDean(int $departmentId, string $deanIdNo): void;

    /**
     * Clear the dean mapping for the given department (atomic).
     * Optionally clears the dean's user_roles.department_id for the Dean role.
     */
    public function clearDeanForDepartment(int $departmentId): void;
}
