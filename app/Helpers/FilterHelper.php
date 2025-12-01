<?php

namespace App\Helpers;

class FilterHelper
{
    /**
     * Filter roles by level.
     * @param array $roles Array of roles (each must have 'role_level')
     * @param int $currentRoleLevel
     * @param bool $isAAO
     * @return array
     */
    public static function filterRolesByLevel(array $roles, int $currentRoleLevel, bool $isAAO): array
    {
        return array_filter($roles, function ($role) use ($currentRoleLevel, $isAAO) {
            $level = (int)($role['role_level'] ?? 0);
            return $isAAO ? $level <= $currentRoleLevel : $level < $currentRoleLevel;
        });
    }

    /**
     * Filter colleges to only the user's college (if not AAO and college is set).
     * @param array $colleges
     * @param int|string|null $userCollegeId
     * @param bool $isAAO
     * @return array
     */
    public static function filterColleges(array $colleges, $userCollegeId, bool $isAAO): array
    {
        if ($isAAO || !$userCollegeId) {
            return $colleges;
        }
        return array_filter($colleges, function ($college) use ($userCollegeId) {
            return (int)$college['department_id'] === (int)$userCollegeId;
        });
    }
}