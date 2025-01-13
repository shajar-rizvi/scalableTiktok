<?php
session_start();
$azureBaseURL = 'https://tiktokstorage.blob.core.windows.net/videos/';

// Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: signin_form.php");
    exit();
}

// Redirect if filename is not provided
if (!isset($_GET['filename']) || empty($_GET['filename'])) {
    header("Location: secure.php");
    exit();
}

$filename = $_GET['filename'];

// Database connection// Database connection
$hostname = "tiktikapp.mysql.database.azure.com";
$username = "tiktokadmin";
$password = "password!1";
$dbname = "tiktok";

$conn = mysqli_connect($hostname, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch video details based on filename
$query = "SELECT * FROM videos WHERE filename = '$filename'";
$result = mysqli_query($conn, $query);


// Check if video exists
// if (mysqli_num_rows($result) == 1) {
//     $video = mysqli_fetch_assoc($result);
// } else {
//     echo "Video not found.";
//     exit();
// }
if (mysqli_num_rows($result) == 1) {
    $video = mysqli_fetch_assoc($result);
} else {
    echo "Video not found.";
    exit();
}


$videoId = $video['id'];
$userId = $_SESSION['id'];

// Fetch existing comments for the video
$fetchCommentsQuery = "SELECT comments.*, users.username, DATE_FORMAT(upload_datetime, '%W, %M %e, %Y, %l:%i %p') AS formatted_datetime
                       FROM comments 
                       INNER JOIN users ON comments.commenter_id = users.id
                       WHERE comments.video_id = $videoId
                       ORDER BY comments.upload_datetime DESC";
$commentsResult = mysqli_query($conn, $fetchCommentsQuery);

// Check if user has liked or disliked the video
$checkLikeQuery = "SELECT * FROM likes WHERE video_id = $videoId AND user_id = $userId";
$checkDislikeQuery = "SELECT * FROM dislikes WHERE video_id = $videoId AND user_id = $userId";

$hasLiked = mysqli_num_rows(mysqli_query($conn, $checkLikeQuery)) > 0;
$hasDisliked = mysqli_num_rows(mysqli_query($conn, $checkDislikeQuery)) > 0;


// Count total likes and dislikes
$countLikesQuery = "SELECT COUNT(*) AS total_likes FROM likes WHERE video_id = $videoId";
$countDislikesQuery = "SELECT COUNT(*) AS total_dislikes FROM dislikes WHERE video_id = $videoId";

$totalLikesResult = mysqli_query($conn, $countLikesQuery);
$likesData = mysqli_fetch_assoc($totalLikesResult);
$totalLikes = $likesData['total_likes'];
echo $totalLikes; // Output the total likes


$totalDislikesResult = mysqli_query($conn, $countDislikesQuery);
$totalDislikes = mysqli_fetch_assoc($totalDislikesResult)['total_dislikes'];

// Process form submission to add new comment
if (isset($_POST['submit_comment'])) {
    $comment = $_POST['comment'];
    $commenter_id = $_SESSION['id'];

    $insertCommentQuery = "INSERT INTO comments (video_id, commenter_id, comment, upload_datetime) 
                       VALUES ($videoId, $commenter_id, '$comment', NOW())";



    if (mysqli_query($conn, $insertCommentQuery)) {
        // Redirect to prevent form resubmission
        
        header("Location: view_video1.php?filename=$filename");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Video</title>
    <style>
        /* Reset some default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 2em;
            margin-bottom: 10px;
        }

        p {
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        video {
            width: 100%;
            max-width: 640px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .previous {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 1em;
            text-decoration: none;
            color: #555;
            background-color: #e6e6e6;
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s;
        }

        .previous:hover {
            background-color: #ccc;
        }

        .like-dislike-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .like,
        .dislike {
            padding: 10px 20px;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s, color 0.3s;
            display: flex;
            align-items: center;
        }

        .like {
            background-color: #f1f1f1;
        }

        .dislike {
            background-color: #f1f1f1;
        }

        .like.clicked {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        .dislike.clicked {
            background-color: #f44336;
            color: white;
            border-color: #f44336;
        }

        .like span,
        .dislike span {
            margin-left: 8px;
        }

        .comments-section,
        .add-comment-section {
            margin-top: 40px;
        }

        .comment {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 5px solid #ddd;
        }

        .comment strong {
            font-weight: bold;
        }

        .add-comment-section textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 2px solid #ddd;
            resize: vertical;
            margin-bottom: 10px;
        }

        .add-comment-section button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .add-comment-section button:hover {
            background-color: #0056b3;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .like-dislike-buttons {
                flex-direction: column;
                align-items: flex-start;
            }

            .like,
            .dislike {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="previous">Back</a>
        <h2><?php echo $video['title']; ?></h2>
        <p><strong>Description:</strong> <?php echo $video['description'];?></p>
        <video controls>
            <?php $videoURL = $azureBaseURL . "videos/"  ?>
                        

            <source src="<?php echo $videoURL . $filename; ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>

        <!-- Like and Dislike Buttons -->
        <div class="like-dislike-buttons">
            <form action="" method="POST" id="likeDislikeForm">
                <button type="submit" name="like" class="like <?php echo $hasLiked ? 'clicked' : ''; ?>">Like
                    <span><?php echo $totalLikes; ?> Likes</span></button>
                <button type="submit" name="dislike"
                    class="dislike <?php echo $hasDisliked ? 'clicked' : ''; ?>">Dislike
                    <span><?php echo $totalDislikes; ?> Dislikes</span></button>
            </form>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <h3>Comments</h3>
            <?php
            if (mysqli_num_rows($commentsResult) > 0) {
                while ($comment = mysqli_fetch_assoc($commentsResult)) {
                    echo '<div class="comment"><strong>' . $comment['username'] . " - " . $comment['formatted_datetime'] . ':</strong> ' . $comment['comment'] . '</div>';
                }
            } else {
                echo '<p>No comments yet.</p>';
            }
            ?>
        </div>

        <!-- Add Comment Form -->
        <div class="add-comment-section">
            <h3>Add a Comment</h3>
            <form action="" method="POST">
                <textarea name="comment" rows="4" placeholder="Enter your comment" required></textarea>
                <button type="submit" name="submit_comment">Submit Comment</button>
            </form>
        </div>
    </div>

    <?php
    // Handle like and dislike submission
    
    if (isset($_POST['like'])) {
        if (!$hasLiked) {
            // If the user has previously disliked, remove the dislike
            if ($hasDisliked) {
                $deleteDislikeQuery = "DELETE FROM dislikes WHERE video_id = $videoId AND user_id = $userId";
                mysqli_query($conn, $deleteDislikeQuery);
            }
            $likeQuery = "INSERT INTO likes (video_id, user_id) VALUES ($videoId, $userId)";
            mysqli_query($conn, $likeQuery);
            // Reload the page to update the button status
            header("Location: view_video1.php?filename=$filename");
            exit();
        } else {
            // If the user has already liked, remove the like
            $deleteLikeQuery = "DELETE FROM likes WHERE video_id = $videoId AND user_id = $userId";
            mysqli_query($conn, $deleteLikeQuery);
            // Reload the page to update the button status
            header("Location: view_video1.php?filename=$filename");
            exit();
        }
    }
    if (isset($_POST['dislike'])) {
        if (!$hasDisliked) {
            // If the user has previously liked, remove the like
            if ($hasLiked) {
                $deleteLikeQuery = "DELETE FROM likes WHERE video_id = $videoId AND user_id = $userId";
                mysqli_query($conn, $deleteLikeQuery);
            }
            $dislikeQuery = "INSERT INTO dislikes (video_id, user_id) VALUES ($videoId, $userId)";
            mysqli_query($conn, $dislikeQuery);
            // Reload the page to update the button status
            header("Location: view_video1.php?filename=$filename");
            exit();
        } else {
            // If the user has already disliked, remove the dislike
            $deleteDislikeQuery = "DELETE FROM dislikes WHERE video_id = $videoId AND user_id = $userId";
            mysqli_query($conn, $deleteDislikeQuery);
            // Reload the page to update the button status
            header("Location: view_video1.php?filename=$filename");
            exit();
        }
    }
    ?>
</body>
</html>