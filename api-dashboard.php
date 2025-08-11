<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="domain_stats.csv"');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/CONTROLLERS/MainController.php';

use Controllers\MainController;

$mainController = new MainController();

class DashboardAPI {
    private $mainController;

    public function __construct() {
        $this->mainController = new MainController();
    }

    public function getDomainStatsCSV() {
        $domains = $this->mainController->giveAllDomaine();
        
        $output = "Domaine,Terminées,En attente,Disponibles,Personnes\n";

        foreach ($domains as $domain) {
            $domainId = $domain['id'];
            $completed = count($this->mainController->getMissionsByDomainCompleted($domainId));
            $pending = count($this->mainController->getMissionsByDomainPending($domainId));
            $available = count($this->mainController->getMissionsByDomainAvailable($domainId));
            $people = count($this->mainController->getPeopleByDomain($domainId));

            $safeDomainName = str_replace(',', ' ', $domain['name']);

            $output .= "{$safeDomainName},{$completed},{$pending},{$available},{$people}\n";
        }

        return $output;
    }
}

$api = new DashboardAPI();
$action = 'domains';

switch ($action) {
    case 'domains':
        echo $api->getDomainStatsCSV();
        break;
}
exit;
?>