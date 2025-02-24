<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <title>Login</title>
</head>
<body>
  <form action="../backend/auth.php" method="POST">
      <h2>Login</h2>
      <label for="username">Username:</label>
      <input type="text" name="username" required>
      <label for="password">Password:</label>
      <input type="password" name="password" required>
      <button type="submit">Login</button>
  </form>
</body>
</html>
