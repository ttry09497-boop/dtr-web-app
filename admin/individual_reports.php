<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Ensure required columns exist
function ensureColumn(PDO $pdo, $table, $column, $definition) {
    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column
    ");
    $check->execute([':table'=>$table, ':column'=>$column]);
    if ($check->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
    }
}

ensureColumn($pdo, 'users', 'salary_rate', "DECIMAL(10,2) NOT NULL DEFAULT 0");
ensureColumn($pdo, 'dtr_logs', 'working_hours', "DECIMAL(10,2) NULL DEFAULT NULL");
ensureColumn($pdo, 'dtr_logs', 'salary', "DECIMAL(12,2) NULL DEFAULT NULL");

// AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_row') {
    header('Content-Type: application/json; charset=utf-8');
    $in_id = intval($_POST['in_id'] ?? 0);
    $working_hours = floatval($_POST['working_hours'] ?? 0);
    $salary = floatval($_POST['salary'] ?? 0);

    if ($in_id <= 0) {
        echo json_encode(['success'=>false, 'msg'=>'Invalid ID']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE dtr_logs SET working_hours = :wh, salary = :s WHERE id = :id");
    if($stmt->execute([':wh'=>$working_hours, ':s'=>$salary, ':id'=>$in_id])) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'msg'=>'DB update failed']);
    }
    exit;
}

// Users list
$usersStmt = $pdo->query("SELECT id, username, COALESCE(salary_rate,0) AS salary_rate, position FROM users ORDER BY username ASC");
$usersList = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Filters
$selectedUserId = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? intval($_GET['user_id']) : '';
$selectedSalaryRate = 0;
$selectedUserName = '';
$selectedUserPosition = 'N/A';
if ($selectedUserId) {
    $stmt = $pdo->prepare("SELECT username, COALESCE(salary_rate,0) AS salary_rate, position FROM users WHERE id=?");
    $stmt->execute([$selectedUserId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row){
        $selectedUserName = $row['username'];
        $selectedSalaryRate = floatval($row['salary_rate']);
        $selectedUserPosition = $row['position'] ?: 'N/A';
    }
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = [];
$params = [];
if($selectedUserId){ $where[]="dl.user_id=:user_id"; $params[':user_id']=$selectedUserId; }
if($start_date){ $where[]="DATE(dl.timestamp)>=:start_date"; $params[':start_date']=$start_date; }
if($end_date){ $where[]="DATE(dl.timestamp)<=:end_date"; $params[':end_date']=$end_date; }
$whereSQL = !empty($where) ? 'WHERE '.implode(' AND ',$where) : '';

// Fetch DTR logs with locations
$stmt = $pdo->prepare("
    SELECT dl.*, l.name AS location_name 
    FROM dtr_logs dl 
    LEFT JOIN tagged_locations l ON dl.location_id = l.id
    $whereSQL
    ORDER BY dl.timestamp ASC
");
$stmt->execute($params);
$allLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pair time_in & time_out
$logs = [];
$tempTimeIn = [];
$tempTimeOut = [];

foreach($allLogs as $log){
    if($log['action']=='time_in') $tempTimeIn[]=$log;
    elseif($log['action']=='time_out') $tempTimeOut[]=$log;
}

foreach($tempTimeIn as $inLog){
    $timeIn = new DateTime($inLog['timestamp']);
    $locationIn = $inLog['location_name'] ?? 'Unknown';
    $pairedTimeOut = null; $pairedOutIndex = null;

    foreach($tempTimeOut as $idx=>$outLog){
        $timeOutCandidate = new DateTime($outLog['timestamp']);
        if($timeOutCandidate >= $timeIn){
            $pairedTimeOut = $outLog;
            $pairedOutIndex = $idx;
            break;
        }
    }

    if($pairedTimeOut){
        unset($tempTimeOut[$pairedOutIndex]);
        $timeOut = new DateTime($pairedTimeOut['timestamp']);
        $locationOut = $pairedTimeOut['location_name'] ?? $locationIn;
        $workedSeconds = $timeOut->getTimestamp() - $timeIn->getTimestamp();

        // Lunch break 12-1pm adjustment
        $lunchStart = new DateTime($timeIn->format('Y-m-d').' 12:00:00');
        $lunchEnd = new DateTime($timeIn->format('Y-m-d').' 13:00:00');
        if($timeIn < $lunchEnd && $timeOut > $lunchStart){
            $overlapStart = $timeIn>$lunchStart?$timeIn:$lunchStart;
            $overlapEnd = $timeOut<$lunchEnd?$timeOut:$lunchEnd;
            $workedSeconds -= ($overlapEnd->getTimestamp()-$overlapStart->getTimestamp());
        }

        $hoursDecimal = round($workedSeconds/3600,2);
        $computedSalary = round($hoursDecimal*$selectedSalaryRate,2);
        $saved_hours = isset($inLog['working_hours']) ? floatval($inLog['working_hours']) : $hoursDecimal;
        $saved_salary = isset($inLog['salary']) ? floatval($inLog['salary']) : round($saved_hours*$selectedSalaryRate,2);

        $logs[] = [
            'in_id'=>intval($inLog['id']),
            'out_id'=>intval($pairedTimeOut['id']),
            'date'=>$timeIn->format('Y-m-d'),
            'time_in'=>$timeIn->format('h:i A'),
            'time_out'=>$timeOut->format('h:i A'),
            'hours_decimal'=>number_format($saved_hours,2,'.',''),
            'computed_hours'=>number_format($hoursDecimal,2,'.',''),
            'salary'=>number_format($saved_salary,2,'.',''),
            'computed_salary'=>number_format($computedSalary,2,'.',''),
            'location_in'=>$locationIn,
            'location_out'=>$locationOut
        ];
    } else {
        $logs[] = [
            'in_id'=>intval($inLog['id']),
            'out_id'=>null,
            'date'=>$timeIn->format('Y-m-d'),
            'time_in'=>$timeIn->format('h:i A'),
            'time_out'=>'N/A',
            'hours_decimal'=>number_format($inLog['working_hours']??0,2,'.',''),
            'computed_hours'=>number_format(0,2,'.',''),
            'salary'=>number_format($inLog['salary']??0,2,'.',''),
            'computed_salary'=>number_format(round(($inLog['working_hours']??0)*$selectedSalaryRate,2),2,'.',''),
            'location_in'=>$locationIn,
            'location_out'=>'N/A'
        ];
    }
}

// leftover unmatched time_out
foreach($tempTimeOut as $outLog){
    $timeOut = new DateTime($outLog['timestamp']);
    $logs[]=[
        'in_id'=>null,
        'out_id'=>intval($outLog['id']),
        'date'=>$timeOut->format('Y-m-d'),
        'time_in'=>'N/A',
        'time_out'=>$timeOut->format('h:i A'),
        'hours_decimal'=>'0.00',
        'computed_hours'=>'0.00',
        'salary'=>number_format($outLog['salary']??0,2,'.',''),
        'computed_salary'=>'0.00',
        'location_in'=>'N/A',
        'location_out'=>$outLog['location_name']??'Unknown'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Individual DTR Report</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
<style>
@media print {
    body * { visibility: hidden; }
    #printHeader, #printHeader * { visibility: visible; }
    #dtrTable, #dtrTable * { visibility: visible; }
    #dtrTable { position: absolute; top: 180px; left: 0; width: 100%; border-collapse: collapse; font-size: 12pt; }
    #dtrTable th, #dtrTable td { border: 1px solid #000; padding: 4px 6px; }
    #printHeader { position: absolute; top: 0; left: 0; width: 100%; text-align:center; }
}
</style>
</head>
<body class="bg-gray-100 text-gray-800">
<div class="flex min-h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-gradient-to-br from-[#667eea] to-[#764ba2] text-white shadow-2xl">
  <div class="px-6 py-6 flex items-center space-x-3">
    <img src="../assets/logo.jpg" alt="Logo" class="w-10 h-10 rounded-full" />
    <h2 class="text-2xl font-bold tracking-wide text-white">Monitoring</h2>
  </div>
  <nav class="px-6 py-8 space-y-6">
    <a href="dashboard.php" class="block text-white px-3 py-2 rounded hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600">📊 Dashboard</a>
    <a href="user_management.php" class="block text-white px-3 py-2 rounded hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600">👥 User Management</a>
    <a href="create_group.php" class="block text-white px-3 py-2 rounded hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600">🗂️ Create Group</a>
    <a href="create_task.php" class="block text-white px-3 py-2 rounded hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600">✅ Assign Task</a>
    <a href="dtr_logs.php" class="block text-white px-3 py-2 rounded hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600">📄 DTR Logs</a>
    <a href="individual_reports.php" class="block text-white px-3 py-2 rounded bg-gradient-to-r from-red-500 to-purple-600 shadow-lg">📤 Individual Reports</a>
    <hr class="my-4 border-gray-600" />
    <a href="../includes/logout.php" class="block text-gray-200 hover:text-red-200">🚪 Logout</a>
  </nav>
</aside>

<!-- Main content -->
<div class="flex-1 flex flex-col">
<header class="bg-white shadow px-6 py-4 flex justify-between items-center">
  <h1 class="text-xl font-semibold">Individual DTR Report</h1>
  <div class="text-sm text-gray-600">Hello, <?= htmlspecialchars($_SESSION['username']); ?></div>
</header>

<main class="p-6">
<div class="bg-white rounded-xl shadow-md p-6 overflow-x-auto">

<!-- Filters -->
<form method="GET" class="flex items-center space-x-3 mb-4">
  <div>
    <label>User:</label>
    <select name="user_id" onchange="this.form.submit()">
      <option value="">--Select User--</option>
      <?php foreach($usersList as $u): ?>
        <option value="<?= $u['id'] ?>" <?= ($selectedUserId==$u['id'])?'selected':'' ?>><?= htmlspecialchars($u['username']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label>From:</label>
    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" onchange="this.form.submit()">
  </div>
  <div>
    <label>To:</label>
    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" onchange="this.form.submit()">
  </div>
</form>

<!-- Print & CSV -->
<div class="mb-4 flex justify-end gap-2">
  <button onclick="printDTR()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">🖨️ Print Report</button>
  <button onclick="exportCSV()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">⬇️ Export CSV</button>
</div>

<!-- Print Header -->
<div id="printHeader" class="hidden">
    <h2 class="text-2xl font-bold">DIS COMPANY</h2>
    <p><?= htmlspecialchars($selectedUserPosition) ?></p>
    <p>User: <?= htmlspecialchars($selectedUserName ?: 'All Users') ?></p>
    <hr class="my-2 border-black">
</div>

<!-- DTR Table -->
<table id="dtrTable" class="min-w-full border border-gray-200 rounded-lg">
<thead class="bg-gray-100 text-gray-700">
<tr>
<th>#</th><th>Date</th><th>Time In</th><th>Time Out</th>
<th>Hours (hrs)</th><th>Salary (₱)</th>
<th>Location In</th><th>Location Out</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php
$counter=1; $totalHours=0; $totalSalary=0;
foreach($logs as $row):
$totalHours+=floatval($row['hours_decimal']);
$totalSalary+=floatval($row['salary']);
?>
<tr>
<td><?= $counter++ ?></td>
<td><?= $row['date'] ?></td>
<td><?= $row['time_in'] ?></td>
<td><?= $row['time_out'] ?></td>
<td>
<?php if($row['in_id']): ?>
<input type="number" step="0.01" min="0" id="hours_<?= $row['in_id'] ?>" value="<?= $row['hours_decimal'] ?>"
       class="border px-2 py-1 w-24" oninput="updateSalary(<?= $row['in_id'] ?>)">
<?php else: ?><?= $row['hours_decimal'] ?><?php endif; ?>
</td>
<td><span id="salary_<?= $row['in_id'] ?>"><?= $row['salary'] ?></span></td>
<td><?= htmlspecialchars($row['location_in']) ?></td>
<td><?= htmlspecialchars($row['location_out']) ?></td>
<td>
<?php if($row['in_id']): ?>
<button id="saveBtn_<?= $row['in_id'] ?>" onclick="saveRow(<?= $row['in_id'] ?>)"
class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Save</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="bg-gray-100 font-semibold">
<tr>
<td colspan="4" class="text-right">Total:</td>
<td id="totalHours"><?= number_format($totalHours,2) ?></td>
<td id="totalSalary"><?= number_format($totalSalary,2) ?></td>
<td colspan="3"></td>
</tr>
</tfoot>
</table>
</div>
</main>
</div>
</div>

<script>
function updateSalary(inId){
    const hours = parseFloat(document.getElementById('hours_'+inId).value)||0;
    const rate = <?= $selectedSalaryRate ?>;
    document.getElementById('salary_'+inId).textContent = (hours*rate).toFixed(2);
    updateTotals();
}

function saveRow(inId){
    const hours = parseFloat(document.getElementById('hours_'+inId).value)||0;
    const salary = parseFloat(document.getElementById('salary_'+inId).textContent)||0;
    const btn = document.getElementById('saveBtn_'+inId);
    btn.disabled=true; btn.textContent='Saving...';

    fetch('',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`action=save_row&in_id=${inId}&working_hours=${hours}&salary=${salary}`
    }).then(r=>r.json()).then(data=>{
        if(data.success){ btn.textContent='Saved'; setTimeout(()=>{btn.textContent='Save'; btn.disabled=false;},1000);}
        else { alert('Error: '+data.msg); btn.disabled=false; btn.textContent='Save'; }
    }).catch(err=>{ alert('Error: '+err); btn.disabled=false; btn.textContent='Save'; });
}

function updateTotals(){
    let totalH=0,totalS=0;
    document.querySelectorAll('input[id^="hours_"]').forEach(i=>{totalH+=parseFloat(i.value)||0;});
    document.querySelectorAll('span[id^="salary_"]').forEach(s=>{totalS+=parseFloat(s.textContent)||0;});
    document.getElementById('totalHours').textContent=totalH.toFixed(2);
    document.getElementById('totalSalary').textContent=totalS.toFixed(2);
}

function printDTR(){
    document.getElementById('printHeader').classList.remove('hidden');
    window.print();
    document.getElementById('printHeader').classList.add('hidden');
}

function exportCSV(){
    let csv='Date,Time In,Time Out,Hours,Salary,Location In,Location Out\n';
    document.querySelectorAll('#dtrTable tbody tr').forEach(tr=>{
        const tds=tr.querySelectorAll('td');
        if(tds.length>1){
            csv += [
                tds[1].textContent.trim(),
                tds[2].textContent.trim(),
                tds[3].textContent.trim(),
                tds[4].querySelector('input')?tds[4].querySelector('input').value:tds[4].textContent.trim(),
                tds[5].querySelector('span')?tds[5].querySelector('span').textContent:tds[5].textContent.trim(),
                tds[6].textContent.trim(),
                tds[7].textContent.trim()
            ].join(',')+'\n';
        }
    });
    const blob=new Blob([csv],{type:'text/csv'});
    const a=document.createElement('a');
    a.href=URL.createObjectURL(blob); a.download='dtr_report.csv';
    document.body.appendChild(a); a.click(); a.remove();
}

feather.replace();
</script>
</body>
</html>
