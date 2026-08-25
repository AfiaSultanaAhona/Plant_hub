<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");
mysqli_report(MYSQLI_REPORT_OFF);

$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'AFIA SULTANA';

// Fetch categories dynamically from database
$categories = [];
$cat_query = mysqli_query($conn, "SELECT * FROM category");
if (!$cat_query) {
    $cat_query = mysqli_query($conn, "SELECT * FROM plant_category");
}
if ($cat_query) {
    while ($c = mysqli_fetch_assoc($cat_query)) {
        $categories[] = $c;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to Plant Hub 🌿</title>
    <style>
        body { background-color: #eef7f2; margin: 0; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        
        /* Hero Section */
        .hero-card {
            background: linear-gradient(135deg, #0d3822 0%, #155e3e 100%);
            border-radius: 16px;
            padding: 50px 40px;
            color: white;
            box-shadow: 0 10px 25px rgba(13, 56, 34, 0.2);
            position: relative;
            overflow: hidden;
        }
        .hero-badge {
            background-color: rgba(52, 211, 153, 0.2);
            color: #34d399;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 15px;
            border: 1px solid rgba(52, 211, 153, 0.3);
        }
        .hero-title { font-size: 38px; font-weight: 800; margin: 0 0 12px 0; line-height: 1.2; }
        .hero-subtitle { font-size: 18px; color: #a7f3d0; margin-bottom: 25px; max-width: 600px; line-height: 1.5; }
        .btn-primary {
            background-color: #10b981;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .btn-primary:hover { background-color: #059669; transform: translateY(-2px); }

        /* Feature Cards */
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 30px; }
        .feature-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            text-align: center;
        }
        .feature-icon { font-size: 32px; margin-bottom: 10px; }
        .feature-title { font-weight: 700; font-size: 16px; color: #0d3822; margin-bottom: 6px; }
        .feature-desc { font-size: 13px; color: #6b7280; margin: 0; }

        /* Categories Section */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin: 40px 0 20px 0; }
        .section-title { font-size: 22px; font-weight: 800; color: #0d3822; margin: 0; }
        
        .category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-decoration: none;
            color: #1f2937;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            border: 2px solid transparent;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .category-card:hover { border-color: #10b981; transform: translateY(-3px); }
        .category-title { font-weight: 700; font-size: 18px; margin: 10px 0 5px 0; color: #0d3822; }
        .category-link { color: #10b981; font-weight: 700; font-size: 14px; }
    </style>
</head>
<body>

<?php if (file_exists("header.php")) include("header.php"); ?>

<div class="container">
    <!-- Hero Banner -->
    <div class="hero-card">
        <span class="hero-badge">🌿 WELCOME BACK, <?php echo strtoupper(htmlspecialchars($user_name)); ?>!</span>
        <h1 class="hero-title">Bring Nature Fresh Into Your Living Space</h1>
        <p class="hero-subtitle">Discover our hand-picked collection of premium indoor, outdoor, and flowering plants delivered directly to your doorstep.</p>
        <a href="shop.php" class="btn-primary">Explore All Plants 🛒</a>
    </div>

    <!-- Quick Perks Highlights -->
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🌱</div>
            <div class="feature-title">100% Healthy Plants</div>
            <p class="feature-desc">Nurtured with care by certified nursery specialists.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⭐</div>
            <div class="feature-title">Loyalty Rewards</div>
            <p class="feature-desc">Earn 10 points for every $500 spent on all purchases.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🚚</div>
            <div class="feature-title">Fast Delivery</div>
            <p class="feature-desc">Carefully packaged and shipped safely to your home.</p>
        </div>
    </div>

    <!-- Dynamic Plant Categories -->
    <div class="section-header">
        <h2 class="section-title">Shop by Category</h2>
        <a href="shop.php" style="color: #10b981; text-decoration: none; font-weight: 700;">View All →</a>
    </div>

    <div class="category-grid">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): 
                $c_id = $cat['Category_ID'] ?? $cat['id'] ?? reset($cat);
                $c_name = $cat['Category_name'] ?? $cat['name'] ?? 'Category #'.$c_id;
            ?>
                <a href="shop.php?category=<?php echo $c_id; ?>" class="category-card">
                    <div>
                        <div style="font-size: 36px;">🪴</div>
                        <div class="category-title"><?php echo htmlspecialchars($c_name); ?></div>
                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 15px;">
                            Browse curated <?php echo htmlspecialchars($c_name); ?> for your home.
                        </p>
                    </div>
                    <span class="category-link">Browse <?php echo htmlspecialchars($c_name); ?> →</span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #6b7280;">No categories available right now.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (file_exists("footer.php")) include("footer.php"); ?>

</body>
</html>