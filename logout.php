<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging Out...</title>
</head>
<body>
<script>
    // Remove session data from LocalStorage
    localStorage.removeItem("plant_hub_user");
    // Redirect to home page
    window.location.href = "index.php";
</script>
</body>
</html>