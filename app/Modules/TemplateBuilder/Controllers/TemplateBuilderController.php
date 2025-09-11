<?php
// /app/Modules/TemplateBuilder/Controllers/TemplateBuilderController.php
declare(strict_types=1);

namespace App\Modules\TemplateBuilder\Controllers;

use App\Interfaces\StorageInterface;
use App\Security\RBAC;
use App\Config\Permissions;
use App\Models\UserModel;

final class TemplateBuilderController
{
    private StorageInterface $db;
    private UserModel $userModel;

    /**
     * Roles not exclusive to a single college (show list of college “folders”)
     * e.g., VPAA, Admin, Librarian, QA, Registrar, etc.
     */
    private const NON_COLLEGE_BOUND_ROLES = [
        'VPAA',
        'Admin',
        'Librarian',
    ];

    /**
     * Roles exclusive to a college (show Drive-like template grid + right pane)
     * e.g., Dean, College Dean, Program Chair, Department Chair, etc.
     */
    private const COLLEGE_BOUND_ROLES = [
        'Dean',
        'College Secretary',
        'Chair',
    ];

    public function __construct(StorageInterface $db)
    {
        $this->db = $db;
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->userModel = new UserModel($db);
    }

    public function index(): string
    {
        // 1) RBAC Gate
        (new RBAC($this->db))->require((string)$_SESSION['user_id'], Permissions::TEMPLATEBUILDER_VIEW);

        // 2) User context (role_name, college info)
        $user = $this->userModel->getUserProfile((string)$_SESSION['user_id']);
        $roleName = trim((string)($user['role_name'] ?? ''));
        $userCollegeId = $user['college_id'] ?? null;
        $userCollegeShort = $user['college_short_name'] ?? null;
        $userCollegeFull  = $user['college_name'] ?? null;

        // 3) Mode resolution by role arrays (NOT by checking null college)
        $mode = 'multi-college'; // default
        if (in_array($roleName, self::COLLEGE_BOUND_ROLES, true)) {
            $mode = 'college-drive';
        } elseif (in_array($roleName, self::NON_COLLEGE_BOUND_ROLES, true)) {
            $mode = 'multi-college';
        }

        // 4) Allow “entering a college folder” from multi-college view
        //    ?college={id} switches to college-drive view
        $selectedCollegeId = null;
        if (isset($_GET['college']) && ctype_digit((string)$_GET['college'])) {
            $selectedCollegeId = (int)$_GET['college'];
            $mode = 'college-drive';
        }

        // 5) Mock data for now — replace with model later
        //    Colleges shown in folder list (for non-college-bound roles)
        $colleges = [
            ['college_id' => 1, 'short_name' => 'CCS', 'college_name' => 'College of Computer Studies'],
            ['college_id' => 2, 'short_name' => 'CBA', 'college_name' => 'College of Business Administration'],
            ['college_id' => 3, 'short_name' => 'CAS', 'college_name' => 'College of Arts and Sciences'],
        ];

        //    Templates shown in the drive view (for selected college or user’s own)
        $currentCollege = null;
        if ($mode === 'college-drive') {
            // Pick by GET ?college=... if present; otherwise default to user’s college (if any)
            $cid = $selectedCollegeId ?? (is_numeric($userCollegeId) ? (int)$userCollegeId : null);
            if ($cid !== null) {
                // Map from $colleges mock to get names
                foreach ($colleges as $c) {
                    if ((int)$c['college_id'] === (int)$cid) {
                        $currentCollege = $c;
                        break;
                    }
                }
            } elseif ($userCollegeShort || $userCollegeFull) {
                $currentCollege = [
                    'college_id'   => (int)($userCollegeId ?? 0),
                    'short_name'   => (string)($userCollegeShort ?? ''),
                    'college_name' => (string)($userCollegeFull ?? ''),
                ];
            }

            // Mock templates
            $templates = [
                [
                    'template_id' => 101,
                    'title'       => 'Syllabus — Introduction to Programming',
                    'course_code' => 'CS101',
                    'updated_at'  => '2025-08-30 14:05',
                    'owner'       => 'Dept Template',
                ],
                [
                    'template_id' => 102,
                    'title'       => 'Syllabus — Data Structures',
                    'course_code' => 'CS201',
                    'updated_at'  => '2025-09-02 10:11',
                    'owner'       => 'Dept Template',
                ],
                [
                    'template_id' => 103,
                    'title'       => 'Syllabus — Database Systems',
                    'course_code' => 'CS301',
                    'updated_at'  => '2025-09-08 09:00',
                    'owner'       => 'Dept Template',
                ],
            ];
        } else {
            $templates = [];
        }

        // 6) Render view
        ob_start();
        $viewData = [
            'mode'           => $mode,
            'roleName'       => $roleName,
            'colleges'       => $colleges,
            'templates'      => $templates,
            'currentCollege' => $currentCollege,
        ];
        extract($viewData, \EXTR_SKIP);
        require dirname(__DIR__) . '/Views/index.php';
        return (string)ob_get_clean();
    }
}
