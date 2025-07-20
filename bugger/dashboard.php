<?php
session_start();
require 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all users for "Assign To" dropdown (exclude self to avoid self-assign if you want)
$users_stmt = $conn->prepare("SELECT id, username FROM users WHERE id != ?");
$users_stmt->execute([$user_id]);
$all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Add Bug
if (isset($_POST['add_bug'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $assigned_to = $_POST['assigned_to'];
    $reported_by = $user_id;

    if ($title && $description && $assigned_to) {
        $stmt = $conn->prepare("INSERT INTO bugs (title, description, status, created_by, assigned_to, reported_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $status, $user_id, $assigned_to, $reported_by]);
        header('Location: dashboard.php');
        exit;
    }
}

// Handle Edit Bug
if (isset($_POST['edit_bug'])) {
    $bug_id = $_POST['bug_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $assigned_to = $_POST['assigned_to'];

    if ($title && $description) {
        $stmt = $conn->prepare("UPDATE bugs SET title = ?, description = ?, status = ?, assigned_to = ? WHERE id = ? AND created_by = ?");
        $stmt->execute([$title, $description, $status, $assigned_to, $bug_id, $user_id]);
        header('Location: dashboard.php');
        exit;
    }
}

// Handle Delete Bug
if (isset($_POST['delete_bug'])) {
    $bug_id = $_POST['bug_id'];
    $stmt = $conn->prepare("DELETE FROM bugs WHERE id = ? AND created_by = ?");
    $stmt->execute([$bug_id, $user_id]);
    header('Location: dashboard.php');
    exit;
}

// Fetch user's bugs with assigned and reported usernames
$stmt = $conn->prepare("
    SELECT b.*, 
           u1.username AS assigned_username,
           u2.username AS reported_username 
    FROM bugs b
    LEFT JOIN users u1 ON b.assigned_to = u1.id
    LEFT JOIN users u2 ON b.reported_by = u2.id
    WHERE b.created_by = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard - 🐞Bug Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #fff;
        }
        .navbar {
            background-color: #4A90E2;
        }
        .navbar-brand, .nav-link, .btn {
            color: #fff !important;
        }
        .btn-blue {
            background-color: #4A90E2;
            color: white;
        }
        .btn-blue:hover {
            background-color: #3a78d1;
        }
        .card {
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .modal-header, .modal-footer {
            border:none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg px-4">
    <a class="navbar-brand fw-bold" href="#">🐞Bug Tracker</a>
    <div class="ms-auto d-flex align-items-center">
        <span class="text-white me-3">Hello, <?=htmlspecialchars($_SESSION['username'])?></span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color:#4A90E2;">Reported Bugs</h2>
        <button class="btn btn-blue" data-bs-toggle="modal" data-bs-target="#addBugModal">Add New Bug</button>
    </div>

    <?php if(count($bugs) === 0): ?>
        <p class="text-center text-secondary">There are no bugs reported yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Reported By</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($bugs as $bug): ?>
                    <tr>
                        <td><?=htmlspecialchars($bug['title'])?></td>
                        <td>
                            <?php
                                $color = 'secondary';
                                if ($bug['status'] === 'Open') $color = 'danger';
                                elseif ($bug['status'] === 'In Progress') $color = 'warning';
                                elseif ($bug['status'] === 'Closed') $color = 'success';
                            ?>
                            <span class="badge bg-<?=$color?>"><?=$bug['status']?></span>
                        </td>
                        <td><?=htmlspecialchars($bug['assigned_username'] ?? 'Unassigned')?></td>
                        <td><?=htmlspecialchars($bug['reported_username'] ?? 'Unknown')?></td>
                        <td><?=date('d M Y, H:i', strtotime($bug['created_at']))?></td>
                        <td class="text-end">
                            <!-- View -->
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewBugModal<?=$bug['id']?>">View</button>

                            <!-- Edit -->
                            <button class="btn btn-sm btn-blue" data-bs-toggle="modal" data-bs-target="#editBugModal<?=$bug['id']?>">Edit</button>

                            <!-- Delete -->
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteBugModal<?=$bug['id']?>">Delete</button>
                        </td>
                    </tr>

                    <!-- View Modal -->
                    <div class="modal fade" id="viewBugModal<?=$bug['id']?>" tabindex="-1" aria-labelledby="viewBugLabel<?=$bug['id']?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="viewBugLabel<?=$bug['id']?>"><?=htmlspecialchars($bug['title'])?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <p><strong>Description:</strong><br><?=nl2br(htmlspecialchars($bug['description']))?></p>
                            <p><strong>Status:</strong> <?=$bug['status']?></p>
                            <p><strong>Assigned To:</strong> <?=htmlspecialchars($bug['assigned_username'] ?? 'Unassigned')?></p>
                            <p><strong>Reported By:</strong> <?=htmlspecialchars($bug['reported_username'] ?? 'Unknown')?></p>
                            <p><small>Created at: <?=date('d M Y, H:i', strtotime($bug['created_at']))?></small></p>
                            <p><small>Last updated: <?=date('d M Y, H:i', strtotime($bug['updated_at']))?></small></p>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editBugModal<?=$bug['id']?>" tabindex="-1" aria-labelledby="editBugLabel<?=$bug['id']?>" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="POST">
                            <div class="modal-header">
                              <h5 class="modal-title" id="editBugLabel<?=$bug['id']?>">Edit Bug</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <input type="hidden" name="bug_id" value="<?=$bug['id']?>">
                              <div class="mb-3">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" required value="<?=htmlspecialchars($bug['title'])?>">
                              </div>
                              <div class="mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="4" required><?=htmlspecialchars($bug['description'])?></textarea>
                              </div>
                              <div class="mb-3">
                                <label>Status</label>
                                <select name="status" class="form-select" required>
                                  <option value="Open" <?=$bug['status']=='Open'?'selected':''?>>Open</option>
                                  <option value="In Progress" <?=$bug['status']=='In Progress'?'selected':''?>>In Progress</option>
                                  <option value="Closed" <?=$bug['status']=='Closed'?'selected':''?>>Closed</option>
                                </select>
                              </div>
                              <div class="mb-3">
                                <label>Assign To</label>
                                <select name="assigned_to" class="form-select" required>
                                  <option value="">Select user</option>
                                  <?php foreach ($all_users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $bug['assigned_to'] == $user['id'] ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($user['username']) ?>
                                    </option>
                                  <?php endforeach; ?>
                                </select>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="submit" name="edit_bug" class="btn btn-blue">Save Changes</button>
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteBugModal<?=$bug['id']?>" tabindex="-1" aria-labelledby="deleteBugLabel<?=$bug['id']?>" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <form method="POST">
                            <div class="modal-header">
                              <h5 class="modal-title" id="deleteBugLabel<?=$bug['id']?>">Confirm Delete</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              Are you sure you want to delete the bug "<strong><?=htmlspecialchars($bug['title'])?></strong>"?
                              <input type="hidden" name="bug_id" value="<?=$bug['id']?>">
                            </div>
                            <div class="modal-footer">
                              <button type="submit" name="delete_bug" class="btn btn-danger">Delete</button>
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Add Bug Modal -->
<div class="modal fade" id="addBugModal" tabindex="-1" aria-labelledby="addBugLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
<div class="modal-header">
<h5 class="modal-title" id="addBugLabel">Add New Bug</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="mb-3">
<label>Title</label>
<input type="text" name="title" class="form-control" required>
</div>
<div class="mb-3">
<label>Description</label>
<textarea name="description" class="form-control" rows="4" required></textarea>
</div>
<div class="mb-3">
<label>Status</label>
<select name="status" class="form-select" required>
<option value="Open" selected>Open</option>
<option value="In Progress">In Progress</option>
<option value="Closed">Closed</option>
</select>
</div>
<div class="mb-3">
<label>Assign To</label>
<select name="assigned_to" class="form-select" required>
<option value="">Select user</option>
<?php foreach ($all_users as $user): ?>
<option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<div class="modal-footer">
<button type="submit" name="add_bug" class="btn btn-blue">Add Bug</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
</div>
</form>
</div>

</div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> </body> </html>  
