<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">

  <div class="w-full max-w-md bg-white bg-opacity-90 shadow-xl rounded-2xl p-6 sm:p-8">
    <div class="flex justify-center mb-4 sm:mb-6">
      <img src="assets/logo.jpg" alt="Logo" class="w-16 h-16 sm:w-20 sm:h-20 rounded-full shadow-lg">
    </div>
    <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-800 mb-4 sm:mb-6">Login</h2>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm text-center">
        Invalid username or password.
      </div>
    <?php endif; ?>

    <form method="POST" action="includes/auth.php">
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1" for="username">Username</label>
        <input type="text" id="username" name="username" required
               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Password</label>
        <input type="password" id="password" name="password" required
               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <button type="submit"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition">
        Login
      </button>
    </form>
  </div>

</body>
</html>
