<!DOCTYPE html>
<!--
    Author: Caden Chan
    Date: April 2nd, 2026
    Description: index.php — Entry point for the Soul Collector clicker game.
    Displays a login/registration form asking for email and birth date.
    JavaScript in indexPage.js validates the email format before allowing submission.
    On success, the form submits via POST to login.php.

    NOTE: Google Gemini was used to generate the login page background art — not original work.
    Inspired by Hades 2.
-->
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Soul Collector</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/indexPage.css" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet" />
    <link rel="icon" href="images/soulClickerImage.webp" />
</head>

<body class="login-body">

    <main class="login-main">
        <div class="login-card">
            <h2 class="login-title">Enter the Underworld</h2>
            <p class="login-subtitle">Provide your soul's registry to proceed</p>

            <!-- Email error message — shown by indexPage.js on invalid input -->
            <div id="emailError" class="error-message hidden">
                Invalid email. Must be in the format: name@domain.ext
            </div>

            <!-- Login form — novalidate defers all validation to indexPage.js -->
            <form id="loginForm" action="login.php" method="POST" novalidate>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="soul@underworld.com" required
                        maxlength="255" autocomplete="email" />
                </div>

                <div class="form-group">
                    <label for="birth_date">Birth Date <span class="label-note">(used as your password)</span></label>
                    <input type="date" id="birth_date" name="birth_date" required min="1960-01-01" max="2026-12-31" />
                </div>

                <button type="submit" class="enter-button">Enter the Underworld</button>
            </form>
        </div>
    </main>

    <script src="js/indexPage.js"></script>
</body>

</html>