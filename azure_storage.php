<?php
require 'vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

$accountName = "tiktokstorage";
$accountKey = "k4vUv8bAC8SQ6bcfvQtAMO5SYpba/pmq6psveJPZtu7cLxUS2pEn94Z811fxAwwV8VkjMcpTnzP++AStbJk7Vw==";
$connectionString = "DefaultEndpointsProtocol=https;AccountName=tiktokstorage;AccountKey=k4vUv8bAC8SQ6bcfvQtAMO5SYpba/pmq6psveJPZtu7cLxUS2pEn94Z811fxAwwV8VkjMcpTnzP++AStbJk7Vw==;EndpointSuffix=core.windows.net";

// Create Blob Client
$blobClient = BlobRestProxy::createBlobService($connectionString);

// Upload a video or thumbnail
try {
    $containerName = "videos";  // Your Azure container name
    $fileToUpload = "https://tiktokstorage.blob.core.windows.net/videos/path_to_your_file.mp4";  // Path to file on your server

    // Upload blob
    $content = fopen($fileToUpload, "r");
    $blobClient->createBlockBlob($containerName, basename($fileToUpload), $content);

    echo "File uploaded successfully.";
} catch (ServiceException $e) {
    echo "Error: " . $e->getMessage();
}
?>
