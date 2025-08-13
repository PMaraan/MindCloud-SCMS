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

    public function index() [
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
            require_once __DIR__ . '/../views'
        } catch (Exception $e) {
            $error = $e->getMessage();
            //require __DIR__ . '/../views/error.php'; // fallback error view
        }
    ]

}
?>