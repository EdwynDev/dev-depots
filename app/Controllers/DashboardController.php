<?php

require_once 'BaseController.php';

class DashboardController extends BaseController
{
    public function index()
    {
        $this->requireAuth();

        $userId = $_SESSION['discord_user']['id'];
        $domainId = $this->user['domaine_id'];
        $domainName = $this->mainController->getDomainName($domainId);
        
        // Récupérer les missions actives
        $activeMissions = $this->mainController->getActiveMissions($userId);
        
        // Récupérer les missions disponibles du domaine
        $availableMissions = $this->mainController->getMissionsByDomain($domainId);
        
        // Grouper les missions par nom
        $groupedMissions = [];
        foreach ($availableMissions as $mission) {
            $name = $mission['name'];
            if (!isset($groupedMissions[$name])) {
                $groupedMissions[$name] = [
                    'info' => $mission,
                    'count' => 1,
                    'available_count' => $mission['status'] === 'available' ? 1 : 0
                ];
            } else {
                $groupedMissions[$name]['count']++;
                if ($mission['status'] === 'available') {
                    $groupedMissions[$name]['available_count']++;
                }
            }
        }

        // Limiter à 6 missions pour le dashboard
        $recentMissions = array_slice($groupedMissions, 0, 6, true);

        $this->render('dashboard/index', [
            'title' => 'Tableau de bord',
            'domainName' => $domainName,
            'activeMissions' => $activeMissions,
            'recentMissions' => $recentMissions,
            'totalAvailableMissions' => count($groupedMissions)
        ]);
    }
}