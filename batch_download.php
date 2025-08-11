<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['discord_user'])) {
    http_response_code(403);
    exit('Non autorisé');
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    http_response_code(400);
    exit('Aucun fichier sélectionné');
}

$zip = new ZipArchive();
$tempFile = tempnam(sys_get_temp_dir(), 'zip');

if ($zip->open($tempFile, ZipArchive::CREATE) !== TRUE) {
    http_response_code(500);
    exit('Impossible de créer l\'archive');
}

foreach ($data as $file) {
    if (file_exists($file['path'])) {
        $zip->addFile($file['path'], $file['name']);
    }
}

$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="grouped_mission.zip"');
header('Content-Length: ' . filesize($tempFile));
header('Pragma: no-cache');
header('Expires: 0');

readfile($tempFile);
unlink($tempFile);
