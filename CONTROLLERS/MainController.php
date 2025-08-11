<?php

namespace Controllers;

use Models\Main;
use Config\Database;

require_once __DIR__ . '/../CONFIG/database.php';
require_once __DIR__ . '/../MODELS/Main.php';

class MainController
{
    private $userModel;

    public function __construct()
    {
        $db = new Database();
        $this->userModel = new Main($db->connect());
    }
    public function checkIfInDatabase($id)
    {
        return $this->userModel->checkIfInDatabase($id);
    }
    public function checkIfIsChefs($id)
    {
        return $this->userModel->checkIfIsChefs($id);
    }
    public function checkIfIsRef($id)
    {
        return $this->userModel->checkIfIsRef($id);
    }
    public function checkDomaine($id)
    {
        return $this->userModel->checkDomaine($id);
    }
    public function giveAllDomaine()
    {
        return $this->userModel->giveAllDomaine();
    }
    public function insertFile($userId, $filePath, $fileSize, $fileType, $domaineId, $currentMissionId)
    {
        return $this->userModel->insertFile($userId, $filePath, $fileSize, $fileType, $domaineId, $currentMissionId);
    }
    public function createMission($name, $description, $deadline, $domainId, $imageUrl, $difficulty, $fileType, $created_at, $attachedFilePath)
    {
        return $this->userModel->createMission($name, $description, $deadline, $domainId, $imageUrl, $difficulty, $fileType, $created_at, $attachedFilePath);
    }
    public function updateMission($nameBefore, $name, $description, $deadline, $difficulty, $fileType)
    {
        return $this->userModel->updateMission($nameBefore, $name, $description, $deadline, $difficulty, $fileType);
    }
    public function getMissionsByDomainAdmin($domainId)
    {
        return $this->userModel->getMissionsByDomainAdmin($domainId);
    }
    public function getMissionsByDomain($domainId)
    {
        return $this->userModel->getMissionsByDomain($domainId);
    }
    public function getMissionsByDomainCompleted($domainId)
    {
        return $this->userModel->getMissionsByDomainCompleted($domainId);
    }
    public function getMissionsByDomainPending($domainId)
    {
        return $this->userModel->getMissionsByDomainPending($domainId);
    }
    public function getMissionsByDomainAvailable($domainId)
    {
        return $this->userModel->getMissionsByDomainAvailable($domainId);
    }
    public function getPeopleByDomain($domainId)
    {
        return $this->userModel->getPeopleByDomain($domainId);
    }
    public function acceptMission($missionId, $userId, $missionName)
    {
        // Vérifier le nombre de missions actives
        $activeMissions = $this->hasActiveMission($userId);

        if ($activeMissions >= 3) {
            return false;
        }

        return $this->userModel->acceptMission($missionId, $userId, $missionName);
    }
    public function getUsername($userId)
    {
        return $this->userModel->getUsername($userId);
    }
    public function getActiveMissions($userId)
    {
        return $this->userModel->getActiveMissions($userId);
    }
    public function getDomainName($domainId)
    {
        return $this->userModel->getDomainName($domainId);
    }
    public function addMemberToDomain($memberId, $memberName, $domainId, $secondaryDomaine)
    {
        return $this->userModel->addMemberToDomain($memberId, $memberName, $domainId, $secondaryDomaine);
    }
    public function getMembersByDomain($domainId)
    {
        return $this->userModel->getMembersByDomain($domainId);
    }
    public function getMembersByDomainSecondary($domainId)
    {
        return $this->userModel->getMembersByDomainSecondary($domainId);
    }
    public function getCurrentMission($userId)
    {
        return $this->userModel->getCurrentMission($userId);
    }

    public function getMissionById($missionId)
    {
        return $this->userModel->getMissionById($missionId);
    }
    public function hasActiveMission($userId)
    {
        return $this->userModel->hasActiveMission($userId);
    }
    public function validateMission($missionId)
    {
        return $this->userModel->validateMission($missionId);
    }
    public function rejectMission($missionId)
    {
        return $this->userModel->rejectMission($missionId);
    }
    public function checkFileExists($missionId)
    {
        return $this->userModel->checkFileExists($missionId);
    }
    public function getUploadInfo($filePath)
    {
        return $this->userModel->getUploadInfo($filePath);
    }
    public function deleteFileFromDatabase($filePath)
    {
        return $this->userModel->deleteFileFromDatabase($filePath);
    }
    public function getChefsForUser($userId)
    {
        return $this->userModel->getChefsForUser($userId);
    }

    public function getChefsForDomain($domainId) {
        return $this->userModel->getChefsForDomain($domainId);
    }

    public function getMissionsForUser($userId)
    {
        return $this->userModel->getMissionsForUser($userId);
    }

    public function getUploadsForUser($userId)
    {
        return $this->userModel->getUploadsForUser($userId);
    }
    public function getUserById($userId)
    {
        return $this->userModel->getUserById($userId);
    }

    public function getUserByUsername($username)
    {
        return $this->userModel->getUserByUsername($username);
    }
    public function uploadImage($image)
    {
        $targetDir = __DIR__ . '/../mission_img/';
        $fileName = uniqid() . '_' . basename($image['name']);
        $targetPath = $targetDir . $fileName;

        if (move_uploaded_file($image['tmp_name'], $targetPath)) {
            return 'mission_img/' . $fileName;
        } else {
            return null;
        }
    }

    public function deleteMission($missionId)
    {
        return $this->userModel->deleteMission($missionId);
    }

    public function getAllMissions()
    {
        return $this->userModel->getAllMissions();
    }

    public function getAllMembers()
    {
        return $this->userModel->getAllMembers();
    }
    public function updateDomaineSecondaryForChef($userId, $newDomainId)
    {
        return $this->userModel->updateDomaineSecondaryForChef($userId, $newDomainId);
    }

    public function addComment($missionId, $userId, $content)
    {
        return $this->userModel->addComment($missionId, $userId, $content);
    }
    public function getMissionByCommentId($commentId)
    {
        return $this->userModel->getMissionByCommentId($commentId);
    }

    public function getComments($missionId)
    {
        return $this->userModel->getComments($missionId);
    }

    public function addReaction($commentId, $userId, $reactionType)
    {
        return $this->userModel->addReaction($commentId, $userId, $reactionType);
    }

    public function getReactions($commentId)
    {
        return $this->userModel->getReactions($commentId);
    }
    public function uploadFile($file)
    {
        return $this->userModel->uploadFile($file);
    }

    public function getUserReactions($commentId, $userId)
    {
        return $this->userModel->getUserReactions($commentId, $userId);
    }

    public function getDetailedUploadInfo($missionId, $domainId = null)
    {
        return $this->userModel->getDetailedUploadInfo($missionId, $domainId);
    }

    public function addAsset3D($name, $missionId, $userId, $imagePath) {
        return $this->userModel->addAsset3D($name, $missionId, $userId, $imagePath);
    }

    public function getAllAssets3D() {
        return $this->userModel->getAllAssets3D();
    }

    public function searchAssets3D($search) {
        return $this->userModel->searchAssets3D($search);
    }

    public function getAssignedMissionsWithUsers() {
        return $this->userModel->getAssignedMissionsWithUsers();
    }

    public function deleteAsset3D($assetId) {
        return $this->userModel->deleteAsset3D($assetId);
    }

    public function createFaq($question, $answer, $userId, $domaineId = null) {
        return $this->userModel->createFaq($question, $answer, $userId, $domaineId);
    }

    public function searchFaqs($searchQuery = '', $limit = null, $offset = 0, $domaineId = null) {
        return $this->userModel->searchFaqs($searchQuery, $limit, $offset, $domaineId);
    }

    public function countFaqs($searchQuery = '', $domaineId = null) {
        return $this->userModel->countFaqs($searchQuery, $domaineId);
    }

    public function checkIncrementationUploads($missionId) {
        return $this->userModel->checkIncrementationUploads($missionId);
    }

    public function createIncrementationUploads($missionId) {
        return $this->userModel->createIncrementationUploads($missionId);
    }

    public function incrementUploads($missionId) {
        return $this->userModel->incrementUploads($missionId);
    }

    public function filterAndSortFiles($files, $searchQuery = '', $sortBy = 'date', $order = 'desc') {
        return $this->userModel->filterAndSortFiles($files, $searchQuery, $sortBy, $order);
    }
    public function removeMember($userId) {
        return $this->userModel->removeMember($userId);
    }

    public function deleteComment($commentId) {
        return $this->userModel->deleteComment($commentId);
    }
}
