<?php
require_once '../includes/config.php';
$success = '';
$error = '';
$groups = [];
$tasks = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Delete Task
        if (isset($_POST['delete_id'])) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([(int)$_POST['delete_id']]);
            $success = "Task deleted successfully.";

        // Edit Task
        } elseif (isset($_POST['edit_id'])) {
            $stmt = $pdo->prepare("UPDATE tasks SET task_name = ?, due_date = ?, description = ?, group_id = ? WHERE id = ?");
            $stmt->execute([
                trim($_POST['edit_task_name']),
                $_POST['edit_due_date'] ?: null,
                trim($_POST['edit_description']) ?: null,
                (int)$_POST['edit_group_id'],
                (int)$_POST['edit_id']
            ]);
            $success = "Task updated successfully.";

        // Add New Task
        } else {
            $groupId = (int)($_POST['group_id'] ?? 0);
            $taskName = trim($_POST['task_name'] ?? '');
            $dueDate = $_POST['due_date'] ?: null;
            $desc = trim($_POST['description'] ?? '');

            if ($groupId && $taskName !== '') {
                // Check if group already has active task
                $check = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE group_id = ? AND (due_date IS NULL OR due_date >= CURDATE())");
                $check->execute([$groupId]);
                if ($check->fetchColumn() > 0) {
                    $error = "This group already has an active task!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tasks (group_id, task_name, due_date, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$groupId, $taskName, $dueDate, $desc]);
                    $success = "Task assigned successfully!";
                }
            } else {
                $error = "Group and Task Name are required.";
            }
        }
    }

    // Fetch only groups without active tasks
    $groups = $pdo->query("
        SELECT id, name FROM groups
        WHERE id NOT IN (
            SELECT group_id FROM tasks
            WHERE due_date IS NULL OR due_date >= CURDATE()
        )
        ORDER BY name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all tasks for display
    $tasks = $pdo->query("
        SELECT t.id, t.task_name, t.due_date, t.description, g.name AS group_name, t.group_id
        FROM tasks t
        LEFT JOIN groups g ON t.group_id = g.id
        ORDER BY t.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    $error = "Database error: " . htmlspecialchars($ex->getMessage());
    $tasks = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Assign Task</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
// Open edit modal
function openTaskEditModal(id, name, due, desc, group_id) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_task_name').value = name;
    document.getElementById('edit_due_date').value = due;
    document.getElementById('edit_description').value = desc;
    document.getElementById('edit_group_id').value = group_id;
    document.getElementById('taskEditModal').classList.remove('hidden');
}

// Close edit modal
function closeTaskEditModal() {
    document.getElementById('taskEditModal').classList.add('hidden');
}

// Filter tasks table
function filterTasks() {
    const filter = document.getElementById('taskSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#taskTable tbody tr');
    rows.forEach(row => {
        const taskName = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
        const groupName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        row.style.display = (taskName.includes(filter) || groupName.includes(filter)) ? '' : 'none';
    });
}
</script>
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
      <a href="create_group.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">🗂️ Create Group</a>
      <a href="create_task.php" class="block text-base font-medium text-white bg-gradient-to-r from-red-500 to-purple-600 px-3 py-2 rounded">✅ Assign Task</a>
      <a href="dtr_logs.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">📄 DTR Logs</a>
      <a href="individual_reports.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">📤 Individual Reports</a>
      <hr class="my-4 border-gray-600" />
      <a href="../includes/logout.php" class="block text-base font-semibold text-red-300 hover:text-red-100">🚪 Logout</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-6 overflow-y-auto">

    <!-- Add Task Form -->
    <div class="bg-white rounded-2xl shadow-md p-8 mb-6">
        <h2 class="text-2xl font-semibold mb-4">✅ Assign New Task</h2>
        <?php if($success): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded border border-green-200"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if($error): ?><div class="mb-4 p-3 bg-red-50 text-red-700 rounded border border-red-200"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label>Select Group</label>
                <select name="group_id" required class="w-full p-2 border rounded">
                    <option value="">-- Choose Group --</option>
                    <?php foreach($groups as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Task Name</label>
                <input type="text" name="task_name" required class="w-full p-2 border rounded">
            </div>
            <div>
                <label>Due Date</label>
                <input type="date" name="due_date" class="w-full p-2 border rounded">
            </div>
            <div>
                <label>Description</label>
                <textarea name="description" rows="3" class="w-full p-2 border rounded"></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Assign Task</button>
            </div>
        </form>
    </div>

    <!-- Search bar -->
    <div class="mb-4">
        <input type="text" id="taskSearch" onkeyup="filterTasks()" placeholder="Search tasks..." class="w-full p-2 border rounded">
    </div>

    <!-- Task Table -->
    <div class="bg-white rounded-2xl shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-4">📋 Assigned Tasks</h2>
        <div class="overflow-x-auto">
            <table id="taskTable" class="min-w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-50 text-xs text-gray-600 uppercase">
                    <tr>
                        <th class="px-4 py-2">Task</th>
                        <th class="px-4 py-2">Group</th>
                        <th class="px-4 py-2">Due</th>
                        <th class="px-4 py-2">Description</th>
                        <th class="px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($tasks): ?>
                        <?php foreach($tasks as $t): ?>
                        <tr>
                            <td class="px-4 py-2"><?= htmlspecialchars($t['task_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($t['group_name']) ?></td>
                            <td class="px-4 py-2"><?= $t['due_date'] ? date("m/d/Y", strtotime($t['due_date'])) : 'No Due' ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($t['description']) ?></td>
                            <td class="px-4 py-2 space-x-2">
                                <button type="button" onclick="openTaskEditModal('<?= $t['id'] ?>','<?= addslashes($t['task_name']) ?>','<?= $t['due_date'] ?>','<?= addslashes($t['description']) ?>','<?= $t['group_id'] ?>')" class="px-3 py-1 text-white rounded bg-green-500">Edit</button>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this task?');">
                                    <input type="hidden" name="delete_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="px-3 py-1 text-white rounded bg-red-500">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-gray-500">No tasks assigned yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

  </main>
</div>

<!-- Edit Modal -->
<div id="taskEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-xl w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">✏️ Edit Task</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="edit_id" id="edit_id">
            <div>
                <label>Task Name</label>
                <input type="text" name="edit_task_name" id="edit_task_name" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label>Group</label>
                <select name="edit_group_id" id="edit_group_id" class="w-full p-2 border rounded" required>
                    <?php foreach($groups as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Due Date</label>
                <input type="date" name="edit_due_date" id="edit_due_date" class="w-full p-2 border rounded">
            </div>
            <div>
                <label>Description</label>
                <textarea name="edit_description" id="edit_description" rows="3" class="w-full p-2 border rounded"></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeTaskEditModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
