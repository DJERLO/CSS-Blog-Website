<?php
require_once '../config.php';
// Start session
session_start();

// Set page title
$title = "Developers Management";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare('DELETE FROM developers WHERE id=?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $message = 'Developer entry deleted!';
    } else {
        $message = 'Delete failed.';
    }
    $stmt->close();
}

// Handle Edit (fetch data)
$edit = false;
$edit_entry = null;
if (isset($_GET['edit'])) {
    $edit = true;
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM developers WHERE id=$id");
    $edit_entry = $result->fetch_assoc();
}

// Handle Create or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $github_link = $_POST['github_link'];
    $image = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $img_name = basename($_FILES['image']['name']);
        $target = '../uploads/' . $img_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image = $img_name;
        }
    }
    
    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $id = intval($_POST['id']);
        if ($image) {
            $stmt = $conn->prepare('UPDATE developers SET title=?, content=?, image=?, github_link=? WHERE id=?');
            $stmt->bind_param('ssssi', $title, $content, $image, $github_link, $id);
        } else {
            $stmt = $conn->prepare('UPDATE developers SET title=?, content=?, github_link=? WHERE id=?');
            $stmt->bind_param('sssi', $title, $content, $github_link, $id);
        }
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: developers.php?msg=updated');
            exit();
        } else {
            $message = 'Update failed.';
        }
        $stmt->close();
    } else {
        // Create
        $stmt = $conn->prepare('INSERT INTO developers (title, content, image, github_link) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $title, $content, $image, $github_link);
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: developers.php?msg=added');
            exit();
        } else {
            $message = 'Error: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Show message from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $message = 'Developer entry added successfully!';
    if ($_GET['msg'] === 'updated') $message = 'Developer entry updated!';
}

// Fetch all entries
$entries = $conn->query('SELECT * FROM developers ORDER BY id DESC');

// Get current page for active state
$current_page = 'developers';

// Start buffering the content
ob_start();

?>
<!-- Main Content -->
<div class="main-content">
    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <h1>Developers Management</h1>
    <h2><?= $edit ? 'Edit Developer Entry' : 'Add New Developer Entry' ?></h2>
    <form method="post" enctype="multipart/form-data">
        <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= $edit_entry['id'] ?>">
        <?php endif; ?>
        <label>Title:<br><input type="text" name="title" required value="<?= $edit ? htmlspecialchars($edit_entry['title']) : '' ?>"></label><br><br>
        <label>Content:<br><textarea name="content" required><?= $edit ? htmlspecialchars($edit_entry['content']) : '' ?></textarea></label><br><br>
        <label>GitHub Link:<br><input type="url" name="github_link" placeholder="https://github.com/username" value="<?= $edit ? htmlspecialchars($edit_entry['github_link']) : '' ?>"></label><br><br>
        <label>Image:<br><input type="file" name="image" accept="image/*"></label>
        <?php if ($edit && $edit_entry['image']): ?>
            <br><img src="../uploads/<?= htmlspecialchars($edit_entry['image']) ?>" alt="" style="max-width:100px;">
        <?php endif; ?>
        <br><br>
        <button type="submit"><?= $edit ? 'Update Entry' : 'Add Entry' ?></button>
        <?php if ($edit): ?>
            <a href="developers.php">Cancel</a>
        <?php endif; ?>
    </form>

    <h2>All Developer Entries</h2>
    <table>
        <tr>
            <th>Title</th>
            <th>Content</th>
            <th>GitHub Link</th>
            <th>Image</th>
            <th>Actions</th>
            <th>Created At</th>
        </tr>
        <?php while ($row = $entries->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['content'])) ?></td>
                <td>
                    <?php if ($row['github_link']): ?>
                        <a href="<?= htmlspecialchars($row['github_link']) ?>" target="_blank">
                            <i class="fab fa-github"></i> View GitHub
                        </a>
                    <?php endif; ?>
                </td>
                <td><?php if ($row['image']): ?><img src="../uploads/<?= htmlspecialchars($row['image']) ?>" style="max-width:80px;"/><?php endif; ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>">Edit</a> |
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this entry?');">Delete</a>
                </td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php
$content = ob_get_clean();
include 'includes/base.php';