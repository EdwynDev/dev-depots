<?php
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(30 * 24 * 60 * 60);
    session_start();
}

if (isset($_GET['path'])) {
    $filePath = realpath($_GET['path']);

    if (file_exists($filePath)) {
        $fileName = basename($filePath);

        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Expires: 0');

        if (ob_get_level()) {
            ob_end_clean();
        }

        readfile($filePath);
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        exit('Fichier non trouvé');
    }
} else {
    header("HTTP/1.0 400 Bad Request");
    exit('Paramètre manquant');
}
