<?php
//root/app/controllers/ProgramController.php
require_once __DIR__ . '/../models/ProgramModel.php';

class ProgramController{
    private ProgramModel $programModel;
    private $db;

    public function __construct(ProgramModel $programModel, StorageInterface $db) {
        $this->programModel = $programModel;
        $this->db = $db;
    }

    public function index() {
        try {
            // check permissions here
            $canView = $this->db->checkPermission('ProgramViewing');
            if (!$canView) {
                throw new Exception("You don't have permission for this action.");
            }
            $canEdit = $this->db->checkPermission('ProgramModification');
            $canDelete = $this->db-checkPermission('ProgramDeletion');

            // Pass all data to the view
            $programs = $this->programModel->getAllPrograms();
            require_once __DIR__ . '/../views';
        } catch (Exception $e) {
            $error = $e->getMessage();
            //require __DIR__ . '/../views/error.php'; // fallback error view
        }
    }

    public function create($data) {
        $programId = $this->model->createProgram($data);
        $chairIdNo = trim((string)($_POST['chair_id_no'] ?? ''));
        if ($chairIdNo !== '') {
            try {
                (new \App\Services\AssignmentsService($this->db))->setProgramChair((int)$programId, $chairIdNo);
                \App\Helpers\FlashHelper::set('success', 'Program created. Chair assigned.');
            } catch (\DomainException $e) {
                \App\Helpers\FlashHelper::set('warning', 'Program created, but chair not assigned: ' . $e->getMessage());
            }
        } else {
            \App\Helpers\FlashHelper::set('success', 'Program created.');
        }
    }

    public function edit() {
        $chairIdNo = trim((string)($_POST['chair_id_no'] ?? ''));
        try {
            (new \App\Services\AssignmentsService($this->db))->setProgramChair($programId, $chairIdNo !== '' ? $chairIdNo : null);
            \App\Helpers\FlashHelper::set('success', 'Program updated.');
        } catch (\DomainException $e) {
            \App\Helpers\FlashHelper::set('warning', 'Program updated, but chair not assigned: ' . $e->getMessage());
        }
    }

}
?>