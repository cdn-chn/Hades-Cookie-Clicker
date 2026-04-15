<?php
/*
    Author: Caden Chan
    Date: April 2nd, 2026
    Description: leaderboard.php — Saves final game results and displays rankings.
    Receives POST data from the quit form in play.php, inserts the session into
    the database, then fetches the player's lifetime stats and the global top 5
    to display in the Hall of Souls leaderboard screen.
*/

// 1. Database connection
require_once '../connect.php';

// 2. Receive and sanitize POST parameters
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$total_souls = filter_input(INPUT_POST, 'total_souls', FILTER_VALIDATE_INT);
$current_souls = filter_input(INPUT_POST, 'current_souls', FILTER_VALIDATE_INT);
$souls_per_click = filter_input(INPUT_POST, 'souls_per_click', FILTER_VALIDATE_INT) ?: 1;

// Catch the upgrade levels (default to 0 if missing)
$scythe = filter_input(INPUT_POST, 'scythe_level', FILTER_VALIDATE_INT) ?: 0;
$charon = filter_input(INPUT_POST, 'charon_level', FILTER_VALIDATE_INT) ?: 0;
$darkmage = filter_input(INPUT_POST, 'dark_mage_level', FILTER_VALIDATE_INT) ?: 0;
$ravens = filter_input(INPUT_POST, 'ravens_level', FILTER_VALIDATE_INT) ?: 0;

// Achievements are stored as a JSON string, not an integer
$achievements = $_POST['achievements_unlocked'] ?? '[]';

if (!$email) {
    die("Error: No soul data received. Return to the <a href='index.php'>Gate</a>.");
}

// 3. Save: insert all session data into the database
$insertStmt = $pdo->prepare("
    INSERT INTO Results 
    (email, total_souls, current_souls, souls_per_click, scythe_level, charon_level, dark_mage_level, ravens_level, achievements_unlocked) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$insertStmt->execute([$email, $total_souls, $current_souls, $souls_per_click, $scythe, $charon, $darkmage, $ravens, $achievements]);

// 4. Fetch user stats: calculate lifetime totals for the current player
$userStatsStmt = $pdo->prepare("
    SELECT 
        COUNT(id)          AS sessions, 
        SUM(total_souls)   AS lifetime_total, 
        MAX(current_souls) AS record_bank 
    FROM Results 
    WHERE email = ?
");
$userStatsStmt->execute([$email]);
$userStats = $userStatsStmt->fetch();

// 5. Fetch global top 5: rank players by their highest cumulative total souls
$top5Stmt = $pdo->query("
    SELECT 
        email, 
        SUM(total_souls) AS total_score
    FROM Results 
    GROUP BY email 
    ORDER BY total_score DESC 
    LIMIT 5
");
$topPlayers = $top5Stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Soul Collector - Leaderboard</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/indexPage.css" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet" />
    <style>
        .leaderboard-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            max-width: 800px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .leaderboard-card {
            background: rgba(19, 36, 34, 0.8);
            border: 1px solid var(--soul-light);
            padding: 20px;
            border-radius: 10px;
        }

        .stat-line {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            border-bottom: 1px solid var(--border);
            padding-bottom: 5px;
        }

        .rank-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .gold-text {
            color: var(--gold);
            font-weight: bold;
        }
    </style>
</head>

<body class="login-body">
    <main class="login-main">
        <div class="leaderboard-container">

            <h1 class="login-title" style="font-size: 2.5rem;">The Hall of Souls</h1>

            <div class="stats-grid">
                <!-- Player's personal lifetime stats -->
                <div class="leaderboard-card">
                    <h2 class="login-title">Your Legacy</h2>
                    <div class="stat-line"><span>Sessions:</span> <span><?php echo $userStats['sessions']; ?></span>
                    </div>
                    <div class="stat-line"><span>Lifetime Souls:</span> <span
                            class="gold-text"><?php echo number_format($userStats['lifetime_total']); ?></span></div>
                    <div class="stat-line"><span>Record Bank:</span>
                        <span><?php echo number_format($userStats['record_bank']); ?></span></div>
                </div>

                <!-- Global top 5 players ranked by lifetime total souls -->
                <div class="leaderboard-card">
                    <h2 class="login-title">Top 5 Harvesters</h2>
                    <?php
                    $rank = 1;
                    foreach ($topPlayers as $row): ?>
                        <div class="rank-item">
                            <span><?php echo $rank++; ?>. <?php echo htmlspecialchars($row['email']); ?></span>
                            <span class="gold-text"><?php echo number_format($row['total_score']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <a href="play.php?email=<?php echo urlencode($email); ?>" class="enter-button"
                    style="text-decoration:none; text-align:center;">Re-Enter Underworld</a>
                <a href="index.php" class="enter-button"
                    style="text-decoration:none; text-align:center; background:#6e1c24;">Abandon Souls</a>
            </div>

        </div>
    </main>
</body>

</html>