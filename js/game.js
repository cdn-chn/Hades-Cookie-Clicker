
/* Author Caden Chan

    Date: April 2nd, 2026

    Description: This is the JavaScript file for our clicker game "Soul Collector". It contains the core game logic, including the model for tracking souls,
    upgrades, and achievements, as well as the view functions to update the display and handle user interactions. The game allows players to click to earn souls, 
    purchase upgrades to increase their soul harvesting, and unlock achievements based on their progress. The code is structured to be modular 
    and maintainable, with clear separation between the game logic and the user interface.
*/

window.addEventListener("load", function () {

    // ==================== MODEL ====================

    // Check if saved data exists from PHP, otherwise default to 0
    let souls = window.SAVED_CURRENT_SOULS || 0;
    let totalSoulsCollected = window.SAVED_TOTAL_SOULS || 0;
    let soulsPerClick = window.SAVED_CLICK_POWER || 1;


    let autoIntervalId = null;


    // ==================== UPGRADES (WITH LOADED SAVES) ====================
    const upgrades = [
        {
            id: "scythe", name: "Rusty Scythe", basePrice: 10, growth: 1.25,
            owned: window.SAVED_SCYTHE || 0, effect: 1, type: "click"
        },
        {
            id: "spectral", name: "Spectral Blade", basePrice: 50, growth: 1.3,
            owned: window.SAVED_CHARON || 0, effect: 5, type: "click"
        },
        {
            id: "pact", name: "Dark Pact", basePrice: 200, growth: 1.35,
            owned: window.SAVED_DARKMAGE || 0, effect: 20, type: "click"
        },
        {
            id: "rift", name: "Soul Minion", basePrice: 100, growth: 1.4,
            owned: window.SAVED_RAVENS || 0, effect: 4, type: "auto"
        }
    ];

    // ==================== ACHIEVEMENTS (WITH LOADED SAVES) ====================
    // window.SAVED_ACHIEVEMENTS is an array of booleans like [true, true, false, false, false]
    const loadedAchieves = window.SAVED_ACHIEVEMENTS || [];

    const achievements = [
        { name: "First Soul", requirement: 10, earned: loadedAchieves[0] || false },
        { name: "Novice Harvester", requirement: 100, earned: loadedAchieves[1] || false },
        { name: "Automation Begins", requirement: 4, type: "auto", earned: loadedAchieves[2] || false },
        { name: "Soul Collector", requirement: 1000, earned: loadedAchieves[3] || false },
        { name: "Soul King", requirement: 5000, earned: loadedAchieves[4] || false }
    ];

    // Auto-start the ravens if the player already owns them from a previous save
    if (upgrades[3].owned > 0) {
        startAutoSystem();
    }

    // ==================== HELPERS ====================

    /**
     * Calculates the current cost to buy the next level of an upgrade based on base price, growth rate, and number already owned.
     *
     * @param {Object} upgrade - The upgrade object with basePrice, growth, and owned properties
     * @returns {Number} The integer cost in souls for the next purchase
     */
    function calculateCost(upgrade) {
        return Math.floor(
            upgrade.basePrice * Math.pow(upgrade.growth, upgrade.owned)
        );
    }

    /**
     * Returns the total number of upgrade levels owned across all upgrade types.
     *
     * @returns {Number} Total count of all owned upgrade levels
     */
    function getTotalUpgrades() {
        return upgrades.reduce((sum, u) => sum + u.owned, 0);
    }

    /**
     * Generates a floating text element above the click zone to show the amount of souls gained.
     *
     * @param {Number} amount - The number of souls gained
     * @returns {void}
     */
    function spawnFloatText(amount) {
        const zone = document.getElementById("click-zone");
        const span = document.createElement("span");
        span.className = "float-text";
        span.textContent = "+" + amount;
        zone.appendChild(span);

        setTimeout(function () {
            span.remove();
        }, 1200);
    }

    // ==================== GAME LOGIC ====================

    /**
     * Handles a click on the cookie area: adds souls per click to total souls, then updates the display and checks achievements.
     *
     * @returns {void}
     */
    function handleClick() {
        souls += soulsPerClick;
        totalSoulsCollected += soulsPerClick;
        spawnFloatText(soulsPerClick);
        updateDisplay();
        checkAchievements();
    }

    /**
     * Attempts to purchase one level of an upgrade by id. Deducts souls, increments owned count, applies click or auto effect, and updates display/achievements if affordable.
     *
     * @param {String} id - The upgrade id (e.g. "scythe", "spectral", "pact", "rift")
     * @returns {void}
     */
    function buyUpgrade(id) {
        const upgrade = upgrades.find(u => u.id === id);
        const cost = calculateCost(upgrade);

        if (souls >= cost) {
            souls -= cost;
            upgrade.owned++;

            if (upgrade.type === "click") {
                soulsPerClick += upgrade.effect;
            }

            if (upgrade.type === "auto") {
                const autoUpgrade = upgrades.find(u => u.type === "auto");
                // current auto rate = soulsPerClick * number of auto upgrades
                autoSoulsPerSecond = soulsPerClick * autoUpgrade.owned;
                startAutoSystem();
            }

            updateDisplay();
            checkAchievements();
        }
    }

    /**
     * Starts or restarts the auto soul-generation timer. Clears any existing interval, then sets a new one based on owned auto upgrades (interval shortens with more levels).
     *
     * @returns {void}
     */
    function startAutoSystem() {
        if (autoIntervalId) clearInterval(autoIntervalId); // reset timer

        const autoUpgrade = upgrades.find(u => u.type === "auto");
        if (autoUpgrade.owned === 0) return;

        // Base interval = 1000ms (1 sec)
        // Each level reduces interval slightly to make it faster
        const interval = Math.max(200, 1000 - (autoUpgrade.owned - 1) * 100);

        autoIntervalId = setInterval(() => {
            // Total auto clicks = current soulsPerClick × number of auto upgrades
            souls += soulsPerClick * autoUpgrade.owned;
            totalSoulsCollected += soulsPerClick * autoUpgrade.owned;
            updateDisplay();
            checkAchievements();
        }, interval);
    }

    // ==================== ACHIEVEMENTS ====================

    /**
     * Checks all achievements; marks any newly met as earned, shows a congrats popup, and re-renders the achievements list.
     *
     * @returns {void}
     */
    function checkAchievements() {
        achievements.forEach(function (ach) {
            if (!ach.earned) {
                if (ach.type === "auto") {
                    const autoUpgrade = upgrades.find(u => u.type === "auto");
                    if (autoUpgrade && autoUpgrade.owned >= 1) {
                        ach.earned = true;
                        showCongrats(ach.name + " Unlocked!");
                    }
                } else {
                    if (totalSoulsCollected >= ach.requirement) {
                        ach.earned = true;
                        showCongrats(ach.name + " Unlocked!");
                    }
                }
            }
        });

        renderAchievements();
    }
    // ==================== VIEW ====================

    /**
     * Updates the main game display: score, souls per click, total upgrades count, and the upgrade cards.
     *
     * @returns {void}
     */
    function updateDisplay() {
        document.getElementById("scoreDisplay").textContent = souls;
        document.getElementById("totalSoulsCollected").textContent = "Total Souls Collected: " + totalSoulsCollected;
        document.getElementById("soulsPerClickDisplay").textContent = soulsPerClick;
        document.getElementById("soulsPerSecond").textContent = soulsPerClick * upgrades.find(u => u.type === "auto").owned;
        document.getElementById("totalUpgradesDisplay").textContent = getTotalUpgrades();

        renderUpgrades();
    }

    /**
     * Renders all upgrade cards in the DOM: updates cost, level, and locked/available styling based on current souls.
     *
     * @returns {void}
     */
    function renderUpgrades() {
        upgrades.forEach(function (upgrade) {
            const card = document.getElementById("upgrade-" + upgrade.id);
            const cost = calculateCost(upgrade);

            const costSpan = card.querySelector(".cost");
            const levelTag = card.querySelector(".level-tag");

            costSpan.textContent = "Cost: " + cost;
            levelTag.textContent = "Lv " + upgrade.owned;

            if (souls >= cost) {
                card.classList.remove("locked");
                card.classList.add("available");
            } else {
                card.classList.remove("available");
                card.classList.add("locked");
            }
        });
    }

    /**
     * Rebuilds the achievements list in the DOM: creates a card for each achievement with name, description, and earned state.
     *
     * @returns {void}
     */
    function renderAchievements() {
        const container = document.getElementById("achievementsContainer");
        container.innerHTML = "";

        achievements.forEach(function (ach) {
            const div = document.createElement("div");
            div.className = "achievement-card";

            div.className = "achievement-card";
            if (ach.earned) {
                div.style.opacity = "1";
            }

            // Add achievement name on top and description below
            div.innerHTML = `
            <div class="achievement-name">${ach.name}</div>
            <div class="achievement-description">
                ${ach.type === "auto" ? "Buy Flock of Ravens" : `Souls needed: ${ach.requirement}`}
            </div>
            ${ach.earned ? '<div class="earned-check"><img src="images/pixelTrophyTransparent.png" alt="trophy"></div>' : ''}`;

            container.appendChild(div);
        });
    }

    /**
     * Shows a congratulations popup with the given message, then hides it after 2.5 seconds.
     *
     * @param {String} message - The text to display in the popup (e.g. "First Soul Unlocked!")
     * @returns {void}
     */
    function showCongrats(message) {
        const popup = document.getElementById("congratsPopup");
        popup.textContent = message;
        popup.classList.remove("hidden");

        setTimeout(function () {
            popup.classList.add("hidden");
        }, 2500);
    }

    // ==================== EVENT LISTENERS ====================

    document.getElementById("clickArea")
        .addEventListener("click", handleClick);

    upgrades.forEach(function (upgrade) {
        const card = document.getElementById("upgrade-" + upgrade.id);
        card.addEventListener("click", function () {
            buyUpgrade(upgrade.id);
        });
    });

    // Initial render
    renderAchievements();
    updateDisplay();




    // ==================== HELP OVERLAY FUNCTIONALITY ====================

    const helpButton = document.getElementById("helpButton");
    const helpOverlay = document.getElementById("helpOverlay");
    const closeHelp = document.getElementById("closeHelp");

    // Show the help overlay
    helpButton.addEventListener("click", function () {
        helpOverlay.classList.remove("hidden");
    });

    // Hide the help overlay
    closeHelp.addEventListener("click", function () {
        helpOverlay.classList.add("hidden");
    });

    // ==================== QUIT BUTTON FUNCTIONALITY ====================
    const quitButton = document.getElementById("quitButton");

    if (quitButton) {
        quitButton.addEventListener("click", function () {
            // 1. Core Stats
            document.getElementById("final_total_souls").value = totalSoulsCollected;
            document.getElementById("final_current_souls").value = souls;
            document.getElementById("final_souls_per_click").value = soulsPerClick;

            // 2. Upgrade Levels
            document.getElementById("final_scythe").value = upgrades[0].owned;
            document.getElementById("final_charon").value = upgrades[1].owned;
            document.getElementById("final_darkmage").value = upgrades[2].owned;
            document.getElementById("final_ravens").value = upgrades[3].owned;

            // 3. Achievements (Map the 'earned' booleans into a simple array and stringify it)
            const earnedArray = achievements.map(ach => ach.earned);
            document.getElementById("final_achievements").value = JSON.stringify(earnedArray);

            // Send to database
            document.getElementById("quitForm").submit();
        });
    }

    updateDisplay();
});