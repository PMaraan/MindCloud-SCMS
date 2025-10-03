<?php
// app/Services/DepartmentPolicies.php
// PURPOSE: Centralize business rules so they are enforced in one place,
// regardless of database. Keeps controllers slim and consistent.

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DepartmentsRepositoryInterface;
use DomainException;

final class DepartmentPolicies
{
    public function __construct(private DepartmentsRepositoryInterface $repo) {}

    /**
     * Ensure a department exists. Throw a DomainException otherwise.
     */
    public function assertExists(int $departmentId): void
    {
        if (!$this->repo->exists($departmentId)) {
            throw new DomainException('Selected department does not exist.');
        }
    }

    /**
     * Ensure the department is a College (is_college=TRUE). Throw otherwise.
     */
    public function assertCollege(int $departmentId): void
    {
        $this->assertExists($departmentId);
        if (!$this->repo->isCollege($departmentId)) {
            throw new DomainException('This action requires a College, but a Department was selected.');
        }
    }
}
