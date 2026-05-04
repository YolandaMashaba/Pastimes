<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_role('admin');

$user  = current_user();
$flash = get_flash();

// Fetch all customers
$all_users = $pdo
    ->query("SELECT * FROM tbluser ORDER BY created_at DESC")
    ->fetchAll();

// Pending only
$pending_users = array_filter($all_users, fn($u) => $u['verification_status'] === 'pending');

// Fetch items for moderation
$all_items = $pdo
    ->query("SELECT c.*, u.first_name AS seller_name, u.last_name AS seller_lastname
               FROM tblclothes c
               JOIN tbluser u ON c.seller_id = u.user_id
               ORDER BY c.created_at DESC")
    ->fetchAll();

$pending_items = array_filter($all_items, fn($i) => $i['status'] === 'pending');

// Stats
$total      = count($all_users);
$pending_ct = count($pending_users);
$verified_ct = $total - $pending_ct;
$total_items = count($all_items);
$pending_items_ct = count($pending_items);
$active_items_ct = count(array_filter($all_items, fn($i) => $i['status'] === 'active'));

// Initialize variables to prevent undefined errors
$total = $total ?? 0;
$pending_ct = $pending_ct ?? 0;
$verified_ct = $verified_ct ?? 0;
$total_items = $total_items ?? 0;
$pending_items_ct = $pending_items_ct ?? 0;
$active_items_ct = $active_items_ct ?? 0;
$all_users = $all_users ?? [];
$pending_users = $pending_users ?? [];
$all_items = $all_items ?? [];
$pending_items = $pending_items ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Customer Management | Pastimes</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<div class="container">
  <div class="admin-header">
    <h1> Admin Dashboard</h1>
    <p class="page-subtitle">Customer Registration Verification & Management</p>
  </div>

  <?php if ($flash): ?>
  <div class="flash flash-<?php echo htmlspecialchars($flash['type']); ?>">
    <?php echo htmlspecialchars($flash['message']); ?>
  </div>
  <?php endif; ?>

  <!-- Stats Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-number"><?php echo $total; ?></div>
      <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo $pending_ct; ?></div>
      <div class="stat-label">Pending Users</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo $total_items; ?></div>
      <div class="stat-label">Total Items</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo $pending_items_ct; ?></div>
      <div class="stat-label">Pending Items</div>
    </div>
  </div>

  <!-- Pending Users Section -->
  <?php if ($pending_ct > 0): ?>
  <div class="admin-section">
    <h2> Pending User Verifications</h2>
    <div class="table-scroll">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Phone</th>
            <th>User Type</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending_users as $user): ?>
          <tr>
            <td><?php echo $user['user_id']; ?></td>
            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['cellphone']); ?></td>
            <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
            <td>
              <form method="POST" action="actions/approve-user.php" style="display:inline;">
                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this user?')">✓ Approve</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Pending Items Section -->
  <?php if ($pending_items_ct > 0): ?>
  <div class="admin-section">
    <h2> Pending Item Approvals</h2>
    <div class="table-scroll">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Seller</th>
            <th>Price</th>
            <th>Submitted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending_items as $item): ?>
          <tr>
            <td><?php echo $item['clothes_id']; ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:.75rem;">
                <?php if ($item['image_path']): ?>
                  <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                       alt="<?php echo htmlspecialchars($item['title']); ?>"
                       style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                <?php else: ?>
                  <div style="width:48px;height:48px;background:var(--color-bg-secondary);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--color-text-muted);font-size:.8rem;">No Image</div>
                <?php endif; ?>
                <div>
                  <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                  <?php if ($item['description']): ?>
                  <div style="font-size:.78rem;color:var(--color-text-muted);margin-top:.2rem;"><?php echo htmlspecialchars(substr($item['description'], 0, 60)) . '...'; ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td><?php echo htmlspecialchars($item['seller_name'] . ' ' . $item['seller_lastname']); ?></td>
            <td>R <?php echo number_format((float)$item['price'], 2); ?></td>
            <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
            <td>
              <form method="POST" action="actions/approve-item.php" style="display:inline;">
                <input type="hidden" name="item_id" value="<?php echo $item['clothes_id']; ?>">
                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this item?')">✓ Approve</button>
              </form>
              <form method="POST" action="actions/reject-item.php" style="display:inline;margin-left:.5rem;">
                <input type="hidden" name="item_id" value="<?php echo $item['clothes_id']; ?>">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this item?')">✗ Reject</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- All Users Management -->
  <div class="admin-section">
    <h2> All Customer Management</h2>
    <div class="section-actions">
      <button class="btn btn-primary" onclick="openAddUserModal()">+ Add New Customer</button>
    </div>
    
    <div class="table-scroll">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Phone</th>
            <th>User Type</th>
            <th>Status</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_users as $user): ?>
          <tr>
            <td><?php echo $user['user_id']; ?></td>
            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars($user['cellphone']); ?></td>
            <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
            <td><span class="badge badge-<?php echo $user['verification_status']; ?>"><?php echo htmlspecialchars($user['verification_status']); ?></span></td>
            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
            <td>
              <button class="btn btn-sm btn-outline" onclick="openEditModal(<?php echo $user['user_id']; ?>)">Edit</button>
              <form method="POST" action="actions/delete-user.php" style="display:inline;" onsubmit="return confirm('Delete this user? This cannot be undone.')">
                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add/Edit User Modal (simplified for demo) -->
<div id="userModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeUserModal()">&times;</span>
    <h3 id="modalTitle">Manage User</h3>
    <p>Customer management functionality would be implemented here with forms to add/edit user details.</p>
    <button class="btn btn-outline" onclick="closeUserModal()">Close</button>
  </div>
</div>

<script>
function openAddUserModal() {
  document.getElementById('modalTitle').textContent = 'Add New Customer';
  document.getElementById('userModal').style.display = 'block';
}

function openEditModal(userId) {
  document.getElementById('modalTitle').textContent = 'Edit Customer #' + userId;
  document.getElementById('userModal').style.display = 'block';
}

function closeUserModal() {
  document.getElementById('userModal').style.display = 'none';
}
</script>

</body>
</html>