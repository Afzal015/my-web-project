<?php
require 'config.php';
require 'function.php';

$message = "";

// Update Paper Status
if (isset($_POST['update_status'])) {
    $data = [
        'status' => $_POST['status'],
        'paper_id' => $_POST['paper_id']
    ];
    executeQuery($conn, "UPDATE Papers SET status = :status WHERE paper_id = :paper_id", $data);
    $message = "Paper status updated successfully.";
}

// Assign Paper to Reviewer
if (isset($_POST['assign_reviewer'])) {
    $paper_id = $_POST['paper_id'];
    $reviewer_email = $_POST['reviewer_email'];

    if (!empty($reviewer_email)) {
        // Check if the reviewer exists
        $reviewer_exists = fetchOne($conn, "SELECT COUNT(*) AS count FROM reviewer WHERE email = :email", ['email' => $reviewer_email]);

        if ($reviewer_exists['count'] == 0) {
            $message = "Reviewer email does not exist.";
        } else {
            // Check if an assignment already exists
            $assignment_exists = fetchOne($conn, "SELECT COUNT(*) AS count FROM paper_review_assignments WHERE paper_id = :paper_id", ['paper_id' => $paper_id]);

            if ($assignment_exists['count'] > 0) {
                // Update existing assignment
                executeQuery($conn, "UPDATE paper_review_assignments SET reviewer_email = :reviewer_email WHERE paper_id = :paper_id", [
                    'paper_id' => $paper_id,
                    'reviewer_email' => $reviewer_email
                ]);
            } else {
                // Insert a new assignment if it doesn't exist
                executeQuery($conn, "INSERT INTO paper_review_assignments (paper_id, reviewer_email) VALUES (:paper_id, :reviewer_email)", [
                    'paper_id' => $paper_id,
                    'reviewer_email' => $reviewer_email
                ]);
            }
            $message = "Paper assigned to reviewer successfully.";
        }
    } else {
        $message = "Please select a reviewer.";
    }
}

// Fetch Authors
$authors = fetchAll($conn, "SELECT * FROM Author");

// Fetch Reviewers
$reviewers = fetchAll($conn, "SELECT * FROM Reviewers");

// Fetch Papers
$papers = fetchAll($conn, "
    SELECT p.paper_id, p.title, p.abstract, p.keywords, p.status, 
           a.author_name, a.email AS author_email, 
           r.reviewer_name, pra.reviewer_email, p.file_path
    FROM Papers p
    LEFT JOIN Author a ON p.author_email = a.email
    LEFT JOIN paper_review_assignments pra ON p.paper_id = pra.paper_id
    LEFT JOIN Reviewers r ON pra.reviewer_email = r.email
");

$users = fetchAll($conn, "SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Admin Dashboard</h1>

    <!-- Success Message -->
    <?php if (!empty($message)) echo "<p style='color: green;'>$message</p>"; ?>

    <!-- Users Section -->
    <h2>Registered Users</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']); ?></td>
                <td><?= htmlspecialchars($user['name']); ?></td>
                <td><?= htmlspecialchars($user['email']); ?></td>
                <td><?= htmlspecialchars($user['role']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Authors Section -->
    <h2>Authors</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Affiliation</th>
            <th>Telephone</th>
            <th>Postal Address</th>
        </tr>
        <?php foreach ($authors as $author): ?>
            <tr>
                <td><?= htmlspecialchars($author['author_id']); ?></td>
                <td><?= htmlspecialchars($author['author_name']); ?></td>
                <td><?= htmlspecialchars($author['email']); ?></td>
                <td><?= htmlspecialchars($author['affiliation']); ?></td>
                <td><?= htmlspecialchars($author['tel_no']); ?></td>
                <td><?= htmlspecialchars($author['postal_address']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Reviewers Section -->
    <h2>Reviewers</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Affiliation</th>
            <th>Specialization</th>
            <th>Max Papers</th>
        </tr>
        <?php foreach ($reviewers as $reviewer): ?>
            <tr>
                <td><?= htmlspecialchars($reviewer['reviewer_id']); ?></td>
                <td><?= htmlspecialchars($reviewer['reviewer_name']); ?></td>
                <td><?= htmlspecialchars($reviewer['email']); ?></td>
                <td><?= htmlspecialchars($reviewer['affiliation']); ?></td>
                <td><?= htmlspecialchars($reviewer['specialization']); ?></td>
                <td><?= htmlspecialchars($reviewer['max_papers']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Papers Section -->
    <h2>Papers</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Abstract</th>
            <th>Keywords</th>
            <th>Status</th>
            <th>Author</th>
            <th>Reviewer</th>
            <th>File</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($papers as $paper): ?>
            <tr>
                <td><?= htmlspecialchars($paper['paper_id']); ?></td>
                <td><?= htmlspecialchars($paper['title']); ?></td>
                <td><?= htmlspecialchars($paper['abstract']); ?></td>
                <td><?= htmlspecialchars($paper['keywords']); ?></td>
                <td><?= htmlspecialchars($paper['status']); ?></td>
                <td><?= htmlspecialchars($paper['author_name']) . " (" . htmlspecialchars($paper['author_email']) . ")"; ?></td>
                <td><?= htmlspecialchars($paper['reviewer_name'] ?? 'Not Assigned'); ?></td>
                <td><a href="<?= htmlspecialchars($paper['file_path']); ?>" target="_blank">Download</a></td>
                <td>
                    <!-- Update Paper Status -->
                    <form method="POST">
                        <input type="hidden" name="paper_id" value="<?= $paper['paper_id']; ?>">
                        <select name="status">
                            <option value="pending" <?= $paper['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="under review" <?= $paper['status'] === 'under review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?= $paper['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?= $paper['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" name="update_status">Update</button>
                    </form>

                    <!-- Assign Reviewer -->
                    <form method="POST">
                        <input type="hidden" name="paper_id" value="<?= $paper['paper_id']; ?>">
                        <select name="reviewer_email" required>
                            <option value="">Select Reviewer</option>
                            <?php foreach ($reviewers as $reviewer): ?>
                                <option value="<?= htmlspecialchars($reviewer['email']); ?>" 
                                    <?= $paper['reviewer_email'] === $reviewer['email'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($reviewer['reviewer_name']); ?> (<?= htmlspecialchars($reviewer['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_reviewer">Assign</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
