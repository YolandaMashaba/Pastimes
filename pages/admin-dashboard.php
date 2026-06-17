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

  <!-- Quick Actions -->
  <div class="admin-section">
    <h2>Quick Actions</h2>
    <div class="action-buttons">
      <a href="/pastimes-marketplace-v2/pages/messages.php" class="btn btn-primary"><i class="fas fa-envelope"></i> Messages</a>
      <button class="btn btn-secondary" onclick="openAddUserModal()"><i class="fas fa-user-plus"></i> Add User</button>
      <button class="btn btn-secondary" onclick="openAddItemModal()"><i class="fas fa-plus"></i> Add Item</button>
    </div>
  </div>

  <!-- Start New Conversation -->
  <div class="admin-section">
    <h2>Start New Conversation</h2>
    <form method="GET" action="/pastimes-marketplace-v2/pages/messages.php" class="inline-form">
      <div class="form-group">
        <label for="message_user">Select User</label>
        <select id="message_user" name="user_id" required>
          <option value="">Select a user...</option>
          <?php foreach ($all_users as $u): ?>
          <option value="<?php echo $u['user_id']; ?>">
            <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name'] . ' (' . $u['username'] . ')'); ?>
            (<?php echo htmlspecialchars($u['role']); ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">Open Conversation</button>
    </form>
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
              <?php if (in_array($user['role'] ?? '', ['seller', 'both'])): ?>
                <a href="/pastimes-marketplace-v2/pages/admin-verify.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">Review Documents</a>
              <?php else: ?>
                <form method="POST" action="actions/approve-user.php" style="display:inline;" class="approve-user-form">
                  <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                  <button type="submit" class="btn btn-sm btn-success">✓ Approve</button>
                </form>
              <?php endif; ?>
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
                  <?php
                  $image_src = $item['image_path'];
                  // Fix inconsistent image paths
                  if (strpos($image_src, '/pastimes-marketplace-v2') === false) {
                    $image_src = '/pastimes-marketplace-v2' . $image_src;
                  }
                  ?>
                  <img src="<?php echo htmlspecialchars($image_src); ?>"
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
              <form method="POST" action="actions/approve-item.php" style="display:inline;" class="approve-item-form">
                <input type="hidden" name="item_id" value="<?php echo $item['clothes_id']; ?>">
                <button type="submit" class="btn btn-sm btn-success">✓ Approve</button>
              </form>
              <form method="POST" action="actions/reject-item.php" style="display:inline;margin-left:.5rem;" class="reject-item-form">
                <input type="hidden" name="item_id" value="<?php echo $item['clothes_id']; ?>">
                <button type="submit" class="btn btn-sm btn-danger">✗ Reject</button>
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
              <form method="POST" action="actions/delete-user.php" style="display:inline;" class="delete-user-form">
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

  <!-- All Items Management -->
  <div class="admin-section">
    <h2> All Items Management</h2>
    <div class="section-actions">
      <button class="btn btn-primary" onclick="openAddItemModal()">+ Add New Item</button>
    </div>

    <div class="table-scroll">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Seller</th>
            <th>Price</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($all_items as $item): ?>
          <tr>
            <td><?php echo $item['clothes_id']; ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:.75rem;">
                <?php if ($item['image_path']): ?>
                  <?php
                  $image_src = $item['image_path'];
                  // Fix inconsistent image paths
                  if (strpos($image_src, '/pastimes-marketplace-v2') === false) {
                    $image_src = '/pastimes-marketplace-v2' . $image_src;
                  }
                  ?>
                  <img src="<?php echo htmlspecialchars($image_src); ?>"
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
            <td><span class="badge badge-<?php echo $item['status']; ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
            <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
            <td>
              <button class="btn btn-sm btn-outline" onclick="openEditItemModal(<?php echo $item['clothes_id']; ?>)">Edit</button>
              <form method="POST" action="actions/delete-item.php" style="display:inline;" class="delete-item-form">
                <input type="hidden" name="item_id" value="<?php echo $item['clothes_id']; ?>">
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

<!-- Add/Edit User Modal -->
<div id="userModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeUserModal()">&times;</span>
    <h3 id="modalTitle">Manage User</h3>
    <form method="POST" action="actions/manage-user.php" id="userForm">
      <input type="hidden" name="user_id" id="modalUserId" value="">
      <div class="form-group">
        <label for="modalFirstName">First Name *</label>
        <input type="text" id="modalFirstName" name="first_name" required>
      </div>
      <div class="form-group">
        <label for="modalLastName">Last Name *</label>
        <input type="text" id="modalLastName" name="last_name" required>
      </div>
      <div class="form-group">
        <label for="modalUsername">Username *</label>
        <input type="text" id="modalUsername" name="username" required>
      </div>
      <div class="form-group">
        <label for="modalEmail">Email *</label>
        <input type="email" id="modalEmail" name="email" required>
      </div>
      <div class="form-group">
        <label for="modalCellphone">Phone Number *</label>
        <input type="text" id="modalCellphone" name="cellphone" required>
      </div>
      <div class="form-group">
        <label for="modalPassword">Password (for new users only)</label>
        <input type="password" id="modalPassword" name="password">
      </div>
      <div class="form-group">
        <label for="modalRole">Role *</label>
        <select id="modalRole" name="role" required>
          <option value="buyer">Buyer</option>
          <option value="seller">Seller</option>
          <option value="both">Both</option>
        </select>
      </div>
      <div class="form-group">
        <label for="modalVerificationStatus">Verification Status *</label>
        <select id="modalVerificationStatus" name="verification_status" required>
          <option value="pending">Pending</option>
          <option value="verified">Verified</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeUserModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Add/Edit Item Modal -->
<div id="itemModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeItemModal()">&times;</span>
    <h3 id="itemModalTitle">Add New Item</h3>
    <form method="POST" action="actions/admin-add-item.php" id="itemForm" enctype="multipart/form-data">
      <input type="hidden" name="item_id" id="modalItemId" value="">
      <div class="form-group">
        <label for="itemTitle">Title *</label>
        <input type="text" id="itemTitle" name="title" required placeholder="e.g. Vintage Denim Jacket">
      </div>
      <div class="form-group">
        <label for="itemDescription">Description</label>
        <textarea id="itemDescription" name="description" rows="3" placeholder="Describe the condition, size, and style..."></textarea>
      </div>
      <div class="form-group">
        <label for="itemPrice">Price (R) *</label>
        <input type="number" id="itemPrice" name="price" required min="1" step="0.01" placeholder="450.00">
      </div>
      <div class="form-group">
        <label for="itemCategory">Category *</label>
        <select id="itemCategory" name="category" required>
          <option value="tops">Tops</option>
          <option value="bottoms">Bottoms</option>
          <option value="dresses">Dresses</option>
          <option value="outerwear">Outerwear</option>
          <option value="more">More</option>
        </select>
      </div>
      <div class="form-group">
        <label for="itemSellerId">Seller ID *</label>
        <input type="number" id="itemSellerId" name="seller_id" required placeholder="Enter seller user ID">
      </div>
      <div class="form-group">
        <label for="itemStatus">Status *</label>
        <select id="itemStatus" name="status" required>
          <option value="active">Active</option>
          <option value="pending">Pending</option>
          <option value="sold">Sold</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="form-group">
        <label for="itemImage">Item Image</label>
        <input type="file" id="itemImage" name="image" accept="image/jpeg,image/png,image/webp" class="file-input">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeItemModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="itemSubmitBtn">Add Item</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddUserModal() {
  document.getElementById('modalTitle').textContent = 'Add New Customer';
  document.getElementById('modalUserId').value = '';
  document.getElementById('userForm').reset();
  document.getElementById('userModal').style.display = 'block';
}

function openEditModal(userId) {
  document.getElementById('modalTitle').textContent = 'Edit Customer #' + userId;
  document.getElementById('modalUserId').value = userId;
  document.getElementById('userModal').style.display = 'block';
}

function closeUserModal() {
  document.getElementById('userModal').style.display = 'none';
}

function openAddItemModal() {
  document.getElementById('itemModalTitle').textContent = 'Add New Item';
  document.getElementById('modalItemId').value = '';
  document.getElementById('itemForm').reset();
  document.getElementById('itemForm').action = 'actions/admin-add-item.php';
  document.getElementById('itemSubmitBtn').textContent = 'Add Item';
  document.getElementById('itemModal').style.display = 'block';
}

function openEditItemModal(itemId) {
  document.getElementById('itemModalTitle').textContent = 'Edit Item #' + itemId;
  document.getElementById('modalItemId').value = itemId;
  document.getElementById('itemForm').action = 'actions/admin-edit-item.php';
  document.getElementById('itemSubmitBtn').textContent = 'Update Item';
  document.getElementById('itemModal').style.display = 'block';
}

function closeItemModal() {
  document.getElementById('itemModal').style.display = 'none';
}
</script>

<script src="/pastimes-marketplace-v2/assets/js/custom-alert.js"></script>
<script>
// Handle approve user
document.querySelectorAll('.approve-user-form').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const confirmed = await customConfirm('Approve this user?', 'Approve User');
    if (confirmed) {
      form.submit();
    }
  });
});

// Handle approve item
document.querySelectorAll('.approve-item-form').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const confirmed = await customConfirm('Approve this item?', 'Approve Item');
    if (confirmed) {
      form.submit();
    }
  });
});

// Handle reject item
document.querySelectorAll('.reject-item-form').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const confirmed = await customConfirm('Reject this item?', 'Reject Item');
    if (confirmed) {
      form.submit();
    }
  });
});

// Handle delete user
document.querySelectorAll('.delete-user-form').forEach(form => {
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const confirmed = await customConfirmDanger('Delete this user? This cannot be undone.', 'Delete User');
    if (confirmed) {
      form.submit();
    }
  });
});
</script>

</body>
</html>