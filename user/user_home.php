<?php
session_start();
require_once '../includes/config.php'; // expects $pdo (PDO) connection

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

$success = '';
$error = '';

// -----------------------
// Fetch current user info from DB
// -----------------------
$stmt = $pdo->prepare("SELECT id, username, phone, role, position, salary_rate FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    $currentUser = [
        'id' => $user_id,
        'username' => $username,
        'phone' => '',
        'role' => 'user',
        'position' => '',
        'salary_rate' => '0.00'
    ];
}

// -----------------------
// Haversine helper
// -----------------------
function haversineMeters($lat1, $lng1, $lat2, $lng2) {
    $R = 6371000.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)**2;
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

// -----------------------
// Fetch user's groups, tasks, and locations
// -----------------------
$stmt = $pdo->prepare('SELECT group_id FROM group_users WHERE user_id = ?');
$stmt->execute([$user_id]);
$userGroups = $stmt->fetchAll(PDO::FETCH_COLUMN);

$groupsData = [];
$locations = [];

if (!empty($userGroups)) {
    $inQuery = implode(',', array_fill(0, count($userGroups), '?'));

    // Groups
    $stmt = $pdo->prepare("SELECT id, name FROM `groups` WHERE id IN ($inQuery)");
    $stmt->execute($userGroups);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tasks
    $stmt = $pdo->prepare("SELECT id, group_id, task_name, description, due_date FROM tasks WHERE group_id IN ($inQuery)");
    $stmt->execute($userGroups);
    $tasksRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tasksByGroup = [];
    foreach ($tasksRaw as $t) $tasksByGroup[$t['group_id']][] = $t;

    // Locations
    $stmt = $pdo->prepare("SELECT tl.* FROM tagged_locations tl JOIN group_locations gl ON tl.id = gl.location_id WHERE gl.group_id IN ($inQuery)");
    $stmt->execute($userGroups);
    $locationsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($locationsRaw as $loc) {
        $loc['radius'] = isset($loc['radius']) && is_numeric($loc['radius']) ? (float)$loc['radius'] : 50.0;
        $locations[] = $loc;
    }

    foreach ($groups as $g) {
        $groupsData[] = [
            'id' => $g['id'],
            'name' => $g['name'],
            'tasks' => $tasksByGroup[$g['id']] ?? []
        ];
    }
}

$hasTask = false;
foreach ($groupsData as $g) if (!empty($g['tasks'])) { $hasTask = true; break; }
$hasGroupLocation = !empty($locations);

// -----------------------
// Handle Time In/Out POST
// -----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$hasGroupLocation) $error = 'You cannot Time In/Out because your groups have no assigned locations.';
    elseif (!$hasTask) $error = 'You cannot Time In/Out because you have no assigned tasks.';
    else {
        $action = $_POST['action'] ?? null;
        $lat = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $lng = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;

        $actionValue = null;
        if ($action === 'Time In') $actionValue = 'time_in';
        if ($action === 'Time Out') $actionValue = 'time_out';

        if (!$actionValue || $lat === null || $lng === null) {
            $error = 'Invalid request. Make sure your location is enabled and the action is correct.';
        } else {
            $locationId = null;
            foreach ($locations as $loc) {
                $dist = haversineMeters($lat, $lng, (float)$loc['latitude'], (float)$loc['longitude']);
                if ($dist <= (float)$loc['radius']) { $locationId = $loc['id']; break; }
            }

            if (!$locationId) {
                $error = 'You cannot Time In/Out outside the allowed area.';
            } else {
                try {
                    $stmt = $pdo->prepare('INSERT INTO dtr_logs (user_id, action, location_id, timestamp) VALUES (?, ?, ?, NOW())');
                    $stmt->execute([$user_id, $actionValue, $locationId]);
                    $success = $action . ' recorded successfully!';
                } catch (PDOException $ex) {
                    $error = 'Database error: ' . htmlspecialchars($ex->getMessage());
                }
            }
        }
    }
}

// -----------------------
// Fetch today's logs
// -----------------------
$stmt = $pdo->prepare("
    SELECT dl.*, tl.name AS location_name
    FROM dtr_logs dl
    LEFT JOIN tagged_locations tl ON dl.location_id = tl.id
    WHERE dl.user_id = ? AND DATE(dl.timestamp) = CURDATE()
    ORDER BY dl.timestamp DESC
");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jsLocations = json_encode($locations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

// -----------------------
// Fetch all history logs (for dropdown modal)
// -----------------------
$stmt = $pdo->prepare("
    SELECT dl.*, tl.name AS location_name
    FROM dtr_logs dl
    LEFT JOIN tagged_locations tl ON dl.location_id = tl.id
    WHERE dl.user_id = ?
    ORDER BY dl.timestamp DESC
");
$stmt->execute([$user_id]);
$historyLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>User Dashboard - DTR Monitoring</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<style>
header { position: relative; z-index: 99999 !important; }
#userMenuBtn { position: relative; z-index: 999999 !important; }
#userMenu { position: absolute !important; z-index: 999999 !important; }
.leaflet-container { z-index: 1 !important; }
.modal { display: none; }
</style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

<header class="bg-white shadow p-4 flex justify-between items-center relative">
  <h1 class="text-lg font-semibold text-gray-800">DTR Monitoring</h1>

  <div class="relative inline-block text-left">
    <button id="userMenuBtn" class="bg-gray-200 px-3 py-1 rounded hover:bg-gray-300 flex items-center gap-2 focus:outline-none">
      <?= htmlspecialchars($username) ?>
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <div id="userMenu" class="hidden absolute right-0 mt-2 w-56 bg-white border rounded shadow-lg">
      <a href="#" id="viewUserInfo" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">User Info</a>
      <a href="#" id="viewHistoryLogs" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">History Logs</a>
      <a href="../includes/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-100">Logout</a>
    </div>
  </div>
</header>

<?php if ($success): ?><div id="success-msg" class="bg-green-100 text-green-700 p-2 m-4 rounded"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div id="error-msg" class="bg-red-100 text-red-700 p-2 m-4 rounded"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<main class="flex-1 p-6 space-y-6">
  <!-- Action Buttons -->
  <div id="actionButtons" class="flex justify-center gap-4 hidden">
    <button id="timeInBtn" class="bg-green-500 text-white px-6 py-3 rounded-lg shadow hover:bg-green-600">Time In</button>
    <button id="timeOutBtn" class="bg-blue-500 text-white px-6 py-3 rounded-lg shadow hover:bg-blue-600">Time Out</button>
  </div>

  <!-- Map -->
  <div class="bg-white shadow rounded-lg p-4">
    <h2 class="text-md font-semibold text-gray-700 mb-2">Tagged Locations for Your Groups</h2>
    <div id="map" class="w-full h-72 bg-gray-200 rounded-lg flex items-center justify-center text-gray-500">Map Placeholder</div>
  </div>

  <!-- Groups & Tasks -->
  <div class="bg-white shadow rounded-lg p-4">
    <h2 class="text-md font-semibold text-gray-700 mb-2">My Groups & Assigned Tasks</h2>
    <?php if (!empty($groupsData)): ?>
      <?php foreach ($groupsData as $group): ?>
        <div class="mb-4 border border-gray-200 rounded p-3">
          <h3 class="text-gray-800 font-semibold mb-2"><?= htmlspecialchars($group['name']) ?></h3>
          <?php if (!empty($group['tasks'])): ?>
            <ul class="list-disc pl-5 text-gray-700">
              <?php foreach ($group['tasks'] as $task): ?>
                <li class="mb-2">
                  <span class="font-medium"><?= htmlspecialchars($task['task_name']) ?></span>
                  <?php if (!empty($task['due_date'])): ?> - Due: <?= date('Y-m-d', strtotime($task['due_date'])) ?><?php endif; ?>
                  <p class="text-gray-600 text-sm"><?= htmlspecialchars($task['description']) ?></p>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="text-gray-500 text-sm">No tasks assigned yet.</p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-gray-500 text-sm">You are not assigned to any group.</p>
    <?php endif; ?>
  </div>

  <form id="dtrForm" method="POST" style="display:none;">
    <input type="hidden" name="action" id="actionInput">
    <input type="hidden" name="latitude" id="latInput">
    <input type="hidden" name="longitude" id="lngInput">
  </form>

  <!-- Today's Logs -->
  <div class="bg-white shadow rounded-lg p-4">
    <h2 class="text-md font-semibold text-gray-700 mb-2">Today's Logs</h2>
    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-gray-100 text-gray-700 text-left">
          <th class="p-2 border">Action</th>
          <th class="p-2 border">Time</th>
          <th class="p-2 border">Location</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($logs): foreach ($logs as $log): ?>
        <tr class="hover:bg-gray-50">
          <td class="p-2 border"><?= htmlspecialchars($log['action']) ?></td>
          <td class="p-2 border"><?= date('H:i:s', strtotime($log['timestamp'])) ?></td>
          <td class="p-2 border"><?= htmlspecialchars($log['location_name'] ?? 'Unknown') ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="3" class="text-center p-2">No logs for today.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- =================== USER INFO MODAL =================== -->
<div id="userInfoModal" class="fixed inset-0 bg-black bg-opacity-40 hidden z-50 flex justify-center items-center overflow-auto">
  <div class="bg-white p-6 rounded shadow max-w-md w-full relative z-50">
    <h2 class="text-xl font-semibold mb-4">User Information</h2>
    <div class="space-y-3">
      <div>
        <label class="text-gray-700 font-medium">Username</label>
        <p class="p-2 bg-gray-100 rounded"><?= htmlspecialchars($currentUser['username']) ?></p>
      </div>
      <div>
        <label class="text-gray-700 font-medium">Phone</label>
        <p class="p-2 bg-gray-100 rounded"><?= htmlspecialchars($currentUser['phone']) ?></p>
      </div>
      <div>
        <label class="text-gray-700 font-medium">Position</label>
        <p class="p-2 bg-gray-100 rounded"><?= htmlspecialchars($currentUser['position']) ?></p>
      </div>
      <div>
        <label class="text-gray-700 font-medium">Role</label>
        <p class="p-2 bg-gray-100 rounded"><?= htmlspecialchars($currentUser['role']) ?></p>
      </div>
      <div>
        <label class="text-gray-700 font-medium">Salary Rate</label>
        <p class="p-2 bg-gray-100 rounded"><?= number_format($currentUser['salary_rate'],2) ?></p>
      </div>
    </div>
    <div class="text-right mt-4">
      <button id="closeUserInfo" class="bg-gray-200 px-4 py-2 rounded">Close</button>
    </div>
  </div>
</div>

<!-- =================== HISTORY LOGS MODAL =================== -->
<div id="historyLogsModal" class="fixed inset-0 bg-black bg-opacity-40 hidden z-50 flex justify-center items-center overflow-auto">
  <div class="bg-white p-6 rounded shadow max-w-4xl w-full relative z-50">
    <h2 class="text-xl font-semibold mb-4">History Logs</h2>
    <div class="overflow-x-auto max-h-96">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100">
            <th class="p-2 border">Action</th>
            <th class="p-2 border">Time</th>
            <th class="p-2 border">Location</th>
            <th class="p-2 border">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($historyLogs as $h): ?>
          <tr>
            <td class="p-2 border"><?= htmlspecialchars($h['action']) ?></td>
            <td class="p-2 border"><?= date('H:i:s', strtotime($h['timestamp'])) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($h['location_name'] ?? 'Unknown') ?></td>
            <td class="p-2 border"><?= date('Y-m-d', strtotime($h['timestamp'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="text-right mt-4">
      <button id="closeHistoryLogs" class="bg-gray-200 px-4 py-2 rounded">Close</button>
    </div>
  </div>
</div>

<script>
const locations = <?= $jsLocations ?>;
let hasTask = <?= $hasTask ? 'true':'false' ?>;

// Haversine
function getDistance(lat1,lng1,lat2,lng2){const R=6371000,dLat=(lat2-lat1)*Math.PI/180,dLng=(lng2-lng1)*Math.PI/180,a=Math.sin(dLat/2)**2+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2,c=2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));return R*c;}
function isInsideTaggedLocation(userLat,userLng){for(let loc of locations){const distance=getDistance(userLat,userLng,parseFloat(loc.latitude),parseFloat(loc.longitude));if(distance<=(loc.radius||50))return true;}return false;}
function updateButtonVisibility(hasTask,insideRadius){const btnWrapper=document.getElementById('actionButtons');if(hasTask&&insideRadius)btnWrapper.classList.remove('hidden');else btnWrapper.classList.add('hidden');}
function submitDTR(action){if(!navigator.geolocation){alert('Geolocation not supported.');return;}navigator.geolocation.getCurrentPosition(pos=>{const lat=pos.coords.latitude,lng=pos.coords.longitude,inside=isInsideTaggedLocation(lat,lng);updateButtonVisibility(hasTask,inside);if(!inside){alert('You cannot Time In/Out outside the allowed area!');return;}document.getElementById('actionInput').value=action;document.getElementById('latInput').value=lat;document.getElementById('lngInput').value=lng;document.getElementById('dtrForm').submit();},err=>{alert('Unable to get location: '+err.message);},{enableHighAccuracy:true,timeout:10000});}

document.getElementById('timeInBtn').addEventListener('click',()=>submitDTR('Time In'));
document.getElementById('timeOutBtn').addEventListener('click',()=>submitDTR('Time Out'));

// Leaflet Map
const map=L.map('map').setView(locations.length?[parseFloat(locations[0].latitude),parseFloat(locations[0].longitude)]:[6.9214,122.0790],14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap contributors'}).addTo(map);
const markers=[];locations.forEach(loc=>{const lat=parseFloat(loc.latitude),lng=parseFloat(loc.longitude);if(!isNaN(lat)&&!isNaN(lng)){markers.push(L.marker([lat,lng]).addTo(map).bindPopup(loc.name||'Location'));L.circle([lat,lng],{radius:loc.radius||50,color:'blue',fillOpacity:0.1}).addTo(map);}});if(markers.length){const group=new L.featureGroup(markers);map.fitBounds(group.getBounds().pad(0.2));}
map.locate({setView:false,maxZoom:16});
map.on('locationfound',e=>{L.marker(e.latlng).addTo(map).bindPopup('You are here').openPopup();L.circle(e.latlng,{radius:e.accuracy}).addTo(map);const inside=isInsideTaggedLocation(e.latlng.lat,e.latlng.lng);updateButtonVisibility(hasTask,inside);});

// Messages timeout
const successMsg=document.getElementById('success-msg'); if(successMsg)setTimeout(()=>successMsg.remove(),2500);
const errorMsg=document.getElementById('error-msg'); if(errorMsg)setTimeout(()=>errorMsg.remove(),4500);

// Dropdown
const userMenuBtn=document.getElementById("userMenuBtn"),userMenu=document.getElementById("userMenu");
userMenuBtn.addEventListener("click",()=>userMenu.classList.toggle("hidden"));
document.addEventListener("click",e=>{if(!userMenuBtn.contains(e.target)&&!userMenu.contains(e.target))userMenu.classList.add("hidden");});

// User Info Modal
const viewUserInfoBtn=document.getElementById("viewUserInfo"),userInfoModal=document.getElementById("userInfoModal"),closeUserInfo=document.getElementById("closeUserInfo");
viewUserInfoBtn.addEventListener("click",()=>{userInfoModal.classList.remove("hidden");userMenu.classList.add("hidden");});
closeUserInfo.addEventListener("click",()=>userInfoModal.classList.add("hidden"));
userInfoModal.addEventListener("click",e=>{if(e.target===userInfoModal)userInfoModal.classList.add("hidden");});

// History Logs Modal
const viewHistoryLogsBtn=document.getElementById("viewHistoryLogs"),historyLogsModal=document.getElementById("historyLogsModal"),closeHistoryLogs=document.getElementById("closeHistoryLogs");
viewHistoryLogsBtn.addEventListener("click",()=>{historyLogsModal.classList.remove("hidden");userMenu.classList.add("hidden");});
closeHistoryLogs.addEventListener("click",()=>historyLogsModal.classList.add("hidden"));
historyLogsModal.addEventListener("click",e=>{if(e.target===historyLogsModal)historyLogsModal.classList.add("hidden");});
</script>

</body>
</html>
