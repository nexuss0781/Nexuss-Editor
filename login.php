<?php
require_once 'config.php';
session_start();

$error = "";

// Redirect if already logged in
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header("Location: index.php");
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    // Verify password against hash in config.php
    if (password_verify($password, AUTH_PASSWORD_HASH)) {
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid password. Access denied.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950">
<head>
    <meta charset="UTF-8">
    <title>Login | <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <i class="fa-solid fa-microchip text-indigo-500 text-5xl mb-4"></i>
            <h1 class="text-3xl font-bold text-white tracking-tight"><?php echo APP_NAME; ?></h1>
            <p class="text-zinc-500 mt-2 text-sm uppercase tracking-widest font-semibold">Engine Access Terminal</p>
        </div>

        <form action="login.php" method="POST" class="bg-zinc-900 border border-zinc-800 p-8 rounded-2xl shadow-2xl">
            <?php if (isset($_GET['reason']) && $_GET['reason'] === 'session_expired'): ?>
                <div class="mb-6 p-3 bg-amber-500/10 border border-amber-500/50 text-amber-500 text-xs rounded text-center">
                    Session expired due to 30 mins of inactivity.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-6 p-3 bg-red-500/10 border border-red-500/50 text-red-500 text-xs rounded text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase mb-2">Security Password</label>
                    <input type="password" name="password" required autofocus
                           class="w-full bg-zinc-950 border border-zinc-800 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                           placeholder="••••••••••••">
                </div>
                
                <button type="submit" 
                        class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-lg transition-all shadow-lg shadow-indigo-500/20">
                    Authenticate
                </button>
            </div>
        </form>

        <p class="text-center text-zinc-600 text-[10px] mt-8 uppercase tracking-widest">
            Protected Environment &copy; <?php echo date('Y'); ?>
        </p>
    </div>
</body>
</html>
