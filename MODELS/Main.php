<?php

namespace Models;

class Main
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function checkIfInDatabase($id)
    {
        try {
            $query = "SELECT * FROM user WHERE userId = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function checkIfIsChefs($id)
    {
        try {
            $query = "SELECT * FROM user WHERE userId = :id AND chefs = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    public function checkIfIsRef($id)
    {
        try {
            $query = "SELECT * FROM user WHERE userId = :id AND referent = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function checkDomaine($id)
    {
        try {
            $query = "SELECT * FROM domaine WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function giveAllDomaine()
    {
        try {
            $query = "SELECT * FROM domaine";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    public function insertFile($userId, $filePath, $fileSize, $fileType, $domaineId, $currentMissionId)
    {
        try {
            $query = "INSERT INTO uploads (userId, pathFile, file_size, file_type, domaine_id, missionId) 
                      VALUES (:userId, :filePath, :fileSize, :fileType, :domaineId, :currentMissionId)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $stmt->bindParam(':filePath', $filePath, \PDO::PARAM_STR);
            $stmt->bindParam(':fileSize', $fileSize, \PDO::PARAM_INT);
            $stmt->bindParam(':fileType', $fileType, \PDO::PARAM_STR);
            $stmt->bindParam(':domaineId', $domaineId, \PDO::PARAM_INT);
            $stmt->bindParam(':currentMissionId', $currentMissionId, \PDO::PARAM_INT);
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function createMission($name, $description, $deadline, $domainId, $imageUrl, $difficulty, $fileType, $created_at, $attachedFilePath)
    {
        try {
            $query = "INSERT INTO missions (name, description, deadline, domaine_id, image_url, difficulty, typeFichier, created_at, attached_file) 
                      VALUES (:name, :description, :deadline, :domainId, :imageUrl, :difficulty, :fileType, :created_at, :attached_file)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, \PDO::PARAM_STR);
            $stmt->bindParam(':deadline', $deadline, \PDO::PARAM_STR);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->bindParam(':imageUrl', $imageUrl, \PDO::PARAM_STR);
            $stmt->bindParam(':difficulty', $difficulty, \PDO::PARAM_STR);
            $stmt->bindParam(':fileType', $fileType, \PDO::PARAM_STR);
            $stmt->bindParam(':created_at', $created_at, \PDO::PARAM_STR);
            $stmt->bindParam(':attached_file', $attachedFilePath, \PDO::PARAM_STR);
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function updateMission($nameBefore, $name, $description, $deadline, $difficulty, $fileType)
    {
        try {
            $query = "UPDATE missions
                  SET name = :name,
                      description = :description, 
                      deadline = :deadline,
                      difficulty = :difficulty, 
                      typeFichier = :fileType
                  WHERE name = :nameBefore";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name, \PDO::PARAM_STR);
            $stmt->bindParam(':nameBefore', $nameBefore, \PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, \PDO::PARAM_STR);
            $stmt->bindParam(':deadline', $deadline, \PDO::PARAM_STR);
            $stmt->bindParam(':difficulty', $difficulty, \PDO::PARAM_STR);
            $stmt->bindParam(':fileType', $fileType, \PDO::PARAM_STR);

            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getMissionsByDomainAdmin($domainId)
    {
        try {
            $query = "SELECT * FROM missions 
              WHERE domaine_id = :domainId 
              AND status = 'assigned'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    public function getMissionsByDomain($domainId)
    {
        try {
            $query = "SELECT * FROM missions 
              WHERE domaine_id = :domainId 
              AND status != 'completed'
              AND created_at <= CURRENT_TIMESTAMP()
              AND deadline >= CURRENT_TIMESTAMP()
              ORDER BY CASE 
                  WHEN status = 'available' THEN 1
                  WHEN status = 'assigned' THEN 2 
                  ELSE 3
              END";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getMissionsByDomainCompleted($domainId)
    {
        try {
            $query = "SELECT * FROM missions WHERE domaine_id = :domainId AND status = 'completed'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getMissionsByDomainPending($domainId)
    {
        try {
            $query = "SELECT * FROM missions WHERE domaine_id = :domainId AND status = 'assigned'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getMissionsByDomainAvailable($domainId)
    {
        try {
            $query = "SELECT * FROM missions WHERE domaine_id = :domainId AND status = 'available'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getPeopleByDomain($domainId)
    {
        try {
            $query = "SELECT * FROM user WHERE domaine_id = :domainId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function acceptMission($missionId, $userId, $missionName)
    {
        try {
            // Vérifier si l'utilisateur a déjà une mission avec le même nom
            $query = "SELECT COUNT(*) FROM missions WHERE assignee_id = :userId AND name = :missionName AND status = 'assigned'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $stmt->bindParam(':missionName', $missionName, \PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                return false;
            }

            // Vérifier si l'utilisateur a déjà une mission avec le même nom
            $query = "SELECT COUNT(*) FROM missions WHERE assignee_id = :userId AND name = :missionName AND status = 'completed'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $stmt->bindParam(':missionName', $missionName, \PDO::PARAM_STR);
            $stmt->execute();
            $countDeux = $stmt->fetchColumn();

            if ($countDeux > 0) {
                return false;
            }

            // Vérifier si la deadline n'est pas dépassée
            $query = "SELECT deadline FROM missions WHERE id = :missionId AND status = 'available'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $stmt->execute();
            $mission = $stmt->fetch();

            if (!$mission) {
                return false;
            }

            $deadline = new \DateTime($mission['deadline']);
            $now = new \DateTime();

            if ($deadline < $now) {
                return false;
            }

            // Vérifier le nombre de missions actives (limite à 3 par exemple)
            $activeMissions = $this->hasActiveMission($userId);
            if ($activeMissions >= 3) {
                return false;
            }

            $query = "UPDATE missions SET assignee_id = :userId, status = 'assigned' 
             WHERE id = :missionId AND status = 'available'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getActiveMissions($userId)
    {
        try {
            $query = "SELECT * FROM missions WHERE assignee_id = :userId AND status = 'assigned' ORDER BY deadline ASC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getUsername($userId)
    {
        try {
            $userQuery = "SELECT username FROM user WHERE userId = :userId";
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $userStmt->execute();
            $user = $userStmt->fetch();
            return $user ? $user['username'] : '';
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return '';
        }
    }

    public function getDomainName($domainId)
    {
        try {
            $query = "SELECT name FROM domaine WHERE id = :domainId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ? $result['name'] : '';
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return '';
        }
    }

    public function addMemberToDomain($memberId, $memberName, $domainId, $secondaryDomaine)
    {
        try {
            $query = "INSERT INTO user (userId, username, domaine_id, domaine_id_secondary) VALUES (:memberId, :memberName, :domainId, :secondaryDomaine)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':memberId', $memberId);
            $stmt->bindParam(':memberName', $memberName);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->bindParam(':secondaryDomaine', $secondaryDomaine, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getMembersByDomain($domainId)
    {
        try {
            $query = "SELECT * FROM user WHERE domaine_id = :domainId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getMembersByDomainSecondary($domainId)
    {
        try {
            $query = "SELECT * FROM user WHERE domaine_id_secondary = :domainId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getCurrentMission($userId)
    {
        try {
            $query = "SELECT * FROM missions WHERE assignee_id = :userId AND status = 'assigned' LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function getMissionById($missionId)
    {
        try {
            $query = "SELECT * FROM missions WHERE id = :missionId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    public function hasActiveMission($userId)
    {
        try {
            $query = "SELECT COUNT(*) FROM missions WHERE assignee_id = :userId AND status = 'assigned'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return 0;
        }
    }
    public function validateMission($missionId)
    {
        try {
            $query = "UPDATE missions SET status = 'completed' WHERE id = :missionId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function rejectMission($missionId)
    {
        try {
            $query = "UPDATE missions SET status = 'available', assignee_id = NULL WHERE id = :missionId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function checkFileExists($missionId)
    {
        try {
            $query = "SELECT * FROM uploads WHERE missionId = :missionId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function getUploadInfo($filePath)
    {
        try {
            $query = "SELECT u.username, u.userId, m.name AS mission_name, m.id AS mission_id, up.created_at 
              FROM uploads up
              JOIN user u ON up.userId = u.userId
              LEFT JOIN missions m ON up.missionId = m.id
              WHERE up.pathFile = :filePath";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':filePath', $filePath);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result) {
                return [
                    'user' => [
                        'username' => $result['username'],
                        'userId' => $result['userId']
                    ],
                    'mission' => [
                        'name' => $result['mission_name'] ?? 'N/A',
                        'id' => $result['mission_id'] ?? 'N/A'
                    ],
                    'created_at' => $result['created_at']
                ];
            } else {
                return [
                    'user' => [
                        'username' => 'Unknown',
                        'userId' => null
                    ],
                    'mission' => [
                        'name' => 'N/A',
                        'id' => 'N/A'
                    ],
                    'created_at' => 'N/A'
                ];
            }
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [
                'user' => [
                    'username' => 'Unknown',
                    'userId' => null
                ],
                'mission' => [
                    'name' => 'N/A',
                    'id' => 'N/A'
                ],
                'created_at' => 'N/A'
            ];
        }
    }
    public function deleteFileFromDatabase($filePath)
    {
        try {
            $query = "DELETE FROM uploads WHERE pathFile = :filePath";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':filePath', $filePath);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function getChefsForUser($userId)
    {
        try {
            $query = "SELECT u.username 
                  FROM user u
                  WHERE u.chefs = 1 AND u.domaine_id = (
                      SELECT domaine_id 
                      FROM user 
                      WHERE userId = :userId
                  )";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getChefsForDomain($domainId)
    {
        try {
            $query = "SELECT u.* FROM user u 
                      WHERE u.chefs = 1 
                      AND u.domaine_id = :domainId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':domainId', $domainId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getMissionsForUser($userId)
    {
        try {
            $query = "SELECT m.* 
                  FROM missions m
                  WHERE m.assignee_id = :userId AND m.status = 'completed'";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getUploadsForUser($userId)
    {
        try {
            $query = "SELECT u.* 
                  FROM uploads u
                  WHERE u.userId = :userId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    public function getUserById($userId)
    {
        try {
            $query = "SELECT u.*, d.name AS domain_name 
                  FROM user u
                  JOIN domaine d ON u.domaine_id = d.id
                  WHERE u.userId = :userId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function getUserByUsername($username)
    {
        try {
            $query = "SELECT u.*, d.name AS domain_name 
                  FROM user u
                  JOIN domaine d ON u.domaine_id = d.id
                  WHERE u.username = :username";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function deleteMission($missionIds)
    {
        if (!is_array($missionIds)) {
            $missionIds = [$missionIds];
        }

        try {
            foreach ($missionIds as $missionId) {
                // Récupérer le chemin de l'image de référence et du fichier joint associés à la mission
                $query = "SELECT image_url, attached_file FROM missions WHERE id = :missionId";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
                $stmt->execute();
                $mission = $stmt->fetch();
                $imagePath = $mission['image_url'];
                $attachedFilePath = $mission['attached_file'];

                // Supprimer la mission de la base de données
                $query = "DELETE FROM missions WHERE id = :missionId";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
                $deleted = $stmt->execute();

                // Supprimer l'image de référence et le fichier joint associés à la mission
                if ($deleted) {
                    if ($imagePath) {
                        $fullImagePath = __DIR__ . '/../' . $imagePath;
                        // Vérifier si d'autres missions utilisent la même image
                        $query = "SELECT COUNT(*) as count FROM missions WHERE image_url = :imagePath AND id != :missionId";
                        $stmt = $this->db->prepare($query);
                        $stmt->bindParam(':imagePath', $imagePath, \PDO::PARAM_STR);
                        $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
                        $stmt->execute();
                        $result = $stmt->fetch();

                        // Ne supprimer l'image que si aucune autre mission ne l'utilise
                        if ($result['count'] == 0 && file_exists($fullImagePath)) {
                            unlink($fullImagePath);
                        }
                    }

                    if ($attachedFilePath) {
                        $fullAttachedFilePath = __DIR__ . '/../' . $attachedFilePath;
                        if (file_exists($fullAttachedFilePath)) {
                            unlink($fullAttachedFilePath);
                        }
                    }
                }
            }

            return true;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getAllMissions()
    {
        try {
            $query = "SELECT * FROM missions";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getAllMembers()
    {
        try {
            $query = "SELECT * FROM user";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function updateDomaineSecondaryForChef($userId, $newDomainId)
    {
        try {
            $query = "UPDATE user SET domaine_id_secondary = :newDomainId WHERE userId = :userId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $stmt->bindParam(':newDomainId', $newDomainId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function addComment($missionId, $userId, $content)
    {
        try {
            $query = "INSERT INTO comments (mission_id, user_id, content) 
                      VALUES (:missionId, :userId, :content)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $stmt->bindParam(':content', $content, \PDO::PARAM_STR);
            $stmt->execute();
            return $this->db->lastInsertId(); // Retourne l'ID du commentaire créé
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getMissionByCommentId($commentId)
    {
        try {
            $query = "SELECT m.* FROM missions m 
                      JOIN comments c ON c.mission_id = m.id 
                      WHERE c.id = :commentId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':commentId', $commentId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function getComments($missionId)
    {
        try {
            $query = "SELECT c.*, u.username, u.userId 
                      FROM comments c 
                      JOIN user u ON c.user_id = u.userId 
                      WHERE c.mission_id = :missionId 
                      ORDER BY c.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function addReaction($commentId, $userId, $reactionType)
    {
        try {
            $checkQuery = "SELECT * FROM reactions 
                          WHERE comment_id = :commentId 
                          AND user_id = :userId 
                          AND reaction_type = :reactionType";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':commentId', $commentId, \PDO::PARAM_INT);
            $checkStmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $checkStmt->bindParam(':reactionType', $reactionType, \PDO::PARAM_STR);
            $checkStmt->execute();

            if ($checkStmt->fetch()) {
                $deleteQuery = "DELETE FROM reactions 
                              WHERE comment_id = :commentId 
                              AND user_id = :userId 
                              AND reaction_type = :reactionType";
                $deleteStmt = $this->db->prepare($deleteQuery);
                $deleteStmt->bindParam(':commentId', $commentId, \PDO::PARAM_INT);
                $deleteStmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
                $deleteStmt->bindParam(':reactionType', $reactionType, \PDO::PARAM_STR);
                return $deleteStmt->execute();
            } else {
                $insertQuery = "INSERT INTO reactions (comment_id, user_id, reaction_type) 
                              VALUES (:commentId, :userId, :reactionType)";
                $insertStmt = $this->db->prepare($insertQuery);
                $insertStmt->bindParam(':commentId', $commentId, \PDO::PARAM_INT);
                $insertStmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
                $insertStmt->bindParam(':reactionType', $reactionType, \PDO::PARAM_STR);
                return $insertStmt->execute();
            }
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getReactions($commentId)
    {
        try {
            $query = "SELECT reaction_type, COUNT(*) as count 
                      FROM reactions 
                      WHERE comment_id = :commentId 
                      GROUP BY reaction_type";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':commentId', $commentId, \PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll();

            $reactions = [
                'like' => 0,
                'heart' => 0
            ];

            foreach ($results as $result) {
                $reactions[$result['reaction_type']] = (int)$result['count'];
            }

            return $reactions;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return ['like' => 0, 'heart' => 0];
        }
    }

    public function getUserReactions($commentId, $userId)
    {
        try {
            $query = "SELECT reaction_type 
                      FROM reactions 
                      WHERE comment_id = :commentId 
                      AND user_id = :userId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':commentId', $commentId, \PDO::PARAM_INT);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function uploadFile($file)
    {
        $uploadDir = __DIR__ . '/../mission_files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $uploadFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
            return 'mission_files/' . $fileName;
        } else {
            return null;
        }
    }

    public function getDetailedUploadInfo($missionId = null, $domainId = null)
    {
        try {
            $sql = "SELECT 
                    m.name as mission_name,
                    m.description as mission_description,
                    m.difficulty as mission_difficulty,
                    m.status as mission_status,
                    m.assignee_id,
                    m.created_at as mission_created_at,
                    u.pathFile as file_path,
                    usr.username as uploader_name
                FROM missions m
                LEFT JOIN uploads u ON m.id = u.missionId
                LEFT JOIN user usr ON u.userId = usr.userId
                WHERE m.domaine_id = :domainId";

            $params = [':domainId' => $domainId];

            if ($missionId) {
                $sql .= " AND m.id = :missionId";
                $params[':missionId'] = $missionId;
            }

            $sql .= " ORDER BY m.name, u.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error getting detailed upload info: " . $e->getMessage());
            return [];
        }
    }

    public function addAsset3D($name, $missionId, $userId, $imagePath)
    {
        try {
            $query = "INSERT INTO assets3d (name, mission_id, user_id, image_path, created_at) 
                      VALUES (:name, :missionId, :userId, :imagePath, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':name' => $name,
                ':missionId' => $missionId,
                ':userId' => $userId,
                ':imagePath' => $imagePath
            ]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function getAllAssets3D()
    {
        try {
            $query = "SELECT a.*, m.name as mission_name, u.username as user_name 
                     FROM assets3d a 
                     LEFT JOIN missions m ON a.mission_id = m.id 
                     LEFT JOIN user u ON a.user_id = u.userId 
                     ORDER BY a.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function searchAssets3D($search)
    {
        try {
            $query = "SELECT a.*, m.name as mission_name, u.username as user_name 
                     FROM assets3d a 
                     LEFT JOIN missions m ON a.mission_id = m.id 
                     LEFT JOIN user u ON a.user_id = u.userId 
                     WHERE m.name LIKE :search OR u.username LIKE :search 
                     ORDER BY a.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':search' => "%$search%"]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function getAssignedMissionsWithUsers()
    {
        try {
            $query = "SELECT m.*, u.username 
                     FROM missions m 
                     JOIN user u ON m.assignee_id = u.userId 
                     WHERE m.status != 'available' 
                     ORDER BY m.name ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function deleteAsset3D($assetId)
    {
        try {
            $query = "SELECT image_path FROM assets3d WHERE id = :assetId";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':assetId' => $assetId]);
            $asset = $stmt->fetch();

            if ($asset) {
                $query = "DELETE FROM assets3d WHERE id = :assetId";
                $stmt = $this->db->prepare($query);
                $success = $stmt->execute([':assetId' => $assetId]);

                if ($success) {
                    $fullImagePath = __DIR__ . '/../' . $asset['image_path'];
                    if (file_exists($fullImagePath)) {
                        unlink($fullImagePath);
                    }
                    return true;
                }
            }
            return false;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function createFaq($question, $answer, $userId, $domaineId = null)
    {
        try {
            $query = "INSERT INTO faq (question, answer, created_by, domaine_id) 
                      VALUES (:question, :answer, :userId, :domaineId)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':question' => $question,
                ':answer' => $answer,
                ':userId' => $userId,
                ':domaineId' => $domaineId
            ]);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function searchFaqs($searchQuery = '', $limit = null, $offset = 0, $domaineId = null)
    {
        try {
            $query = "SELECT f.*, u.username as creator_name, d.name as domain_name
                     FROM faq f 
                     LEFT JOIN user u ON f.created_by = u.userId
                     LEFT JOIN domaine d ON f.domaine_id = d.id
                     WHERE 1=1";

            $params = [];

            if (!empty($searchQuery)) {
                $query .= " AND (f.question LIKE :search OR f.answer LIKE :search)";
                $params[':search'] = "%$searchQuery%";
            }

            if ($domaineId) {
                $query .= " AND f.domaine_id = :domaineId";
                $params[':domaineId'] = $domaineId;
            }

            $query .= " ORDER BY f.created_at DESC";

            if ($limit !== null) {
                $query .= " LIMIT :limit OFFSET :offset";
            }

            $stmt = $this->db->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            if ($limit !== null) {
                $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return $results;
        } catch (\PDOException $e) {
            error_log("FAQ Error: " . $e->getMessage());
            error_log("FAQ Query: " . $query);
            return [];
        }
    }

    public function countFaqs($searchQuery = '', $domaineId = null)
    {
        try {
            $query = "SELECT COUNT(*) FROM faq f WHERE 1=1";
            $params = [];

            if (!empty($searchQuery)) {
                $query .= " AND (f.question LIKE :search OR f.answer LIKE :search)";
                $params[':search'] = "%$searchQuery%";
            }

            if ($domaineId) {
                $query .= " AND f.domaine_id = :domaineId";
                $params[':domaineId'] = $domaineId;
            }

            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return 0;
        }
    }

    public function checkIncrementationUploads($missionId)
    {
        try {
            $query = "SELECT * FROM incrementation_uploads WHERE mission_id = :missionId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function createIncrementationUploads($missionId)
    {
        try {
            $query = "INSERT INTO incrementation_uploads (mission_id, incrementation) VALUES (:missionId, 0)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function incrementUploads($missionId)
    {
        try {
            $query = "UPDATE incrementation_uploads SET incrementation = incrementation + 1 WHERE mission_id = :missionId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $success = $stmt->execute();

            if ($success) {
                // Récupérer les informations de la mission
                $missionQuery = "SELECT m.*, u.username, d.name as domain_name 
                               FROM missions m 
                               JOIN user u ON m.assignee_id = u.userId 
                               JOIN domaine d ON m.domaine_id = d.id 
                               WHERE m.id = :missionId";
                $missionStmt = $this->db->prepare($missionQuery);
                $missionStmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
                $missionStmt->execute();
                $mission = $missionStmt->fetch();

                // Récupérer les chefs du domaine
                $chefs = $this->getChefsForDomain($mission['domaine_id']);
                $chefsMentions = array_map(function ($chef) {
                    return "<@" . $chef['userId'] . ">";
                }, $chefs);

                // Envoyer le webhook Discord
                try {
                    $webhookUrl = 'https://discord.com/api/webhooks/1369607509815590922/ixWE0gh0GMrmwJmCBUlhcXE59emwM-JXNsw-J8mcv5OfC-iXImcli-ZpYSA8v6lPylpK';

                    $messageContent = sprintf(
                        "🔄 **Re-upload détecté !**\n" .
                            "> Mission : %s\n" .
                            "> Par : <@%s> (%s)\n" .
                            "> Domaine : %s\n" .
                            "\n**Chefs concernés :**\n%s\n" .
                            "\n[Voir la mission](https://depots.neopolyworks.fr/info_mission.php?id=%d)",
                        $mission['name'],
                        $mission['assignee_id'],
                        $mission['username'],
                        $mission['domain_name'],
                        implode(", ", $chefsMentions),
                        $missionId
                    );

                    $webhookData = [
                        'content' => $messageContent,
                        'embeds' => [[
                            'color' => hexdec('FF9900'),
                            'footer' => [
                                'text' => 'Re-upload #' . $this->getUploadCount($missionId)
                            ],
                            'timestamp' => date('c')
                        ]]
                    ];

                    $ch = curl_init($webhookUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_exec($ch);
                    curl_close($ch);
                } catch (\Exception $e) {
                    error_log("Erreur lors de l'envoi au webhook: " . $e->getMessage());
                }
            }
            return $success;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    private function getUploadCount($missionId)
    {
        try {
            $query = "SELECT incrementation FROM incrementation_uploads WHERE mission_id = :missionId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':missionId', $missionId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() ?: 0;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return 0;
        }
    }

    public function filterAndSortFiles($files, $searchQuery = '', $sortBy = 'date', $order = 'desc')
    {
        // Filtrer par recherche
        if (!empty($searchQuery)) {
            $files = array_filter($files, function ($file) use ($searchQuery) {
                $searchLower = strtolower($searchQuery);
                return (
                    strpos(strtolower($file['name']), $searchLower) !== false ||
                    strpos(strtolower($file['user']['username']), $searchLower) !== false ||
                    strpos(strtolower($file['domain']), $searchLower) !== false ||
                    strpos(strtolower($file['mission']['name']), $searchLower) !== false
                );
            });
        }

        // Trier les fichiers
        usort($files, function ($a, $b) use ($sortBy, $order) {
            $result = 0;
            switch ($sortBy) {
                case 'name':
                    $result = strcasecmp($a['name'], $b['name']);
                    break;
                case 'date':
                    $result = strtotime($a['created_at']) - strtotime($b['created_at']);
                    break;
                case 'size':
                    $result = $a['size'] - $b['size'];
                    break;
                case 'domain':
                    $result = strcasecmp($a['domain'], $b['domain']);
                    break;
                case 'user':
                    $result = strcasecmp($a['user']['username'], $b['user']['username']);
                    break;
            }
            return $order === 'desc' ? -$result : $result;
        });

        return array_values($files);
    }

    public function removeMember($userIds)
    {
        try {
            if (!is_array($userIds)) {
                $userIds = [$userIds];
            }

            if (empty($userIds)) {
                return true;
            }

            $placeholders = implode(',', array_fill(0, count($userIds), '?'));

            $query = "DELETE FROM user WHERE userId IN ($placeholders)";

            $stmt = $this->db->prepare($query);
            $stmt->execute($userIds);

            return true;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function deleteComment($commentId)
    {
        try {
            $query = "DELETE FROM comments WHERE id = :commentId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':commentId', $commentId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
