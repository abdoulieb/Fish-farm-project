<?php
require_once 'config.php';
require_once 'auth.php';

if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    $itemId = $_POST['item_id'] ?? null;
    $itemType = $_POST['item_type'] ?? null;
    $caption = $_POST['caption'] ?? null;

    if (!$itemId || !$itemType) {
        throw new Exception('Invalid request parameters');
    }

    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . uniqid() . '_' . $fileName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if image file is a actual image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        throw new Exception('File is not an image');
    }

    // Check file size (5MB max)
    if ($_FILES["image"]["size"] > 5000000) {
        throw new Exception('File is too large (max 5MB)');
    }

    // Allow certain file formats
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed');
    }

    // Upload file
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        // Update database based on item type
        switch ($itemType) {
            case 'fish':
                $stmt = $pdo->prepare("UPDATE fish_types SET image_path = ? WHERE id = ?");
                break;
            case 'team':
                $stmt = $pdo->prepare("UPDATE team_members SET photo_url = ? WHERE id = ?");
                break;
            case 'partner':
                $stmt = $pdo->prepare("UPDATE partners SET logo_url = ? WHERE id = ?");
                break;
            default:
                throw new Exception('Invalid item type');
        }

        $stmt->execute([$targetFile, $itemId]);
        $response['success'] = true;
        $response['message'] = 'Image updated successfully';
    } else {
        throw new Exception('Error uploading file');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
