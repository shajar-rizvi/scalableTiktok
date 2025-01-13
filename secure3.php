<?php
require_once 'azure_storage.php';  // Include the Azure Storage client

session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = mysqli_connect("tiktikapp.mysql.database.azure.com", "tiktokadmin", "password!1", "tiktok");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle video and thumbnail upload
if (isset($_POST['upload_video'])) {
    // Handle video upload
    $title = $_POST['title'];
    $description = $_POST['description'];
    $publisher = "no";
    $producer = "no";
    $genre = $_POST['genre'];
    $ageRating = "no";

    // File upload handling for video
    // $video_target_dir = "uploads/";
    // $video_target_file = $video_target_dir . basename($_FILES["fileToUpload"]["name"]);
    $video_uploadOk = 1;
    // $videoFileType = strtolower(pathinfo($video_target_file, PATHINFO_EXTENSION));

    // $file = $_FILES['video_file']['tmp_name'];
    // $blobClient->createBlockBlob("videos", $_FILES['video_file']['name'], fopen($file, "r"));


    // // File upload handling for thumbnail
    // $thumbnail_target_dir = "uploads/";
    // $thumbnail_target_file = $thumbnail_target_dir . basename($_FILES["thumbnailToUpload"]["name"]);
    $thumbnail_uploadOk = 1;
    // $thumbnailFileType = strtolower(pathinfo($thumbnail_target_file, PATHINFO_EXTENSION));

    // // Check if file already exists
    // if (file_exists($video_target_file)) {
    //     echo "Sorry, video file already exists.";
    //     $video_uploadOk = 0;
    // }
    // if (file_exists($thumbnail_target_file)) {
    //     echo "Sorry, thumbnail image file already exists.";
    //     $thumbnail_uploadOk = 0;
    // }


     // File upload handling for video
     $videoFile = $_FILES["fileToUpload"];
     $thumbnailFile = $_FILES["thumbnailToUpload"];
 
     // Validate video file
     $allowedVideoFormats = ["mp4", "avi", "mov", "mkv"];
     $videoFileType = strtolower(pathinfo($videoFile["name"], PATHINFO_EXTENSION));
     if (!in_array($videoFileType, $allowedVideoFormats)) {
         die("Error: Only MP4, AVI, MOV, MKV files are allowed for video.");
     }
 
     // Validate thumbnail file
     $allowedThumbnailFormats = ["jpg", "jpeg", "png", "gif"];
     $thumbnailFileType = strtolower(pathinfo($thumbnailFile["name"], PATHINFO_EXTENSION));
     if (!in_array($thumbnailFileType, $allowedThumbnailFormats)) {
         die("Error: Only JPG, JPEG, PNG, and GIF files are allowed for thumbnails.");
     }
 
     try {
         // Upload video to Azure Blob Storage

         $videoBlobName = "videos/" . $videoFile["name"];
         $videoTempFilePath = $videoFile["tmp_name"];
         $blobClient->createBlockBlob("videos", $videoBlobName, fopen($videoTempFilePath, "r"));
        
         // Upload thumbnail to Azure Blob Storage
         $thumbnailBlobName = "thumbnails/" . $thumbnailFile["name"];
         $thumbnailTempFilePath = $thumbnailFile["tmp_name"];
         $blobClient->createBlockBlob("videos", $thumbnailBlobName, fopen($thumbnailTempFilePath, "r"));

     } catch (Exception $e) {
        echo "Sorry, Exception in Blob upload.";

     }

    // Check file size for video
    if ($_FILES["fileToUpload"]["size"] > 500000000) {
        echo "Sorry, your video file is too large.";
        $video_uploadOk = 0;
    }
    // Check file size for thumbnail
    if ($_FILES["thumbnailToUpload"]["size"] > 50000000) {
        echo "Sorry, your thumbnail image file is too large.";
        $thumbnail_uploadOk = 0;
    }

    // Allow certain file formats for video
    $allowedVideoFormats = array("mp4", "avi", "mp3", "mov", "pdf", "docx", "png", "jpg");
    if (!in_array($videoFileType, $allowedVideoFormats)) {
        echo "Sorry, only MP4, AVI, MOV, PDF, and DOCX files are allowed for video.";
        $video_uploadOk = 0;
    }
    // Allow certain file formats for thumbnail
    $allowedThumbnailFormats = array("jpg", "jpeg", "png", "gif");
    if (!in_array($thumbnailFileType, $allowedThumbnailFormats)) {
        echo "Sorry, only JPG, JPEG, PNG, and GIF files are allowed for thumbnail image.";
        $thumbnail_uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error for video
    if ($video_uploadOk == 0) {
        echo "Sorry, your video file was not uploaded.";
    } elseif ($thumbnail_uploadOk == 0) {
        echo "Sorry, your thumbnail image file was not uploaded.";
    } else {
        // if everything is ok, try to upload files
        // if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $video_target_file) && move_uploaded_file($_FILES["thumbnailToUpload"]["tmp_name"], $thumbnail_target_file)) {
            // Insert video details into database
            $video_filename = basename($_FILES["fileToUpload"]["name"]);
            $thumbnail_filename = basename($_FILES["thumbnailToUpload"]["name"]);
            $uploader_id = $_SESSION['id'];
            $upload_datetime = date("Y-m-d H:i:s"); // Current date and time

            $sql = "INSERT INTO videos (title, description, publisher, producer, genre, AgeRating, filename, thumbnail, uploader_id, upload_datetime) 
                    VALUES ('$title', '$description', 'no', 'no', '$genre', 'no', '$video_filename', '$thumbnail_filename', '$uploader_id', '$upload_datetime')";

            if (mysqli_query($conn, $sql)) {
                echo "The video file " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " and thumbnail image file " . htmlspecialchars(basename($_FILES["thumbnailToUpload"]["name"])) . " have been uploaded.";
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
            }
        } 
        // else {
        //     echo "Sorry, there was an error uploading your files.";
        }
    // }
// }

// Fetch genres from the Genres table
$genre_query = "SELECT genre_name FROM Genres";
$genre_result = mysqli_query($conn, $genre_query);
$genres = array();
while ($row = mysqli_fetch_assoc($genre_result)) {
    $genres[] = $row['genre_name'];
}

// Fetch all age ratings from the AgeRating table
$age_rating_query = "SELECT rating_name FROM AgeRating";
$age_rating_result = mysqli_query($conn, $age_rating_query);
$age_ratings = array();
while ($row = mysqli_fetch_assoc($age_rating_result)) {
    $age_ratings[] = $row['rating_name'];
}

// Delete selected videos
if (isset($_POST['delete_videos'])) {
    if (isset($_POST['videos']) && !empty($_POST['videos'])) {
        $videos_to_delete = $_POST['videos'];
        foreach ($videos_to_delete as $video_id) {
            // Delete associated likes
            mysqli_query($conn, "DELETE FROM Likes WHERE video_id = $video_id");

            // Delete associated dislikes
            mysqli_query($conn, "DELETE FROM Dislikes WHERE video_id = $video_id");

            // Delete associated comments
            mysqli_query($conn, "DELETE FROM Comments WHERE video_id = $video_id");

            // Fetch video and thumbnail filenames
            $file_query = "SELECT filename, thumbnail FROM videos WHERE id = $video_id";
            $file_result = mysqli_query($conn, $file_query);
            $file_row = mysqli_fetch_assoc($file_result);
            $video_filename = $file_row['filename'];
            $thumbnail_filename = $file_row['thumbnail'];

            // Delete video file
            $video_path = "uploads/" . $video_filename;
            if (file_exists($video_path)) {
                unlink($video_path);
            }

            // Delete thumbnail file
            $thumbnail_path = "uploads/" . $thumbnail_filename;
            if (file_exists($thumbnail_path)) {
                unlink($thumbnail_path);
            }

            // Delete video entry from database
            mysqli_query($conn, "DELETE FROM videos WHERE id = $video_id");
        }
        echo "Selected videos along with their associated likes, dislikes, comments, and files have been deleted.";
    } else {
        echo "No videos selected for deletion.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css">
    <style>
        /* Global Styles */
        /* Global Styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #141414;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: auto;
        }

        /* Navbar */
        .nav-tabs .nav-link {
            color: #007bff;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .nav-tabs {
            margin-bottom: 30px;
        }

        /* Logout Link */
        .logout {
            float: right;
            font-size: 1.1rem;
            color: #007bff;
            text-decoration: none;
        }

        .logout:hover {
            text-decoration: underline;
        }

        /* Section Styling */
        .video-container,
        .upload-container {
            background-color: #1e1e1e;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            padding: 30px;
            margin-bottom: 30px;
        }

        .video-container h3,
        .upload-container h3 {
            color: #fff;
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #bbb;
        }

        .form-control,
        .form-control-file,
        select.form-control {
            border-radius: 8px;
            padding: 12px 16px;
            background-color: #2e2e2e;
            border: 1px solid #444;
            color: #fff;
            font-size: 1.1rem;
        }

        .form-control:focus,
        select.form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        .form-control-file {
            background-color: #2e2e2e;
            color: #fff;
            border: none;
        }

        input[type="file"] {
            padding: 8px 10px;
            background-color: #333;
        }

        /* Button Styling */
        .btn {
            border-radius: 8px;
            font-size: 1.1rem;
            padding: 12px 20px;
            transition: background-color 0.3s ease;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn:focus {
            box-shadow: none;
        }

        /* Video List Styling */
        .video-container input[type="checkbox"] {
            margin-right: 10px;
        }

        .video-container .btn-danger {
            display: inline-block;
            margin-top: 10px;
        }

        /* Table Styling */
        table {
            width: 100%;
            margin-top: 20px;
            color: #fff;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 12px;
            text-align: center;
        }

        table th {
            background-color: #333;
            font-weight: 600;
        }

        table td {
            background-color: #222;
        }

        table tr:hover {
            background-color: #444;
        }

        table input[type="checkbox"] {
            margin-right: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .logout {
                float: none;
                margin-top: 20px;
                text-align: center;
            }

            .video-container,
            .upload-container {
                margin-bottom: 20px;
            }

            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 30px;
            }

            /* Adjust Form Elements for Small Screens */
            .form-control,
            .form-control-file,
            select.form-control {
                font-size: 1rem;
            }

            .btn {
                width: 100%;
                padding: 12px;
            }

            .video-container h3,
            .upload-container h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Navbar with Links -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="index.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="#">Upload Video Page</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php" class="logout">Logout</a>
            </li>
        </ul>

        <!-- Main Content -->
        <div class="row">

            <div>
                <div class="upload-container">
                    <h3>Upload Video</h3>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Title:</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea class="form-control" id="description" name="description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="genre">Genre:</label>
                            <select class="form-control" id="genre" name="genre" required>
                                <?php foreach ($genres as $genre): ?>
                                    <option value="<?php echo $genre; ?>"><?php echo $genre; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fileToUpload">Select video to upload:</label>
                            <input type="file" class="form-control-file" name="fileToUpload" id="fileToUpload" required>
                        </div>
                        <div class="form-group">
                            <label for="thumbnailToUpload">Select thumbnail image to upload:</label>
                            <input type="file" class="form-control-file" name="thumbnailToUpload" id="thumbnailToUpload"
                                required>
                        </div>
                        <button type="submit" class="btn btn-primary" name="upload_video">Upload Video</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>


</html>

<?php
mysqli_close($conn);
?>