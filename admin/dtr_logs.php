<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// --- Date filter ---
$where = [];
$params = [];

if (!empty($_GET['start_date'])) {
    $where[] = "DATE(dl.timestamp) >= :start_date";
    $params[':start_date'] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $where[] = "DATE(dl.timestamp) <= :end_date";
    $params[':end_date'] = $_GET['end_date'];
}

$whereSQL = '';
if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

// --- Fetch all DTR logs with location ---
$stmt = $pdo->prepare("
    SELECT dl.*, u.username, l.name AS location_name
    FROM dtr_logs dl
    JOIN users u ON dl.user_id = u.id
    LEFT JOIN tagged_locations l ON dl.location_id = l.id
    $whereSQL
    ORDER BY u.username, dl.timestamp ASC
");
$stmt->execute($params);
$allLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Pair time_in and time_out per user ---
$logs = [];
$tempTimeIn = [];
$tempTimeOut = [];

foreach ($allLogs as $log) {
    $user = $log['username'];
    if (!isset($logs[$user])) $logs[$user] = [];

    if ($log['action'] === 'time_in') {
        $tempTimeIn[$user][] = $log;
    } elseif ($log['action'] === 'time_out') {
        $tempTimeOut[$user][] = $log;
    }
}

// --- Build paired logs ---
foreach ($tempTimeIn as $user => $ins) {
    foreach ($ins as $inLog) {
        $timeIn = new DateTime($inLog['timestamp']);
        $locationIn = $inLog['location_name'] ?? 'Unknown';
        $pairedTimeOut = null;

        if (!empty($tempTimeOut[$user])) {
            foreach ($tempTimeOut[$user] as $key => $outLog) {
                $timeOutCandidate = new DateTime($outLog['timestamp']);
                if ($timeOutCandidate >= $timeIn) {
                    $pairedTimeOut = $outLog;
                    unset($tempTimeOut[$user][$key]);
                    break;
                }
            }
        }

        if ($pairedTimeOut) {
            $timeOut = new DateTime($pairedTimeOut['timestamp']);
            $locationOut = $pairedTimeOut['location_name'] ?? $locationIn;

            $workedSeconds = $timeOut->getTimestamp() - $timeIn->getTimestamp();

            // Lunch break adjustment (12:00 - 13:00)
            $lunchStart = new DateTime($timeIn->format('Y-m-d') . ' 12:00:00');
            $lunchEnd   = new DateTime($timeIn->format('Y-m-d') . ' 13:00:00');
            if ($timeIn < $lunchEnd && $timeOut > $lunchStart) {
                $overlapStart = max($timeIn, $lunchStart);
                $overlapEnd   = min($timeOut, $lunchEnd);
                $workedSeconds -= ($overlapEnd->getTimestamp() - $overlapStart->getTimestamp());
            }

            $hours = floor($workedSeconds / 3600);
            $minutes = floor(($workedSeconds % 3600) / 60);

            $logs[$user][] = [
                'date' => $timeIn->format('Y-m-d'),
                'time_in' => $timeIn->format('h:i A'),
                'time_out' => $timeOut->format('h:i A'),
                'hours_worked' => "{$hours}h {$minutes}m",
                'location_in' => $locationIn,
                'location_out' => $locationOut
            ];
        } else {
            $logs[$user][] = [
                'date' => $timeIn->format('Y-m-d'),
                'time_in' => $timeIn->format('h:i A'),
                'time_out' => 'N/A',
                'hours_worked' => '0h 0m',
                'location_in' => $locationIn,
                'location_out' => 'N/A'
            ];
        }
    }
}

// --- Any leftover unmatched time_outs ---
foreach ($tempTimeOut as $user => $outs) {
    foreach ($outs as $outLog) {
        $timeOut = new DateTime($outLog['timestamp']);
        $logs[$user][] = [
            'date' => $timeOut->format('Y-m-d'),
            'time_in' => 'N/A',
            'time_out' => $timeOut->format('h:i A'),
            'hours_worked' => '0h 0m',
            'location_in' => 'N/A',
            'location_out' => $outLog['location_name'] ?? 'Unknown'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DTR Logs - Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
<style>
@media print {
    body {
        background: #fff;
        color: #000;
    }
    .no-print { display: none; }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #000 !important;
        padding: 8px;
    }
    th {
        background-color: #eee !important;
    }
}
</style>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
  <aside class="w-64 bg-gradient-to-br from-[#667eea] to-[#764ba2] text-white shadow-2xl no-print">
    <div class="px-6 py-6 flex items-center space-x-3">
      <img src="../assets/logo.jpg" alt="Logo" class="w-10 h-10 rounded-full" />
      <h2 class="text-2xl font-bold tracking-wide text-white">Monitoring</h2>
    </div>
    <nav class="px-6 py-8 space-y-6">
      <a href="dashboard.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">📊 Dashboard</a>
      <a href="user_management.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">👥 User Management</a>
      <a href="create_group.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">🗂️ Create Group</a>
      <a href="create_task.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">✅ Assign Task</a>
      <a href="dtr_logs.php" class="block text-base font-medium px-3 py-2 rounded bg-gradient-to-r from-red-500 to-purple-600 shadow-lg">📄 DTR Logs</a>
      <a href="individual_reports.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">📤Individual Reports</a>
      <hr class="my-4 border-gray-600" />
      <a href="../includes/logout.php" class="block text-base font-semibold text-gray-200 hover:text-red-200 transition duration-500">🚪 Logout</a>
    </nav>
  </aside>

  <!-- Main content -->
  <div class="flex-1 flex flex-col">
    <header class="bg-white shadow px-6 py-4 flex justify-between items-center no-print">
      <h1 class="text-xl font-semibold">DTR Logs</h1>
      <div class="text-sm text-gray-600">Hello, <?= htmlspecialchars($_SESSION['username']); ?></div>
    </header>

    <main class="p-6">
      <div class="bg-white rounded-xl shadow-md p-6 overflow-x-auto">

        <h2 class="text-lg font-semibold mb-4">All Users' DTR Logs</h2>

        <!-- Date Filter -->
        <div class="mb-4 no-print">
          <form method="GET" class="flex items-center space-x-3">
            <div>
              <label for="start_date" class="text-sm font-medium">From:</label>
              <input type="date" name="start_date" id="start_date" value="<?= $_GET['start_date'] ?? '' ?>" class="border rounded px-2 py-1">
            </div>
            <div>
              <label for="end_date" class="text-sm font-medium">To:</label>
              <input type="date" name="end_date" id="end_date" value="<?= $_GET['end_date'] ?? '' ?>" class="border rounded px-2 py-1">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Filter</button>
          </form>
        </div>

        <!-- Print Button -->
        <div class="mb-4 flex justify-end no-print">
            <button onclick="printDTR()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                🖨️ Print DTR
            </button>
        </div>

        <!-- DTR Table -->
        <table id="dtrTable" class="min-w-full border border-gray-200 rounded-lg">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="px-4 py-3 border-b text-left">#</th>
              <th class="px-4 py-3 border-b text-left">Username</th>
              <th class="px-4 py-3 border-b text-left">Date</th>
              <th class="px-4 py-3 border-b text-left">Time In</th>
              <th class="px-4 py-3 border-b text-left">Time Out</th>
              <th class="px-4 py-3 border-b text-left">Hours Worked</th>
              <th class="px-4 py-3 border-b text-left">Location In</th>
              <th class="px-4 py-3 border-b text-left">Location Out</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $counter = 1;
            $hasLogs = false;
            foreach ($logs as $username => $userLogs):
                foreach ($userLogs as $log): 
                    $hasLogs = true; ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border-b"><?= $counter++ ?></td>
                <td class="px-4 py-2 border-b"><?= htmlspecialchars($username) ?></td>
                <td class="px-4 py-2 border-b"><?= $log['date'] ?></td>
                <td class="px-4 py-2 border-b"><?= $log['time_in'] ?></td>
                <td class="px-4 py-2 border-b"><?= $log['time_out'] ?></td>
                <td class="px-4 py-2 border-b"><?= $log['hours_worked'] ?></td>
                <td class="px-4 py-2 border-b"><?= htmlspecialchars($log['location_in']) ?></td>
                <td class="px-4 py-2 border-b"><?= htmlspecialchars($log['location_out']) ?></td>
              </tr>
            <?php endforeach; endforeach; 
            if (!$hasLogs): ?>
              <tr>
                <td colspan="8" class="px-4 py-2 text-center text-gray-500">No DTR records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>

      </div>
    </main>
  </div>
</div>

<script>
feather.replace();

function printDTR() {
    const printContent = document.getElementById('dtrTable').outerHTML;
    const originalContent = document.body.innerHTML;

    document.body.innerHTML = `
        <div style="text-align:center; margin-bottom:20px;">
            <h1 style="font-size:24px;">DIS COMPANY</h1>
            <h2 style="font-size:20px;">Daily Time Record (DTR)</h2>
        </div>
        ${printContent}
    `;

    window.print();
    document.body.innerHTML = originalContent;
    feather.replace();
}
</script>
</body>
</html>
