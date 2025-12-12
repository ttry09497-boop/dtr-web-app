<?php
require_once '../includes/config.php';

$success = '';
$error   = '';

// Handle form submission for creating group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $groupName = trim($_POST['group_name']);
    $selectedUsers = $_POST['users'] ?? [];
    $selectedLocations = $_POST['locations'] ?? [];

    if (!empty($groupName)) {
        $stmt = $pdo->prepare("INSERT INTO groups (name) VALUES (?)");
        $stmt->execute([$groupName]);
        $groupId = $pdo->lastInsertId();

        if (!empty($selectedUsers)) {
            $stmtUser = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (?, ?)");
            $stmtUpdate = $pdo->prepare("UPDATE users SET status = 'assigned' WHERE id = ?");
            foreach ($selectedUsers as $userId) {
                $stmtUser->execute([$groupId, $userId]);
                $stmtUpdate->execute([$userId]);
            }
        }

        if (!empty($selectedLocations)) {
            $stmtLoc = $pdo->prepare("INSERT INTO group_locations (group_id, location_id) VALUES (?, ?)");
            foreach ($selectedLocations as $locId) {
                $stmtLoc->execute([$groupId, $locId]);
            }
        }

        $success = "Group created successfully!";
    } else {
        $error = "Group name is required.";
    }
}
if (!empty($selectedUsers)) {
    // Remove duplicates
    $selectedUsers = array_unique($selectedUsers);

    $stmtUser = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (?, ?)");
    $stmtUpdate = $pdo->prepare("UPDATE users SET status = 'assigned' WHERE id = ?");

    foreach ($selectedUsers as $userId) {
        // Check if the user is already assigned to the group
        $check = $pdo->prepare("SELECT COUNT(*) FROM group_users WHERE group_id = ? AND user_id = ?");
        $check->execute([$groupId, $userId]);
        if ($check->fetchColumn() == 0) {
            $stmtUser->execute([$groupId, $userId]);
            $stmtUpdate->execute([$userId]);
        }
    }
}
if (!empty($selectedUsers)) {
    // Remove duplicates
    $selectedUsers = array_unique($selectedUsers);

    $stmtUser = $pdo->prepare("INSERT INTO group_users (group_id, user_id) VALUES (?, ?)");
    $stmtUpdate = $pdo->prepare("UPDATE users SET status = 'assigned' WHERE id = ?");

    foreach ($selectedUsers as $userId) {
        // Check if the user is already assigned to the group
        $check = $pdo->prepare("SELECT COUNT(*) FROM group_users WHERE group_id = ? AND user_id = ?");
        $check->execute([$groupId, $userId]);
        if ($check->fetchColumn() == 0) {
            $stmtUser->execute([$groupId, $userId]);
            $stmtUpdate->execute([$userId]);
        }
    }
}


// Fetch all users with status dynamically
$allUsersStmt = $pdo->query("
    SELECT u.id, u.username, u.phone,
        CASE WHEN gu.user_id IS NOT NULL THEN 'assigned' ELSE 'available' END AS status
    FROM users u
    LEFT JOIN group_users gu ON u.id = gu.user_id
    ORDER BY u.username ASC
");
$allUsers = $allUsersStmt->fetchAll(PDO::FETCH_ASSOC);


// Fetch locations
$stmt = $pdo->query("SELECT id, name, latitude, longitude, radius FROM tagged_locations ORDER BY name ASC");
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch created groups
$stmt = $pdo->query("
    SELECT g.id, g.name, g.created_at,
           (SELECT COUNT(*) FROM group_users gu WHERE gu.group_id = g.id) AS members,
           (SELECT COUNT(*) FROM group_locations gl WHERE gl.group_id = g.id) AS locations
    FROM groups g
    ORDER BY g.created_at DESC
");
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Create Group</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 font-sans">

<div class="flex h-screen">
   <!-- Sidebar -->
   <aside class="w-64 bg-gradient-to-br from-[#667eea] to-[#764ba2] text-white shadow-2xl">
      <div class="px-6 py-6 shadow-md border-b border-gray-700 flex items-center space-x-3">
         <img src="../assets/logo.jpg" alt="Logo" class="w-10 h-10 rounded-full" /> 
         <h2 class="text-2xl font-bold tracking-wide text-white">Monitoring</h2>
      </div>
      <nav class="px-6 py-8 space-y-6">
         <a href="dashboard.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">📊 Dashboard</a>
         <a href="user_management.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">👥 User Management</a>
         <a href="create_group.php" class="block text-base font-medium text-white bg-gradient-to-r from-red-500 to-purple-600 px-3 py-2 rounded">🗂️ Create Group</a>
         <a href="create_task.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">✅ Assign Task</a>
         <a href="dtr_logs.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">📄 DTR Logs</a>
         <a href="individual_reports.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">📤 Individual Reports</a>
         <hr class="my-4 border-gray-600" /> 
         <a href="../includes/logout.php" class="block text-base font-semibold text-red-300 hover:text-red-100">🚪 Logout</a>
      </nav>
   </aside>

   <!-- Main -->
   <main class="flex-1 p-6 overflow-y-auto">
      <!-- Create Group Form -->
      <div class="bg-white rounded-2xl shadow-md p-8 mb-8">
         <h2 class="text-2xl font-semibold mb-6">🗂️ Create New Group</h2>

         <?php if ($success): ?>
            <div class="mb-6 p-4 rounded bg-green-50 text-green-700 border"><?= htmlspecialchars($success) ?></div>
         <?php elseif ($error): ?>
            <div class="mb-6 p-4 rounded bg-red-50 text-red-700 border"><?= htmlspecialchars($error) ?></div>
         <?php endif; ?>

         <form method="POST" class="space-y-6">
            <div>
               <label class="block font-medium mb-2">Group Name</label>
               <input type="text" name="group_name" class="w-full p-3 border rounded-lg">
            </div>
<div>
   <label class="block font-medium mb-2">Select Users</label>

   <!-- Search Bar -->
   <input type="text" id="userSearch" placeholder="Search users..." 
          class="w-full mb-2 border rounded px-3 py-2" />

   <div id="userList" class="h-40 overflow-y-auto border rounded-lg divide-y">
      <?php 
      foreach ($allUsers as $u): 
          $isAssigned = ($u['status'] === 'assigned'); 
      ?>
         <label class="flex items-center justify-between p-3 hover:bg-gray-50 user-row">
            <div>
               <p class="font-medium"><?= htmlspecialchars($u['username']) ?></p>
            </div>
            <div class="flex items-center space-x-2">
                <input type="checkbox" name="users[]" value="<?= $u['id'] ?>" 
                       <?= $isAssigned ? 'disabled' : '' ?> 
                       data-user-id="<?= $u['id'] ?>">
                
                <?php 
                if($isAssigned) {
                    echo '<span class="text-green-600 font-semibold ml-2">Assigned</span>';
                }
                ?>
            </div>
         </label>
      <?php endforeach; ?>
   </div>
</div>

<script>
  const searchInput = document.getElementById('userSearch');
  const userList = document.getElementById('userList');
  const userRows = userList.getElementsByClassName('user-row');

  searchInput.addEventListener('keyup', () => {
    const filter = searchInput.value.toLowerCase();

    for (let row of userRows) {
      const username = row.querySelector('p').textContent.toLowerCase();
      if (username.includes(filter)) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    }
  });
</script>


            <div>
               <label class="block font-medium mb-2">Select Locations</label>
               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <?php foreach ($locations as $loc): ?>
                     <label class="flex items-center justify-between border rounded-lg p-4 hover:bg-gray-50">
                        <div>
                           <p class="font-medium"><?= htmlspecialchars($loc['name']) ?></p>
                           <p class="text-sm text-gray-500">Lat: <?= $loc['latitude'] ?>, Lng: <?= $loc['longitude'] ?> (<?= $loc['radius'] ?>m)</p>
                        </div>
                        <input type="checkbox" name="locations[]" value="<?= $loc['id'] ?>">
                     </label>
                  <?php endforeach; ?>
               </div>
            </div>

            <div class="flex justify-end space-x-3">
               <a href="dashboard.php" class="px-5 py-2 border rounded text-gray-600">Cancel</a>
               <button type="submit" name="create_group" class="px-6 py-2 text-white rounded bg-gradient-to-r from-green-400 to-green-600">Create Group</button>
            </div>
         </form>
      </div>

      <!-- Created Groups -->
      <div class="bg-white rounded-2xl shadow-md p-8">
         <h2 class="text-2xl font-semibold mb-6">Created Groups</h2>
         <?php foreach ($groups as $g): ?>
            <div class="p-5 border rounded-xl mb-3 flex justify-between items-center">
               <div>
                  <p class="font-semibold"><?= htmlspecialchars($g['name']) ?></p>
                  <p class="text-sm"><?= $g['members'] ?> members • <?= $g['locations'] ?> locations</p>
                  <p class="text-xs text-gray-400">Created: <?= date("m/d/Y", strtotime($g['created_at'])) ?></p>
               </div>
               <div class="space-x-2">
                  <button data-id="<?= $g['id'] ?>" class="editBtn px-3 py-1 bg-blue-500 text-white rounded-full hover:bg-blue-600">Edit</button>
                  <button data-id="<?= $g['id'] ?>" class="viewBtn px-3 py-1 bg-gray-500 text-white rounded-full hover:bg-gray-600">View</button>
                  <a href="delete_group.php?id=<?= $g['id'] ?>" class="deleteBtn px-3 py-1 bg-red-500 text-white rounded-full hover:bg-red-600">Delete</a>
               </div>
            </div>
         <?php endforeach; ?>
      </div>
   </main>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
   <div id="editModalContent" class="bg-white rounded-xl p-6 w-96">
      <h2 class="text-xl font-bold mb-4">✏️ Edit Group</h2>
      <form id="editGroupForm">
          <input type="hidden" name="id" id="editGroupId">
          
          <div class="mb-4">
              <label class="block font-medium mb-1">Group Name</label>
              <input type="text" name="name" id="editGroupName" class="w-full p-2 border rounded-lg">
          </div>

          <div class="mb-4">
              <label class="block font-medium mb-1">Members</label>
              <div id="editMembers" class="h-40 overflow-y-auto border rounded-lg p-2">
                  <!-- Checkboxes will be loaded via JS -->
              </div>
          </div>

          <div class="mb-4">
              <label class="block font-medium mb-1">Locations</label>
              <div id="editLocations" class="h-40 overflow-y-auto border rounded-lg p-2">
                  <!-- Checkboxes will be loaded via JS -->
              </div>
          </div>

          <div class="flex justify-end space-x-2">
              <button type="button" id="closeEditModal" class="px-4 py-2 rounded bg-gray-300">Cancel</button>
              <button type="submit" class="px-4 py-2 rounded bg-green-500 text-white">Save Changes</button>
          </div>

          <div id="editError" class="mt-2 text-red-600 hidden"></div>
          <div id="editSuccess" class="mt-2 text-green-600 hidden">Changes saved successfully!</div>
      </form>
   </div>
</div>

<!-- View Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="modal-content bg-white p-6 rounded-xl shadow-xl w-full max-w-md">
    <h2 class="text-xl font-bold mb-4">👥 View Group</h2>
    
    <div class="mb-4">
      <p class="font-medium text-gray-700">Group Name:</p>
      <p id="view-group-name" class="text-gray-900"></p>
    </div>
    
    <div class="mb-4">
      <p class="font-medium text-gray-700">Members:</p>
      <ul id="view-group-members" class="list-disc list-inside text-gray-900"></ul>
    </div>
    
    <div class="mb-4">
      <p class="font-medium text-gray-700">Locations:</p>
      <ul id="view-group-locations" class="list-disc list-inside text-gray-900"></ul>
    </div>
    
    <div class="flex justify-end">
      <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
    </div>
  </div>
</div>

<script>
function closeViewModal(){ $('#viewModal').addClass('hidden'); }

$(document).ready(function(){

    // Open edit modal and load data via AJAX
    $('.editBtn').click(function(){
        let groupId = $(this).data('id');
        $('#editModal').removeClass('hidden');
        $('#editError').hide();
        $('#editSuccess').hide();

        $.ajax({
            url: 'load_group.php',
            method: 'GET',
            data: { id: groupId },
            dataType: 'json',
            success: function(data){
                $('#editGroupId').val(data.id);
                $('#editGroupName').val(data.name);

                // Users checkboxes
let membersHtml = '';
data.allUsers.forEach(user => {
    let checked = data.selectedMembers.includes(user.id) ? 'checked' : '';
    let disabled = data.assignedMembers.includes(user.id) ? 'disabled' : '';
    let assignedText = data.assignedMembers.includes(user.id) ? '<span class="text-green-600 font-semibold ml-2">Assigned</span>' : '';
    
    membersHtml += `<label class="flex justify-between p-2 hover:bg-gray-50">
                        <span>${user.username}</span>
                        <input type="checkbox" class="edit-user-checkbox" name="members[]" value="${user.id}" ${checked} ${disabled}>
                        ${assignedText}
                    </label>`;
});
$('#editMembers').html(membersHtml);

                // Locations checkboxes
                let locHtml = '';
                data.allLocations.forEach(loc => {
                    let checked = data.selectedLocations.includes(loc.id) ? 'checked' : '';
                    locHtml += `<label class="flex justify-between p-2 hover:bg-gray-50">
                                    <span>${loc.name} (${loc.radius}m)</span>
                                    <input type="checkbox" name="locations[]" value="${loc.id}" ${checked}>
                                </label>`;
                });
                $('#editLocations').html(locHtml);
            }
        });
    });

    // Red check effect while selecting users
    $('#editMembers').on('change', '.edit-user-checkbox', function(){
        if($(this).is(':checked')){
            $(this).addClass('checked-red'); // red check color
        } else {
            $(this).removeClass('checked-red');
        }
    });

    // Close modal
    $('#closeEditModal').click(function(){ $('#editModal').addClass('hidden'); });

    // AJAX submit for edit
    $('#editGroupForm').submit(function(e){
        e.preventDefault();
        $('#editError').hide();
        $('#editSuccess').hide();

        $.ajax({
            url: 'edit_group_ajax.php',
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp){
                if(resp.trim() === 'success'){
                    // After saving, show "Assigned" text and disable checkbox
                    $('#editMembers .edit-user-checkbox:checked').each(function(){
                        let span = $(this).siblings('span.assigned-text');
                        if(span.length === 0){
                            $(this).after('<span class="assigned-text text-green-600 font-semibold ml-2">Assigned</span>');
                        }
                        $(this).prop('disabled', true).removeClass('checked-red');
                    });

                    $('#editSuccess').show();
                    setTimeout(() => { $('#editModal').addClass('hidden'); location.reload(); }, 1000);
                } else { 
                    $('#editError').text(resp).show(); 
                }
            },
            error: function(xhr,status,error){ $('#editError').text('An error occurred: '+error).show(); }
        });
    });

    // View modal 
    $('.viewBtn').click(function(){
        let groupId = $(this).data('id');
        $('#viewModal').removeClass('hidden');
        $('#view-group-name').text('Loading...');
        $('#view-group-members').html('');
        $('#view-group-locations').html('');

        $.ajax({
            url: 'view_group.php',
            method: 'GET',
            data: { id: groupId },
            dataType: 'json',
            success: function(data){
                $('#view-group-name').text(data.name);
                data.members.forEach(user => $('#view-group-members').append('<li>'+user.username+'</li>'));
                data.locations.forEach(loc => $('#view-group-locations').append('<li>'+loc.name+'</li>'));
            }
        });
    });

    // Close view modal on clicking outside
    $('#viewModal').click(function(e){ if(e.target==this) $(this).addClass('hidden'); });

    // Delete confirmation
    $('.deleteBtn').click(function(e){
        if(!confirm('Are you sure you want to delete this group?')) e.preventDefault();
    });

});
</script>

<style>
/* Red check effect for selected users */
.checked-red {
    accent-color: red;
}
</style>

</body>
</html>
