<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="members.csv"');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/CONTROLLERS/MainController.php';

use Controllers\MainController;

$mainController = new MainController();

class MembersAPI {
    private $mainController;

    public function __construct() {
        $this->mainController = new MainController();
    }

    public function getMembersCSV() {
        $members = $this->mainController->getAllMembers();
        
        $output = "ID Discord,Nom d'utilisateur,Domaine Principal,Domaine Secondaire,Chef,Date de création,Dernière connexion\n";

        foreach ($members as $member) {
            $userId = $member['userId'];
            $username = str_replace(',', ' ', $member['username']);
            $mainDomain = str_replace(',', ' ', $this->mainController->getDomainName($member['domaine_id']));
            $secondaryDomain = $member['domaine_id_secondary'] ? str_replace(',', ' ', $this->mainController->getDomainName($member['domaine_id_secondary'])) : 'N/A';
            $isChef = $member['chefs'] ? 'Oui' : 'Non';
            $createdAt = $member['created_at'];
            $lastLogin = $member['last_login'] ? $member['last_login'] : 'N/A';

            $output .= "{$userId},{$username},{$mainDomain},{$secondaryDomain},{$isChef},{$createdAt},{$lastLogin}\n";
        }

        return $output;
    }
}

$api = new MembersAPI();
$action = 'members';

switch ($action) {
    case 'members':
        echo $api->getMembersCSV();
        break;
}
exit;
?>