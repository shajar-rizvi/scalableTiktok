<?php
session_start();

$azureBaseURL = 'https://tiktokstorage.blob.core.windows.net/videos/';

// Display success message if available
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success" role="alert">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}


// Database connection
$hostname = "tiktikapp.mysql.database.azure.com";
$username = "tiktokadmin";
$password = "password!1";
$dbname = "tiktok";

$conn = mysqli_connect($hostname, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch genres from the genres table
$genreQuery = "SELECT DISTINCT genre_name FROM genres";
$genreResult = mysqli_query($conn, $genreQuery);
$genres = [];
if ($genreResult && mysqli_num_rows($genreResult) > 0) {
    while ($row = mysqli_fetch_assoc($genreResult)) {
        $genres[] = $row['genre_name'];
    }
}

// Fetch age ratings from the agerating table
$ageRatingQuery = "SELECT DISTINCT rating_name FROM agerating";
$ageRatingResult = mysqli_query($conn, $ageRatingQuery);
$ageRatings = [];
if ($ageRatingResult && mysqli_num_rows($ageRatingResult) > 0) {
    while ($row = mysqli_fetch_assoc($ageRatingResult)) {
        $ageRatings[] = $row['rating_name'];
    }
}

// Check if the user clicked the search button
if (isset($_POST['search'])) {
    $search = $_POST['search'];
    $genre = isset($_POST['genre']) ? $_POST['genre'] : '';
    $ageRating = isset($_POST['age_rating']) ? $_POST['age_rating'] : '';

    // Construct the query based on search terms
    $query = "SELECT * FROM videos WHERE (title LIKE '%$search%' OR description LIKE '%$search%' OR Producer LIKE '%$search%' OR Genre LIKE '%$search%' OR AgeRating LIKE '%$search%')";
    if ($genre != '') {
        $query .= " AND Genre = '$genre'";
    }
    if ($ageRating != '') {
        $query .= " AND AgeRating = '$ageRating'";
    }

    $result = mysqli_query($conn, $query);
} else {
    // If not, fetch all videos
    $query = "SELECT * FROM videos";
    $result = mysqli_query($conn, $query);
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

            // $azureBaseURL = 'https://tiktokstorage.blob.core.windows.net/videos/';
            $videoURL = $azureBaseURL . "videos/" . $video_filename;
            $thumbnailURL = $azureBaseURL . "thumbnails/" . $thumbnail_filename;

            // Delete video file
            $video_path = $videoURL;
            if (file_exists($video_path)) {
                unlink($video_path);
            }

            // Delete thumbnail file
            $thumbnail_path = $thumbnailURL;
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
    <title>Netflix</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
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
            border-bottom: 0px solid #dee2e6;
        }

        h2 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .logout,
        .signup {
            float: right;
            margin-top: 20px;
            margin-right: 20px;
            color: #fff;
            text-decoration: none;
        }

        .logout:hover,
        .signup:hover {
            color: #ccc;
        }

        .search-form {
            margin-bottom: 30px;
            display: flex;
            align-items: center;
        }

        .form-control {
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            margin-right: 10px;
            width: 250px;
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: none;
            background-color: #444;
            color: #fff;
        }

        .btn-search {
            background-color: #e50914;
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 10px 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-search:hover {
            background-color: #ff0c00;
        }

        .video-link {
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }

        .video-link:hover {
            color: #e50914;
        }

        .age-rating {
            color: #fff;
        }

        .age-rating.pg-13 {
            color: #00ff00;
            /* Green for PG-13 */
        }

        .age-rating.r {
            color: #ff0000;
            /* Red for 18+ rating */
        }

        .modal-container {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1;
            /* Sit on top */
            padding-top: 100px;
            /* Location of the box */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgb(0, 0, 0);
            /* Fallback color */
            background-color: rgba(0, 0, 0, 0.9);
            /* Black w/ opacity */

        }


        .modal-content {
            background-color: #222;
            padding: 20px;
            border-radius: 10px;
            align-items: center
        }

        .close-btn {
            color: #ccc;
            cursor: pointer;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .username {
            float: right;
            margin-top: 20px;
            margin-right: 20px;
            color: #fff;
        }

        .thumbnail {
            width: 100px;
            /* Adjust width as needed */
            height: auto;
            /* Maintain aspect ratio */
        }

        .upload-link {
            float: right;
            margin-top: 20px;
            margin-right: 20px;
            color: #fff;
            text-decoration: none;
        }

        /* Dashboard */
        .dashboard {
            background-color: #222;
            padding: 20px;
            margin-top: 30px;
            border-radius: 10px;
        }

        .dashboard h3 {
            color: #fff;
            margin-bottom: 20px;
        }

        .dashboard .video-list {
            list-style: none;
            padding: 0;
        }

        .dashboard .video-list li {
            margin-bottom: 10px;
        }

        .dashboard .video-list li a {
            color: #fff;
            text-decoration: none;
            transition: all 0.3s;
        }

        .dashboard .video-list li a:hover {
            color: #e50914;
        }

        /* Styling the video feed */
        .video-feed {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 20px;
            gap: 20px;
            height: 100vh;
            /* Full height viewport */
            overflow-y: scroll;
            /* Scrollable container */
            background-color: #141414;
        }

        .video-card {

            min-block-size: -webkit-fill-available;
            padding: 15px;
            width: 100%;
            max-width: 600px;
            margin: 10px 0;
            border-radius: 10px;
            overflow: hidden;
            background-color: #222;
            position: relative;
        }

        .video-card video {
            width: 100%;

            /* Ensure video fills the container */
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
            background-color: #000;
        }

        .video-card h5 {
            color: #fff;
            margin-top: 10px;
            font-size: 18px;
            padding: 0;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
        }

        .video-info {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            color: #fff;
        }

        .age-rating {
            color: #fff;
        }

        .age-rating.pg-13 {
            color: #00ff00;
            /* Green for PG-13 */
        }

        .age-rating.r {
            color: #ff0000;
            /* Red for 18+ rating */
        }

        /* Add some hover effects */
        .video-card:hover {
            box-shadow: 0 4px 8px rgba(255, 255, 255, 0.1);
        }

        .video-thumbnail img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .video-thumbnail {
            position: relative;
            width: 100%;
            height: 100%;
            background-color: #000;
        }

        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .video-card p {
            margin: 5px 0;
            font-size: 14px;
        }

        .video-container,
        .upload-container {
            background-color: #141414;
            border-radius: 8px;
            box-shadow: 0 4px 8px #096dd9;
            padding: 20px;
            margin-bottom: 30px;
        }

        .video-container h3,
        .upload-container h3 {
            color: #333;
            font-size: 1.75rem;
            margin-bottom: 20px;
        }

        .video-container input[type="checkbox"] {
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row" style="padding: 10px">
            <div class="col-md-6">

                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">TIktik Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="secure3.php">Upload Video Page</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php" class="logout">Logout</a>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="username">Welcome, <?php echo $_SESSION['username']; ?></span>

                <?php else: ?>
                    <a class="signup" href="#" id="signup-link">Sign Up</a>
                    <a class="logout" href="#" id="signin-link">Sign In</a>

                <?php endif; ?>
            </div>
        </div>

        <!-- Sign-up form -->
        <div id="signup-form" class="modal-container">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h3>Sign Up</h3>
                <form action="signup-process.php" method="POST">
                    <div class="form-group">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                            required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                            required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" id="fname" name="fname" placeholder="First Name"
                            required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" id="lname" name="lname" placeholder="Last Name"
                            required>
                    </div>
                    <div class="form-group">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" id="contact" name="contact" placeholder="Contact Number"
                            required>
                    </div>
                    <button type="submit" name="signup" class="btn btn-primary" style="margin-left: 85px">Sign
                        Up</button>
                </form>
            </div>
        </div>

        <!-- Sign-in form -->
        <div id="signin-form" class="modal-container">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h2>Tiktik</h2>
                <h3>Sign In</h3>
                <form action="signin_process.php" method="POST">
                    <div class="form-group">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                            required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                            required>
                    </div>
                    <button type="submit" name="signin" class="btn btn-primary" style="margin-left: 85px">Sign
                        In</button>
                </form>
            </div>
        </div>

        <!-- Search form -->
        <form class="search-form" action="" method="POST">
            <input type="text" name="search" class="form-control" placeholder="Search videos">
            <select name="genre" class="form-control">
                <option value="">Select Genre</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?php echo $genre; ?>"><?php echo $genre; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-search">Search</button>
        </form>


        <div class="row">

            <div class="video-feed col-md-9">
                <?php

                //AZURE 
                $azureBaseURL = 'https://tiktokstorage.blob.core.windows.net/videos/';


                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $ageRatingClass = strtolower($row['AgeRating']);
                        echo '<div class="video-card">';
                        if (isset($_SESSION['username'])) {

                            //AZURE
                
                            $videoURL = $azureBaseURL . "videos/" . $row['filename'];

                            echo '<a href="view_video1.php?filename=' . $row['filename'] . '">';
                            echo '<h5 class="video-title">' . $row['title'] . '</h5> <p>' . $row['description'] . '</p>
                      </a>';

                      // Display the video using the Azure Blob URL
                            echo '<video class="video-player" src="'.$videoURL . '" controls></video>;';
                            echo ' <video class="video-player" src="'.$videoURL. '" autoplay muted loop playsinline></video>';
                            echo '<h5 class="video-title">' . $row['title'] . '</h5>';
                        } else {
                            $thumbnailURL = $azureBaseURL . "thumbnails/" . $row['thumbnail'];

                            echo '<div class="video-thumbnail">
                        <img src="' . $thumbnailURL . '" alt="Thumbnail" class="thumbnail">
                      </div>';
                        }
                        echo '<div class="video-info">
                    
                  </div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div>No videos found</div>';
                }
                ?>
            </div>
            <?php
            if (isset($_SESSION['username'])) {
                echo '<div class="col-md-3">';
                echo '<div class="video-container">';
                echo '<h3>Videos Uploaded by Me</h3>';
                echo '<form action="" method="POST">';
                $view_option = isset($_GET['view-option']);
                $id = $_SESSION['id'];
                $query = "SELECT * FROM videos WHERE uploader_id = $id";
                $result = mysqli_query($conn, $query); // Define $result here
            
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<div><input type='checkbox' name='videos[]' value='" . $row['id'] . "'><a href='view_video.php?id=" . $row['id'] . "' class='video-link'>" . $row['title'] . "</a></div>";
                    }
                    echo '<button type="submit" name="delete_videos" class="btn btn-primary">Delete Selected Videos</button>';

                } else {
                    echo "No videos uploaded yet.";
                }
            } else {
                echo '<div class="col-md-3">';
                echo '<div class="video-container">';
                echo '<div>You are not logged in. Please <a href="#">Sign-In </a> to watch and upload videos.</div>';
            }

            ?>


        </div>



    </div>

    <script>
        // Show sign-in form when "Sign In" link is clicked
        document.getElementById("signin-link").addEventListener("click", function (e) {
            e.preventDefault();
            document.getElementById("signin-form").style.display = "block";
            document.getElementById("signup-form").style.display = "none";
        });

        // Show sign-up form when "Sign Up" link is clicked
        document.getElementById("signup-link").addEventListener("click", function (e) {
            e.preventDefault();
            document.getElementById("signup-form").style.display = "block";
            document.getElementById("signin-form").style.display = "none";
        });

        // Close sign-in and sign-up forms when close button is clicked
        document.querySelectorAll(".close-btn").forEach(function (closeBtn) {
            closeBtn.addEventListener("click", function () {
                document.getElementById("signin-form").style.display = "none";
                document.getElementById("signup-form").style.display = "none";
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const videos = document.querySelectorAll(".video-player");

            // Function to check if a video is in the viewport
            function checkVideoPlayback() {
                videos.forEach(video => {
                    const rect = video.getBoundingClientRect();
                    const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;

                    // If the video is visible, play it; if not, pause it
                    if (isVisible && video.paused) {
                        video.play();
                    } else if (!isVisible && !video.paused) {
                        video.pause();
                    }
                });
            }

            // Check for visibility on scroll and resize
            window.addEventListener("scroll", checkVideoPlayback);
            window.addEventListener("resize", checkVideoPlayback);

            // Initial check on page load
            checkVideoPlayback();
        });


    </script>

</body>

</html>

<?php
mysqli_close($conn);
?>