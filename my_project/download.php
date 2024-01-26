<?php
require_once "database.php";

try {

    if (isset($_GET['id'])) {
        $fileId = $_GET['id'];

        $stmt = $conn->prepare("SELECT file_path,file_type, filename FROM uploads WHERE id = :id");
        $stmt->bindParam(':id', $fileId, PDO::PARAM_INT);
        $stmt->execute();
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($file) {
            $filePath = $file['file_path'];
            $fileName = $file['filename'];
            $fileType = $file['file_type'];

            // Output appropriate headers for file download
            header('Content-Description: File Transfer');
            header('Content-Type: '.$fileType);
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>