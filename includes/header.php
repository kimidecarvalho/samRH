<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        // ...existing code...
        <div class="user-box">
            <div class="dropdown">
                <button class="dropdown-toggle" type="button">
                    <span><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
        // ...existing code...
    </div>
    <script src="js/script.js"></script>
</body>
</html>
