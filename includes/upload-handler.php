<?php
// Alias for backward compatibility
function handleFileUpload($file, $directory = 'products', $maxSize = 134217728) {
    return handleImageUpload($file, $directory, $maxSize);
}

function handleImageUpload($file, $directory = 'products', $maxSize = 134217728) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File melebihi upload_max_filesize di php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi MAX_FILE_SIZE di form',
            UPLOAD_ERR_PARTIAL => 'File hanya sebagian terupload',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ada',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension',
        ];
        $errorMsg = $errors[$file['error']] ?? 'Upload error occurred.';
        return ['success' => false, 'error' => $errorMsg];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File terlalu besar. Max 128MB. File size: ' . round($file['size']/1024/1024, 2) . 'MB'];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowed)) {
        return ['success' => false, 'error' => 'Format file tidak didukung. Hanya JPG, PNG, GIF, WEBP.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . strtolower($ext);
    $uploadDir = __DIR__ . '/../public/uploads/' . $directory . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true,
            'path' => '/public/uploads/' . $directory . '/' . $filename,
            'file_path' => '/public/uploads/' . $directory . '/' . $filename,
            'filename' => $filename
        ];
    }

    return ['success' => false, 'error' => 'Gagal upload file. Check folder permissions.'];
}

function deleteImage($imagePath) {
    if (empty($imagePath)) return true;

    $fullPath = __DIR__ . '/../' . ltrim($imagePath, '/');
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return true;
}

function resizeImage($source, $destination, $maxWidth = 1920, $maxHeight = 1080) {
    list($width, $height, $type) = getimagesize($source);

    if ($width <= $maxWidth && $height <= $maxHeight) {
        copy($source, $destination);
        return true;
    }

    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = round($width * $ratio);
    $newHeight = round($height * $ratio);

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $destination, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $destination);
            break;
    }

    imagedestroy($image);
    imagedestroy($newImage);

    return true;
}
?>
