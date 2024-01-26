<?php
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("X-Frame-Options: SAMEORIGIN");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net; img-src 'self' data:; font-src 'self'");
header("X-XSS-Protection: 1; mode=block");

session_start();
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
}
require_once "database.php";
$user_id = $_SESSION["user_id"];
$user_name = $_SESSION['user'];
$uploadDir = '../uploads/';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    $file = $_FILES['file'];
    // Check for errors during file upload
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($file['name']);
        $safeFilename = preg_replace('/[^a-zA-Z0-9]/', '_', $fileName);

        $fileType = $file['type'];
        $uploadPath = $uploadDir . $safeFilename;

        // Check file extension and size (you can customize these checks)
        $allowedExtensions = [ 'jpg','png','pdf','docx'];
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['file']['type'];
        $maxFileSize = 5 * 1024 * 1024; // 5 MB

        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        if(!in_array($fileExtension, $allowedExtensions))
        {
            $error =  $fileExtension. ' file format is not supported';
        }
        else if (!in_array($file_type, $allowed_types)) {
            $error =  $fileExtension. ' file format is not supported';

        }
        else if ($file['size'] > $maxFileSize)
        {
            $error =  ' File size exceeds the limit.';
        }


            if (!$error && move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $sql = "INSERT INTO uploads (user_id , filename,file_type, file_path,created_ip) 
                        VALUES (:user_id, :filename,:file_type, :file_path,:created_ip)";
                $stmt = $conn->prepare($sql);

                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':filename', $safeFilename, PDO::PARAM_STR);
                $stmt->bindParam(':file_type', $fileType, PDO::PARAM_STR);
                $stmt->bindParam(':file_path', $uploadPath, PDO::PARAM_STR);
                $stmt->bindParam(':created_ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
                $stmt->execute();
              echo "<div class='alert alert-success'> File uploaded successfully! </div>";
            }

    } else {
        $error = 'Error during file upload.';
    }

    if($error)
    {
        $sql = "INSERT INTO logs (user_id , file_name, upload_ip,err_message) 
                        VALUES (:user_id, :file_name, :upload_ip,:message)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':file_name', $fileName, PDO::PARAM_STR);
        $stmt->bindParam(':upload_ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $stmt->bindParam(':message', $error, PDO::PARAM_STR);

        $stmt->execute();
        echo "<div class='alert alert-danger'> $error </div>";

    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>User Dashboard</title>
</head>
<body>
    <div class="container-fluid">
        <div style="width: 50%;float: left">
        <h1>Welcome <?php echo $_SESSION['user']; ?> </h1>
        </div>
        <div style="width: 50%;float: right;text-align: right">
        <a href="logout.php" class="btn btn-warning">Logout</a>
        </div><br>
    </div>
    <div class="container" style="top: 25px;   position: relative;">
     <form action="index.php" method="post" enctype="multipart/form-data">
       <h3> Select file to upload:</h3><br>
        <input type="file" name="file" id="file" accept=".pdf, .png ,.jpg, .docx">
        <button type="submit">Upload</button><br><br>
        
    </form>
    </div><br>
    <div style=" position: relative;   top: 15px;">
        <div  class="box" style="width: 48%;float: left">
            <h2 style="padding: 5px">File List</h2>

            <table>
    <thead>
        <tr>
            <th>Sl No</th>
            <th>File Name</th>
            <th>Download</th>
        </tr>
    </thead>
    <tbody>
        <?php
            $stmt = $conn->prepare("SELECT id, filename, file_path FROM uploads where user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $slNo = 1;
         foreach ($files as $file): ?>
            <tr>
                <td><?= $slNo; ?></td>
                <td><?= $file['filename']; ?></td>
                <td>
                    <a href="download.php?id=<?= $file['id']; ?>">Download</a>
                </td>
            </tr>
        <?php $slNo ++;
        endforeach; ?>
    </tbody>
</table>
        </div>
        <div class="box" style="width: 48%;float: right">
            <h2 style="padding: 5px">Error Log</h2>
            <table>
                <thead>
                <tr>
                    <th>Sl No</th>
                    <th>File Name</th>
                    <th>Error Message</th>
                    <th>Time Stamp </th>
                    <th>IP </th>

                </tr>
                </thead>
                <tbody>
                <?php
                $stmt = $conn->prepare("SELECT id, file_name, err_message,upload_timestamp,upload_ip FROM logs where user_id = :user_id");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $files2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $slNo2 = 1;
                foreach ($files2 as $file2): ?>
                    <tr>
                        <td><?= $slNo2; ?></td>
                        <td style=" word-break: break-all;"><?= $file2['file_name']; ?></td>
                        <td style=" word-break: break-all;"><?= $file2['err_message']; ?></td>
                        <td><?= $file2['upload_timestamp']; ?></td>
                        <td><?= $file2['upload_ip']; ?></td>
                    </tr>
                <?php $slNo2 ++ ;
                endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>   
    

</body>
</html>