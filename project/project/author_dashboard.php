<?php
require 'config.php';
require 'function.php';

// Start the session
session_start();

$message = "";

// Initialize author info
$authorInfo = null;

// Check if author is logged in
if (isset($_SESSION['author_email'])) {
    $authorEmail = $_SESSION['author_email'];
    $authorInfo = fetchSingle($conn, "SELECT * FROM Author WHERE email = :email", ['email' => $authorEmail]);
}

// Handle "Save Author" functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_author'])) {
    $data = [
        'author_name' => $_POST['author_name'],
        'affiliation' => $_POST['affiliation'],
        'tel_no' => $_POST['tel_no'],
        'email' => $_SESSION['author_email'],
        'postal_address' => $_POST['postal_address']
    ];

    // Check if an author already exists
    $existingAuthor = fetchSingle($conn, "SELECT * FROM Author WHERE email = :email", ['email' => $data['email']]);
    if ($existingAuthor) {
        $message = "<p style='color:red;'>Author already exists. Use 'Update' to modify details.</p>";
    } else {
        executeQuery($conn, "INSERT INTO Author (author_name, affiliation, tel_no, email, postal_address) 
            VALUES (:author_name, :affiliation, :tel_no, :email, :postal_address)", $data);
        $authorInfo = $data;
        $message = "<p style='color:green;'>Author details saved successfully.</p>";
    }
}

// Handle "Update Author" functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_author'])) {
    if ($authorInfo) {
        $data = [
            'author_name' => $_POST['author_name'],
            'affiliation' => $_POST['affiliation'],
            'tel_no' => $_POST['tel_no'],
            'email' => $_SESSION['author_email'],
            'postal_address' => $_POST['postal_address']
        ];

        executeQuery($conn, "UPDATE Author SET author_name = :author_name, affiliation = :affiliation, 
            tel_no = :tel_no, postal_address = :postal_address WHERE email = :email", $data);
        $authorInfo = $data;
        $message = "<p style='color:green;'>Author details updated successfully.</p>";
    } else {
        $message = "<p style='color:red;'>No author details found to update.</p>";
    }
}
// Handle Paper Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_paper'])) {
    if (isset($_SESSION['author_email'])) {
        $authorEmail = $_SESSION['author_email'];

        $authorInfo = fetchSingle($conn, "SELECT * FROM Author WHERE email = :email", ['email' => $authorEmail]);

        if (!$authorInfo) {
            $message = "<p style='color:red;'>Author not found. Please add your author details first.</p>";
        } else {
            $data = [
                'title' => $_POST['title'],
                'abstract' => $_POST['abstract'],
                'keywords' => $_POST['keywords'],
                'paper_type' => $_POST['paper_type'],
                'author_email' => $authorEmail
            ];

            if (isset($_FILES['paper_file']) && $_FILES['paper_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['paper_file']['tmp_name'];
                $fileName = uniqid() . '_' . $_FILES['paper_file']['name'];
                $filePath = 'uploads/' . $fileName;

                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if ($fileExtension !== 'pdf') {
                    $message = "<p style='color:red;'>Only PDF files are allowed.</p>";
                } else {
                    if (is_writable('uploads/')) {
                        if (move_uploaded_file($fileTmpPath, $filePath)) {
                            $data['file_path'] = $filePath;

                            executeQuery($conn, "INSERT INTO Papers (title, abstract, keywords, paper_type, file_path, author_email, status) 
                                    VALUES (:title, :abstract, :keywords, :paper_type, :file_path, :author_email, 'pending')", $data);
                            $message = "<p style='color:green;'>Paper submitted successfully!</p>";
                        } else {
                            $message = "<p style='color:red;'>Failed to upload the file. Please try again.</p>";
                        }
                    } else {
                        $message = "<p style='color:red;'>Upload directory is not writable. Please check permissions.</p>";
                    }
                }
            } else {
                $message = "<p style='color:red;'>Please upload a valid PDF file.</p>";
            }
        }
    } else {
        $message = "<p style='color:red;'>You must be logged in to submit a paper.</p>";
    }
}

// Fetch papers submitted by the logged-in author
$authorPapers = [];
if (isset($_SESSION['author_email'])) {
    $authorPapers = fetchAll($conn, "SELECT * FROM Papers WHERE author_email = :author_email", ['author_email' => $_SESSION['author_email']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Author Dashboard</title>
</head>
<body>
    <div class="container">
        <h1>Welcome, Author</h1>
        <h2>Manage Your Information</h2>

        <!-- Display messages -->
        <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

        <!-- Add/Update Author Form -->
        <div class="section">
            <h3>Author Information</h3>
            <form action="" method="POST">
                <label for="author_name">Author Name:</label>
                <input type="text" id="author_name" name="author_name" 
                       value="<?= $authorInfo ? htmlspecialchars($authorInfo['author_name']) : '' ?>" required>

                <label for="affiliation">Affiliation:</label>
                <input type="text" id="affiliation" name="affiliation" 
                       value="<?= $authorInfo ? htmlspecialchars($authorInfo['affiliation']) : '' ?>" required>

                <label for="tel_no">Telephone:</label>
                <input type="tel" id="tel_no" name="tel_no" 
                       value="<?= $authorInfo ? htmlspecialchars($authorInfo['tel_no']) : '' ?>" required>

                <label for="email">Email (Read-Only):</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_SESSION['author_email']) ?>" readonly>

                <label for="postal_address">Postal Address:</label>
                <textarea id="postal_address" name="postal_address" required><?= $authorInfo ? htmlspecialchars($authorInfo['postal_address']) : '' ?></textarea>

                <!-- Save and Update Buttons -->
                <button type="submit" name="save_author">Save Author</button>
                <button type="submit" name="update_author">Update Author</button>
            </form>
        </div>

        <!-- Display Author's Information -->
        <?php if ($authorInfo): ?>
        <div class="section">
            <h3>Current Author Information</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($authorInfo['author_name']) ?></p>
            <p><strong>Affiliation:</strong> <?= htmlspecialchars($authorInfo['affiliation']) ?></p>
            <p><strong>Telephone:</strong> <?= htmlspecialchars($authorInfo['tel_no']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($authorInfo['email']) ?></p>
            <p><strong>Postal Address:</strong> <?= nl2br(htmlspecialchars($authorInfo['postal_address'])) ?></p>
        </div>
        <?php endif; ?>
        <!-- Submit Paper Form -->
        <div class="section">
            <h3>Submit a New Paper</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <label for="title">Paper Title:</label>
                <input type="text" id="title" name="title" required>

                <label for="abstract">Abstract:</label>
                <textarea id="abstract" name="abstract" required></textarea>

                <label for="keywords">Keywords:</label>
                <input type="text" id="keywords" name="keywords" required>

                <label for="paper_type">Paper Type:</label>
                <select id="paper_type" name="paper_type" required>
                    <option value="research">Full</option>
                    <option value="review">Short</option>
                </select>

                <label for="paper_file">Upload Paper (PDF only):</label>
                <input type="file" id="paper_file" name="paper_file" accept="application/pdf" required>

                <button type="submit" name="submit_paper">Submit Paper</button>
            </form>
        </div>

        <!-- View Submitted Papers -->
        <div class="section">
            <h3>Your Submitted Papers</h3>
            <?php if (!empty($authorPapers)): ?>
                <ul>
                    <?php foreach ($authorPapers as $paper): ?>
                        <li>
                            <strong><?= htmlspecialchars($paper['title']) ?></strong> - 
                            Status: <em><?= htmlspecialchars($paper['status']) ?></em>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No papers submitted yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
