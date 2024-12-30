<?php

require 'config.php';
require 'function.php';

$message = "";

// Check if reviewer email exists in the session
if (isset($_SESSION['reviewer_email'])) {
    $reviewerEmail = $_SESSION['reviewer_email']; // Get the email from session

    // Fetch reviewer's information from the database
    $reviewerInfo = fetchSingle($conn, "SELECT * FROM Reviewers WHERE email = :email", ['email' => $reviewerEmail]);

    // Check if reviewer information is found
    if ($reviewerInfo === false) {
        // Reviewer not found
        $message = "<p style='color:red;'>No reviewer information found. Please update your details.</p>";
        $reviewerInfo = []; // Set to empty array to prevent warnings
    }
} else {
    // Redirect to login page if session is not set or email is not in session
    header("Location: auth.php"); // Redirecting to login page
    exit;
}

// Handle Save or Update Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'reviewer_name' => $_POST['reviewer_name'],
        'affiliation' => $_POST['affiliation'],
        'email' => $_POST['email'],
        'specialization' => $_POST['specialization'],
        'max_papers' => $_POST['max_papers'],
        'tel_no' => $_POST['tel_no'],
        'postal_address' => $_POST['postal_address'],
        'reviewer_email' => $reviewerEmail
    ];

    if (isset($_POST['save_profile'])) {
        // Check if reviewer already exists
        $existingReviewer = fetchSingle($conn, "SELECT * FROM Reviewers WHERE email = :email", ['email' => $data['email']]);
        
        if ($existingReviewer) {
            $message = "<p style='color:red;'>Reviewer already exists. Use 'Update' to modify details.</p>";
        } else {
            executeQuery($conn, "INSERT INTO Reviewers (reviewer_name, affiliation, email, specialization, max_papers, tel_no, postal_address) 
                VALUES (:reviewer_name, :affiliation, :email, :specialization, :max_papers, :tel_no, :postal_address)", $data);
            $reviewerInfo = $data;
            $message = "<p style='color:green;'>Reviewer details saved successfully.</p>";
        }
    } elseif (isset($_POST['update_profile'])) {
        // Update existing reviewer details
        executeQuery($conn, "UPDATE Reviewers SET reviewer_name = :reviewer_name, affiliation = :affiliation, 
            email = :email, specialization = :specialization, max_papers = :max_papers, tel_no = :tel_no, postal_address = :postal_address
            WHERE email = :reviewer_email", $data);
        $reviewerInfo = $data;
        $message = "<p style='color:green;'>Reviewer details updated successfully.</p>";
    }
}

// Fetch papers assigned to the reviewer
$papersToReview = fetchAll($conn, "
    SELECT p.paper_id, p.title, p.abstract, p.keywords, p.status, p.file_path, a.author_name, a.email 
    FROM Papers p 
    JOIN Author a ON p.author_email = a.email
    JOIN Paper_Review_Assignments pra ON p.paper_id = pra.paper_id
    WHERE pra.reviewer_email = :reviewer_email
", ['reviewer_email' => $reviewerEmail]);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Dashboard</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        textarea, select, button, input {
            margin: 5px;
            padding: 5px;
        }
        .form-container {
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>Reviewer Dashboard</h1>

    <!-- Display success or error messages -->
    <?php if (!empty($message)): ?>
        <div><?= $message; ?></div>
    <?php endif; ?>

    <!-- Profile and Update Profile Form combined -->
    <h2>Your Profile</h2>
    <form action="" method="POST">
        <div class="form-container">
            <label for="reviewer_name">Name:</label>
            <input type="text" name="reviewer_name" value="<?= htmlspecialchars($reviewerInfo['reviewer_name'] ?? ''); ?>" required><br>

            <label for="affiliation">Affiliation:</label>
            <input type="text" name="affiliation" value="<?= htmlspecialchars($reviewerInfo['affiliation'] ?? ''); ?>" required><br>

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($reviewerInfo['email'] ?? ''); ?>" required><br>

            <label for="specialization">Specialization:</label>
            <input type="text" name="specialization" value="<?= htmlspecialchars($reviewerInfo['specialization'] ?? ''); ?>" required><br>

            <label for="max_papers">Max Papers to Review:</label>
            <input type="number" name="max_papers" value="<?= htmlspecialchars($reviewerInfo['max_papers'] ?? ''); ?>" required><br>

            <label for="tel_no">Telephone:</label>
            <input type="text" name="tel_no" value="<?= htmlspecialchars($reviewerInfo['tel_no'] ?? ''); ?>" required><br>

            <label for="postal_address">Postal Address:</label>
            <textarea name="postal_address" required><?= htmlspecialchars($reviewerInfo['postal_address'] ?? ''); ?></textarea><br>

            <!-- Save and Update Buttons -->
            <button type="submit" name="save_profile">Save Profile</button>
            <button type="submit" name="update_profile">Update Profile</button>
        </div>
    </form>

    <!-- Display Papers Assigned for Review -->
    <h2>Your Papers to Review</h2>
    <table>
        <tr>
            <th>Title</th>
            <th>Abstract</th>
            <th>Keywords</th>
            <th>Status</th>
            <th>File</th>
            <th>Action</th>
        </tr>
        <?php if (!empty($papersToReview)): ?>
            <?php foreach ($papersToReview as $paper): ?>
            <tr>
                <td><?= htmlspecialchars($paper['title']); ?></td>
                <td><?= htmlspecialchars($paper['abstract']); ?></td>
                <td><?= htmlspecialchars($paper['keywords']); ?></td>
                <td><?= htmlspecialchars($paper['status']); ?></td>
                <td><a href="<?= htmlspecialchars($paper['file_path']); ?>" target="_blank">Download Paper</a></td>
                <td>
                    <form action="" method="POST">
                        <input type="hidden" name="paper_id" value="<?= $paper['paper_id']; ?>">
                        <textarea name="review_comments" placeholder="Enter your comments" required></textarea><br>
                        <select name="review_status" required>
                            <option value="approved">Approve</option>
                            <option value="rejected">Reject</option>
                        </select><br>
                        <button type="submit" name="submit_review">Submit Review</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No papers assigned for review.</td>
            </tr>
        <?php endif; ?>
    </table>

    <!-- Logout Form -->
    <form action="logout.php" method="POST" style="margin-top:20px;">
        <button type="submit" name="logout">Logout</button>
    </form>
</body>
</html>
