<?php
session_start();
require_once '../includes/config.php';

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$timeInToday = $pdo->prepare("SELECT COUNT(*) FROM dtr_logs WHERE action = 'time_in' AND DATE(timestamp) = CURDATE()");
$timeInToday->execute();
$timeInToday = $timeInToday->fetchColumn();
$timeOutToday = $pdo->prepare("SELECT COUNT(*) FROM dtr_logs WHERE action = 'time_out' AND DATE(timestamp) = CURDATE()");
$timeOutToday->execute();
$timeOutToday = $timeOutToday->fetchColumn();
$outsideGeoFence = $pdo->query("SELECT COUNT(*) FROM dtr_logs WHERE outside_geofence = 1 AND DATE(timestamp) = CURDATE()")->fetchColumn();
$usersWithLocation = $pdo->query("SELECT id, username, latitude, longitude FROM users WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
$taggedLocations = $pdo->query("SELECT id, name, latitude, longitude, radius FROM tagged_locations")->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DTR Monitoring Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/feather-icons"></script>

  <!-- Leaflet & Geocoder CSS/JS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
  <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

  <!-- ✅ MAP VISIBILITY FIX -->
  <style>
    #map {
      height: 100%;
      z-index: 0;
    }
    .leaflet-control-zoom,
    .leaflet-control-geocoder {
      z-index: 1000;
    }
    .leaflet-top.leaflet-left {
      display: flex;
      gap: 10px;
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-800">

<div class="flex min-h-screen">

  <!-- Sidebar -->
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<!-- Sidebar -->
<aside class="w-64 bg-gradient-to-br from-[#667eea] to-[#764ba2] text-white shadow-2xl">
  <div class="px-6 py-6 shadow-md border-b border-gray-700 flex items-center space-x-3">
    <img src="../assets/logo.jpg" alt="Logo" class="w-10 h-10 rounded-full" />
    <h2 class="text-2xl font-bold tracking-wide text-white">Monitoring</h2>
  </div>

  <nav class="px-6 py-8 space-y-6">
    <!-- Dashboard -->
    <a href="dashboard.php"
      class="block text-base font-medium px-3 py-2 rounded transition duration-200
      <?php echo $current_page === 'dashboard.php' 
        ? 'bg-gradient-to-r from-red-500 to-purple-600 text-white shadow-lg' 
        : 'text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600'; ?>">
      📊 Dashboard
    </a>

    <!-- User Management -->
    <a href="user_management.php"
      class="block text-base font-medium px-3 py-2 rounded transition duration-200
      <?php echo $current_page === 'user_management.php' 
        ? 'bg-gradient-to-r from-red-500 to-purple-600 text-white shadow-lg' 
        : 'text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600'; ?>">
      👥 User Management
    </a>

      <a href="create_group.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">🗂️ Create Group</a>
      <a href="create_task.php" class="block text-base font-medium text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600 px-3 py-2 rounded">✅ Assign Task</a>

    <!-- DTR Logs -->
    <a href="dtr_logs.php"
      class="block text-base font-medium px-3 py-2 rounded transition duration-200
      <?php echo $current_page === 'dtr_logs.php' 
        ? 'bg-gradient-to-r from-red-500 to-purple-600 text-white shadow-lg' 
        : 'text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600'; ?>">
      📄 DTR Logs
    </a>

    <!-- Reports -->
    <a href="individual_reports.php"
      class="block text-base font-medium px-3 py-2 rounded transition duration-200
      <?php echo $current_page === 'reports.php' 
        ? 'bg-gradient-to-r from-red-500 to-purple-600 text-white shadow-lg' 
        : 'text-white hover:bg-gradient-to-r hover:from-red-500 hover:to-purple-600'; ?>">
      📤 Individual Reports
    </a>

    <hr class="my-4 border-gray-600" />

    <!-- Logout -->
    <a href="../includes/logout.php"
      class="block text-base font-semibold text-gray-200 hover:text-red-200 transition duration-500">
      🚪 Logout
    </a>
  </nav>
</aside>


  <!-- Main Content -->
  <div class="flex-1 flex flex-col">

    <!-- Top Navbar -->
    <header class="bg-white shadow px-6 py-4 flex justify-between items-center">
      <h1 class="text-xl font-semibold">Admin Dashboard</h1>
      <div class="text-sm text-gray-600">Hello, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
    </header>

    <!-- Dashboard Content -->
    <main class="p-6 space-y-6">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-xl">
          <p class="text-sm text-gray-500 mb-2">Total Users</p>
          <h3 class="text-3xl font-bold text-indigo-600"><?php echo $totalUsers; ?></h3>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-xl">
          <p class="text-sm text-gray-500 mb-2">Time-In Today</p>
          <h3 class="text-3xl font-bold text-green-500"><?php echo $timeInToday; ?></h3>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-xl">
          <p class="text-sm text-gray-500 mb-2">Time-Out Today</p>
          <h3 class="text-3xl font-bold text-yellow-500"><?php echo $timeOutToday; ?></h3>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-xl">
          <p class="text-sm text-gray-500 mb-2">Outside Geo-Fence</p>
          <h3 class="text-3xl font-bold text-red-500"><?php echo $outsideGeoFence; ?></h3>
        </div>
      </div>

      <!-- Map -->
      <div class="bg-white mt-6 p-6 rounded-xl shadow-md">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold">User Live Location (OpenStreetMap)</h2>
        </div>
        <div class="relative w-full h-96 bg-gray-200 rounded-lg">
          <div id="map" class="absolute inset-0 rounded-lg"></div>
        </div>
      </div>
      <!-- ✅ Tagged Locations Section -->
<div class="mt-6 bg-white p-6 rounded-xl shadow-md">
  <h3 class="text-lg font-semibold mb-4">📍 Tagged Locations</h3>
  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 rounded-lg">
      <thead class="bg-gray-100 text-gray-700">
        <tr>
          <th class="px-4 py-3 border-b text-left">Name</th>
          <th class="px-4 py-3 border-b text-left">Latitude</th>
          <th class="px-4 py-3 border-b text-left">Longitude</th>
          <th class="px-4 py-3 border-b text-left">Radius (m)</th>
        </tr>
      </thead>
      <tbody>
       <?php if (!empty($taggedLocations)): ?>
    <?php foreach ($taggedLocations as $loc): ?>
      <tr class="hover:bg-gray-50">
        <td class="px-4 py-3 border-b"><?php echo htmlspecialchars($loc['name']); ?></td>
        <td class="px-4 py-3 border-b"><?php echo htmlspecialchars($loc['latitude']); ?></td>
        <td class="px-4 py-3 border-b"><?php echo htmlspecialchars($loc['longitude']); ?></td>
        <td class="px-4 py-3 border-b"><?php echo htmlspecialchars($loc['radius']); ?> m</td>
        <td class="px-4 py-3 border-b text-center space-x-2">
          <!-- Edit button -->
          <!-- Edit button (open modal instead of redirect) -->
<button 
  onclick="openModal(
    <?php echo $loc['id']; ?>, 
    '<?php echo htmlspecialchars($loc['name'], ENT_QUOTES); ?>', 
    '<?php echo $loc['latitude']; ?>', 
    '<?php echo $loc['longitude']; ?>', 
    '<?php echo $loc['radius']; ?>'
  )"
  class="inline-block px-3 py-1 text-sm text-white rounded bg-gradient-to-r from-green-400 to-green-600 hover:from-green-500 hover:to-green-700">
  Edit
</button>

<!-- Delete button (still using confirm) -->
<a href="delete_location.php?id=<?php echo $loc['id']; ?>" 
   onclick="return confirm('Are you sure you want to delete this location?');"
   class="inline-block px-3 py-1 text-sm text-white rounded bg-gradient-to-r from-red-400 to-red-600 hover:from-red-500 hover:to-red-700">
   Delete
</a>

        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr>
      <td colspan="5" class="px-4 py-3 text-center text-gray-500">No tagged locations yet.</td>
    </tr>
  <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
    </main>
  </div>
</div>

<!-- ✅ Edit Modal (Dashboard) -->
<div id="editModal" class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 z-50">
  <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-lg">
    <h2 class="text-xl font-semibold mb-4">✏️ Edit Location</h2>
    <form method="POST" action="update_location.php">
      <input type="hidden" name="id" id="edit_id">

      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" id="edit_name" class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
      </div>

      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700">Latitude</label>
        <input type="text" name="latitude" id="edit_latitude" class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
      </div>

      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700">Longitude</label>
        <input type="text" name="longitude" id="edit_longitude" class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Radius (m)</label>
        <input type="number" name="radius" id="edit_radius" class="w-full px-3 py-2 border rounded-lg focus:ring focus:ring-indigo-300" required>
      </div>

      <div class="flex justify-end space-x-3">
        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id, name, latitude, longitude, radius) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_latitude').value = latitude;
    document.getElementById('edit_longitude').value = longitude;
    document.getElementById('edit_radius').value = radius;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
  }

  function closeModal() {
    document.getElementById('editModal').classList.remove('flex');
    document.getElementById('editModal').classList.add('hidden');
  }
</script>


<script>
let map, userMarker;
const userMarkers = {};
const users = <?php echo json_encode($usersWithLocation); ?>;
const taggedLocations = <?php echo json_encode($taggedLocations); ?>;

document.addEventListener("DOMContentLoaded", function () {
  const defaultCoords = [6.9214, 122.0790];
  map = L.map('map').setView(defaultCoords, 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Add search control with explicit provider
L.Control.geocoder({
  geocoder: L.Control.Geocoder.nominatim(), // force Nominatim API
  defaultMarkGeocode: false
})
.on('markgeocode', function(e) {
  const center = e.geocode.center;

  if (userMarker) {
    userMarker.setLatLng(center).setPopupContent(e.geocode.name).openPopup();
  } else {
    userMarker = L.marker(center).addTo(map).bindPopup(e.geocode.name).openPopup();
  }

  map.setView(center, 14);
})
.addTo(map);

  // Show all users' locations
  users.forEach(user => {
    if (user.latitude && user.longitude) {
      const marker = L.marker([user.latitude, user.longitude]).addTo(map)
        .bindPopup(`<strong>${user.username}</strong>`);
      userMarkers[user.id] = marker;
    }
  });

  if (Object.keys(userMarkers).length > 0) {
    const group = L.featureGroup(Object.values(userMarkers));
    map.fitBounds(group.getBounds());
  }

  // Draw saved tagged locations from DB
  taggedLocations.forEach(loc => {
    L.circle([loc.latitude, loc.longitude], {
      radius: loc.radius,
      color: 'blue',
      fillColor: '#30f',
      fillOpacity: 0.2
    }).addTo(map).bindPopup(`<strong>${loc.name}</strong><br>Radius: ${loc.radius}m`);
  });

  // Tag Location Control
  L.Control.TagLocation = L.Control.extend({
    onAdd: function () {
      const btn = L.DomUtil.create('button', 'leaflet-bar');
      btn.innerHTML = '📍';
      btn.title = 'Tag Location';
      btn.style.width = '34px';
      btn.style.height = '34px';
      btn.style.fontSize = '18px';
      btn.style.background = '#fff';
      btn.style.cursor = 'pointer';
      btn.style.border = 'none';

      L.DomEvent.on(btn, 'click', function (e) {
        L.DomEvent.stopPropagation(e);
        alert("Click on the map to set a tagged location.");
        map.once('click', function(ev) {
          const lat = ev.latlng.lat;
          const lng = ev.latlng.lng;
          const name = prompt("Enter location name:");
          if (!name) return;
          const radius = prompt("Enter radius in meters (default 50):", "50");

          fetch('save_tagged_location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, lat, lng, radius: parseInt(radius) })
          })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'success') {
              L.circle([lat, lng], {
                radius: parseInt(radius) || 50,
                color: 'blue',
                fillColor: '#30f',
                fillOpacity: 0.2
              }).addTo(map).bindPopup(`<strong>${name}</strong><br>Radius: ${radius}m`);
              alert("Location tagged successfully!");
            } else {
              alert("Failed to save location.");
            }
          });
        });
      });

      return btn;
    },
    onRemove: function () {}
  });

  L.control.tagLocation = function (opts) {
    return new L.Control.TagLocation(opts);
  };
  L.control.tagLocation({ position: 'topleft' }).addTo(map);

  // Live geolocation
  if (navigator.geolocation) {
    navigator.geolocation.watchPosition((pos) => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      const coords = [lat, lng];

      if (!userMarker) {
        userMarker = L.marker(coords).addTo(map).bindPopup("You are here").openPopup();
      } else {
        userMarker.setLatLng(coords).update();
      }

      fetch('update_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ lat, lng })
      });
    }, (err) => {
      console.warn('Geolocation error:', err);
    }, {
      enableHighAccuracy: true,
      timeout: 2000,
      maximumAge: 0
    });
  } else {
    alert("Geolocation not supported by your browser.");
  }

  // Custom Fullscreen Toggle Control
  L.Control.FullscreenToggle = L.Control.extend({
    onAdd: function () {
      const btn = L.DomUtil.create('button', 'leaflet-bar');
      btn.id = 'fullscreenBtn';
      btn.title = 'Toggle Fullscreen';
      btn.innerHTML = '⛶';
      btn.style.width = '34px';
      btn.style.height = '34px';
      btn.style.fontSize = '18px';
      btn.style.cursor = 'pointer';
      btn.style.background = '#fff';
      btn.style.border = 'none';

      L.DomEvent.on(btn, 'click', function (e) {
        L.DomEvent.stopPropagation(e);
        const mapDiv = document.getElementById("map");
        if (!document.fullscreenElement) {
          mapDiv.requestFullscreen();
          btn.innerHTML = '🡼';
          btn.title = 'Exit Fullscreen';
        } else {
          document.exitFullscreen();
          btn.innerHTML = '⛶';
          btn.title = 'Toggle Fullscreen';
        }
      });

      return btn;
    },
    onRemove: function () {}
  });

  L.control.fullscreenToggle = function (opts) {
    return new L.Control.FullscreenToggle(opts);
  };
  L.control.fullscreenToggle({ position: 'topleft' }).addTo(map);

});

feather.replace();
</script>
</body>
</html>

