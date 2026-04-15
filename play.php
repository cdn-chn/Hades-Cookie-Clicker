<!--
    Author: Caden Chan
    Date: April 2nd, 2026
    Description: play.php — Main game interface for "Soul Collector".
    Sets up the game layout including the click zone, upgrade shop, and achievement panel.
    PHP retrieves the player's saved data from the database using their email so they
    can continue from a previous session. All saved values are passed to JavaScript
    via window globals for use by the game logic in game.js.
-->

<!doctype html>
<?php
require_once '../connect.php';

$email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
  die("Error: No valid soul registry provided.");
}

// 1. Fetch EVERYTHING from the latest save
$loadStmt = $pdo->prepare("SELECT total_souls, current_souls, souls_per_click, scythe_level, charon_level, dark_mage_level, ravens_level, achievements_unlocked FROM Results WHERE email = ? ORDER BY id DESC LIMIT 1");
$loadStmt->execute([$email]);
$saveData = $loadStmt->fetch();

// 2. Assign to variables (with defaults if no save exists)
$startingTotal = $saveData ? $saveData['total_souls'] : 0;
$startingCurrent = $saveData ? $saveData['current_souls'] : 0;
$startingClick = $saveData ? $saveData['souls_per_click'] : 1;

$lvlScythe = $saveData ? $saveData['scythe_level'] : 0;
$lvlCharon = $saveData ? $saveData['charon_level'] : 0;
$lvlDarkMage = $saveData ? $saveData['dark_mage_level'] : 0;
$lvlRavens = $saveData ? $saveData['ravens_level'] : 0;

// Default to an empty JSON array string if no achievements are saved
$savedAchievements = ($saveData && $saveData['achievements_unlocked']) ? $saveData['achievements_unlocked'] : '[]';
?>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Soul Collector</title>
  <link rel="stylesheet" href="css/style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet" />

  <link rel="icon" href="images/soulClickerImage.webp" />

  <script>
    // Pass saved game state to JavaScript as window globals
    window.SAVED_TOTAL_SOULS = <?php echo (int) $startingTotal; ?>;
    window.SAVED_CURRENT_SOULS = <?php echo (int) $startingCurrent; ?>;
    window.SAVED_CLICK_POWER = <?php echo (int) $startingClick; ?>;

    window.SAVED_SCYTHE = <?php echo (int) $lvlScythe; ?>;
    window.SAVED_CHARON = <?php echo (int) $lvlCharon; ?>;
    window.SAVED_DARKMAGE = <?php echo (int) $lvlDarkMage; ?>;
    window.SAVED_RAVENS = <?php echo (int) $lvlRavens; ?>;

    // Pass the achievements array string safely
    window.SAVED_ACHIEVEMENTS = <?php echo $savedAchievements; ?>;
  </script>
  <script src="js/game.js"></script>
</head>

<body>
  <header class="top-bar">
    <p id="totalSoulsCollected">Total Souls Collected:</p>
    <h1>Soul Collector</h1>
    <div class="header-actions">
      <button id="helpButton" class="help-button">Help</button>
      <button id="quitButton" class="help-button quit-button">Save & Quit</button>
    </div>

    <!-- Hidden form submitted on quit to save progress and redirect to leaderboard -->
    <form id="quitForm" action="leaderboard.php" method="POST" style="display: none;">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
      <input type="hidden" id="final_total_souls" name="total_souls" value="0">
      <input type="hidden" id="final_current_souls" name="current_souls" value="0">
      <input type="hidden" id="final_souls_per_click" name="souls_per_click" value="1">

      <input type="hidden" id="final_scythe" name="scythe_level" value="0">
      <input type="hidden" id="final_charon" name="charon_level" value="0">
      <input type="hidden" id="final_darkmage" name="dark_mage_level" value="0">
      <input type="hidden" id="final_ravens" name="ravens_level" value="0">
      <input type="hidden" id="final_achievements" name="achievements_unlocked" value="[]">
    </form>
  </header>

  <main class="world-grid">
    <section class="ui-panel left-panel">
      <div class="counter-box">
        <h3>SOULS COLLECTED</h3>
        <span id="scoreDisplay">0</span>
      </div>

      <div class="click-zone" id="click-zone">
        <div class="soul-pixel" id="clickArea">
          <img src="images/soulClickerImage.webp" alt="Soul Orb" />
        </div>
      </div>

      <div class="stats-section">
        <div class="stat-item">
          <span class="stat-label">SOULS PER CLICK: </span>
          <span class="stat-value" id="soulsPerClickDisplay">1</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">SOULS PER SECOND: </span>
          <span class="stat-value" id="soulsPerSecond">0</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">POWER LEVEL</span>
          <span class="stat-value" id="totalUpgradesDisplay">0</span>
        </div>
      </div>
    </section>

    <section class="env-panel">
      <!-- env stands for Environment panel — background artwork for the game world -->
      <img src="images/actualbilikeactually.png" alt="" />

      <div class="achievements-container">
        <h3 class="achievement-title">Achievements</h3>

        <div id="achievementsContainer" class="achievement-grid">
          <div class="achievement-card">
            <span class="name">First Soul</span>
          </div>
          <div class="achievement-card">
            <span class="name">Novice Harvester</span>
          </div>
          <div class="achievement-card">
            <span class="name">Automation</span>
          </div>
          <div class="achievement-card">
            <span class="name">Underworld</span>
          </div>
          <div class="achievement-card">
            <span class="name">Soul King</span>
          </div>
        </div>
      </div>

      <div id="messageArea" class="message-area"></div>
      <div id="congratsPopup" class="congrats-popup hidden"></div>
    </section>

    <section class="ui-panel right-panel">
      <h2 class="panel-label">UPGRADES</h2>

      <div class="shop-list">
        <div class="item-card available" id="upgrade-scythe">
          <div class="item-icon scythe">
            <img src="images/icon1.jpg" alt="" />
          </div>
          <div class="item-meta">
            <span class="name">Scythe</span>
            <span class="description">+1 souls per click </span>
            <span class="cost"></span>
          </div>
          <div class="level-tag"></div>
        </div>

        <div class="item-card available" id="upgrade-spectral">
          <div class="item-icon spectral">
            <img src="images/icon2.jpg" alt="" />
          </div>
          <div class="item-meta">
            <span class="name">Charon</span>
            <span class="description">+5 souls per click </span>
            <span class="cost"></span>
          </div>
          <div class="level-tag"></div>
        </div>

        <div class="item-card locked" id="upgrade-pact">
          <div class="item-icon pact">
            <img src="images/icon3.jpg" alt="" />
          </div>
          <div class="item-meta">
            <span class="name">Dark Mage</span>
            <span class="description">+20 souls per click </span>
            <span class="cost"></span>
          </div>
          <div class="level-tag"></div>
        </div>

        <div class="item-card locked" id="upgrade-rift">
          <div class="item-icon rift">
            <img src="images/icon4.jpg" alt="" />
          </div>
          <div class="item-meta">
            <span class="name">Flock of Ravens</span>
            <span class="description">Automatically collects souls equal to clicking power per second</span>
            <span class="cost"></span>
          </div>
          <div class="level-tag"></div>
        </div>
      </div>
    </section>
  </main>

  <!-- HELP OVERLAY — shown when the player clicks the Help button -->
  <div id="helpOverlay" class="hidden">
    <div class="help-content">
      <button id="closeHelp">&times;</button>
      <h2 class="help-title">How to Play</h2>
      <p>
        Welcome to <strong>Soul Harvester</strong>! Your goal is to collect as
        many souls as possible. Click the Soul Orb or use upgrades to increase
        your harvest rate.
      </p>

      <h3>Controls</h3>
      <ul>
        <li><strong>Click Soul Orb:</strong> Collect souls manually.</li>
        <li><strong>REAP SOUL button:</strong> Reap collected souls.</li>
        <li>
          <strong>Upgrades:</strong> Purchase to increase souls per click or
          automate collection.
        </li>
      </ul>

      <h3>Upgrades & Rewards</h3>
      <ul>
        <li><strong>Scythe:</strong> +1 soul per click</li>
        <li><strong>Charon:</strong> +5 souls per click</li>
        <li><strong>Dark Mage:</strong> +20 souls per click</li>
        <li><strong>Flock of Ravens:</strong> Automatic soul collection</li>
      </ul>

      <h3>Achievements</h3>
      <ul>
        <li><strong>First Soul:</strong> Collect your first soul</li>
        <li><strong>Novice Harvester:</strong> Collect 50 souls</li>
        <li><strong>Automation:</strong> Unlock automatic soul collection</li>
        <li><strong>Underworld:</strong> Reach power level 50</li>
        <li><strong>Soul King:</strong> Reach power level 100</li>
      </ul>

      <p>
        Click the <strong>&times;</strong> button to close this help screen.
      </p>
    </div>
  </div>
</body>

</html>