<?php
/*
    Author: Caden Chan
    Date: April 2nd, 2026
    Description: login.php — Processes the login/registration form submitted from index.php.
    Connects to the database to verify or create the player record using their email and
    birth date. Sets a contextual title, message, and redirect link depending on whether
    the player is new, returning with a matching birth date, or using a mismatched birth date.
*/

include '../connect.php';

// 2. Securely receive and filter HTTP POST parameters
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$birth_date = filter_input(INPUT_POST, 'birth_date', FILTER_SANITIZE_STRING);

// Initialize output variables for the HTML response card
$title = "";
$message = "";
$link_url = "";
$link_text = "";

// 3. Handle missing or invalid inputs gracefully
if (!$email || !$birth_date) {
    $title = "Access Denied";
    $message = "Invalid or missing credentials. The Underworld rejects your plea.";
    $link_url = "index.php";
    $link_text = "Return to Gates";
} else {
    // 4. Check if a player with this email already exists
    $stmt = $pdo->prepare("SELECT * FROM Players WHERE email = ?");
    $stmt->execute([$email]);
    $player = $stmt->fetch();

    if ($player) {
        // Player exists — verify their birth date matches
        if ($player['birth_date'] === $birth_date) {
            // Match: returning player
            $title = "Welcome Back";
            $message = "Your soul's registry is recognized. We have been expecting your return.";
            $link_url = "play.php?email=" . urlencode($email);
            $link_text = "Enter the Underworld";
        } else {
            // Mismatch: email is taken but birth date is wrong
            $title = "Identity Theft";
            $message = "That email is already claimed by another soul, and your birth date does not match our records.";
            $link_url = "index.php";
            $link_text = "Try Again";
        }
    } else {
        // Player does not exist — register as a new player
        $insertStmt = $pdo->prepare("INSERT INTO Players (email, birth_date) VALUES (?, ?)");
        $insertStmt->execute([$email, $birth_date]);

        $title = "A New Soul Arrives";
        $message = "Welcome to the game. Your registry has been permanently bound to the Underworld.";
        $link_url = "play.php?email=" . urlencode($email);
        $link_text = "Begin Harvesting";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Underworld Gate - Soul Collector</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/indexPage.css" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet" />
    <link rel="icon" href="images/soulClickerImage.webp" />
    <style>
        /* Extra styling for this bridge page's action link */
        .gateway-link {
            display: inline-block;
            text-decoration: none;
            text-align: center;
            width: 100%;
            margin-top: 15px;
        }

        .message-text {
            color: var(--text);
            text-align: center;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 25px;
        }
    </style>
</head>

<body class="login-body">
    <main class="login-main">
        <div class="login-card">
            <h2 class="login-title"><?php echo htmlspecialchars($title); ?></h2>
            <p class="message-text"><?php echo htmlspecialchars($message); ?></p>

            <a href="<?php echo htmlspecialchars($link_url); ?>" class="enter-button gateway-link">
                <?php echo htmlspecialchars($link_text); ?>
            </a>
        </div>
    </main>
</body>

</html>