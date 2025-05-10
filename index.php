<?php
session_start();

// Constants
$MAX_USERS = 3;
$MAX_CARDS_PER_ROUND = 5;
$MAX_ROUNDS = 5;
$SUITS = ['hearts', 'diamonds', 'clubs', 'spades'];
$VALUES = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'A', 'J', 'Q', 'K'];

// Handle reset request
if (isset($_GET['reset'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle new cards request (normal rounds)
if (isset($_GET['new_cards']) && isset($_SESSION['settings'], $_SESSION['users'])) {
    $settings = $_SESSION['settings'];
    $numCards = $settings['num_cards'];
    $numUsers = $settings['num_users'];
    
    // Initialize deck
    $deck = [];
    foreach ($SUITS as $suit) {
        foreach ($VALUES as $value) {
            $numericValue = is_numeric($value) ? (int)$value : ($value === 'A' ? 11 : 10);
            $deck[] = [
                'value' => $value,
                'suit' => $suit,
                'numeric_value' => $numericValue
            ];
        }
    }

    // Deal new cards for the current round
    $_SESSION['current_round'] = ($_SESSION['current_round'] ?? 0) % $settings['num_rounds'];
    $_SESSION['card_results'][$_SESSION['current_round']] = [];
    
    foreach ($_SESSION['users'] as $userIndex => $user) {
        $currentDeck = $deck;
        shuffle($currentDeck);
        $round = [];
        $total = 0;
        for ($c = 0; $c < $numCards; $c++) {
            $card = array_pop($currentDeck);
            $round[] = $card;
            $total += $card['numeric_value'];
        }
        $_SESSION['card_results'][$_SESSION['current_round']][$userIndex] = [
            'cards' => $round,
            'total' => $total
        ];
    }
    
    // Initialize or reset revealed state for all players
    $_SESSION['revealed'] = array_fill(0, $numUsers, false);
    
    $_SESSION['current_round']++;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle tiebreaker round request
if (isset($_GET['tiebreaker']) && isset($_SESSION['settings'], $_SESSION['users'], $_SESSION['winners'])) {
    $settings = $_SESSION['settings'];
    $numCards = $settings['num_cards'];
    $winners = $_SESSION['winners'];
    
    // Initialize deck
    $deck = [];
    foreach ($SUITS as $suit) {
        foreach ($VALUES as $value) {
            $numericValue = is_numeric($value) ? (int)$value : ($value === 'A' ? 11 : 10);
            $deck[] = [
                'value' => $value,
                'suit' => $suit,
                'numeric_value' => $numericValue
            ];
        }
    }

    // Initialize tiebreaker results if not set
    if (!isset($_SESSION['tiebreaker_results'])) {
        $_SESSION['tiebreaker_results'] = [];
    }
    $tiebreakerRound = count($_SESSION['tiebreaker_results']);
    $_SESSION['tiebreaker_results'][$tiebreakerRound] = [];
    
    // Deal cards only to winners
    foreach ($winners as $userIndex) {
        $currentDeck = $deck;
        shuffle($currentDeck);
        $round = [];
        $total = 0;
        for ($c = 0; $c < $numCards; $c++) {
            $card = array_pop($currentDeck);
            $round[] = $card;
            $total += $card['numeric_value'];
        }
        $_SESSION['tiebreaker_results'][$tiebreakerRound][$userIndex] = [
            'cards' => $round,
            'total' => $total
        ];
    }
    
    // Initialize revealed state for tiebreaker
    $_SESSION['tiebreaker_revealed'][$tiebreakerRound] = array_fill_keys($winners, false);
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['reveal_player']) && !isset($_POST['reveal_tiebreaker_player'])) {
    // Validate inputs
    $numUsers = min((int)($_POST['num_users'] ?? 1), $MAX_USERS);
    $numCards = min((int)($_POST['num_cards'] ?? 1), $MAX_CARDS_PER_ROUND);
    $numRounds = min((int)($_POST['num_rounds'] ?? 1), $MAX_ROUNDS);

    // Store settings
    $_SESSION['settings'] = [
        'num_users' => $numUsers,
        'num_cards' => $numCards,
        'num_rounds' => $numRounds,
    ];

    // Store user names
    $_SESSION['users'] = [];
    for ($i = 1; $i <= $numUsers; $i++) {
        $name = filter_var($_POST["ime$i"] ?? '', FILTER_SANITIZE_STRING);
        if (empty($name)) {
            $name = "Igralec $i";
        }
        $_SESSION['users'][] = ['name' => $name];
    }

    // Initialize card results, current round, and revealed state
    $_SESSION['card_results'] = [];
    $_SESSION['current_round'] = 0;
    $_SESSION['revealed'] = array_fill(0, $numUsers, false);
    unset($_SESSION['tiebreaker_results'], $_SESSION['tiebreaker_revealed'], $_SESSION['winners']);

    // Deal first round
    header('Location: ' . $_SERVER['PHP_SELF'] . '?new_cards');
    exit;
}

// Handle reveal player request (normal rounds)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_player'])) {
    $playerIndex = (int)$_POST['reveal_player'];
    if (isset($_SESSION['revealed'][$playerIndex])) {
        $_SESSION['revealed'][$playerIndex] = true;
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle reveal tiebreaker player request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal_tiebreaker_player'])) {
    $playerIndex = (int)$_POST['reveal_tiebreaker_player'];
    $tiebreakerRound = (int)$_POST['tiebreaker_round'];
    if (isset($_SESSION['tiebreaker_revealed'][$tiebreakerRound][$playerIndex])) {
        $_SESSION['tiebreaker_revealed'][$tiebreakerRound][$playerIndex] = true;
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Display results if game data exists
if (isset($_SESSION['users'], $_SESSION['settings'], $_SESSION['card_results'])) {
    $settings = $_SESSION['settings'];
    $numRounds = $settings['num_rounds'];
    $numUsers = $settings['num_users'];
    $currentRound = ($_SESSION['current_round'] ?? 0) - 1;
    if ($currentRound < 0) $currentRound = 0;

    // Initialize revealed state if not set
    if (!isset($_SESSION['revealed'])) {
        $_SESSION['revealed'] = array_fill(0, $numUsers, false);
    }

    // Calculate totals and determine winners only when all cards are revealed
    $allRevealed = !in_array(false, $_SESSION['revealed']);
    $winners = [];
    $tiebreakerActive = false;
    $tiebreakerWinners = [];
    if ($allRevealed && !empty($_SESSION['card_results'])) {
        $totals = [];
        foreach ($_SESSION['users'] as $userIndex => $user) {
            $userTotal = 0;
            for ($r = 0; $r < min($currentRound + 1, $numRounds); $r++) {
                if (isset($_SESSION['card_results'][$r][$userIndex])) {
                    $userTotal += $_SESSION['card_results'][$r][$userIndex]['total'];
                }
            }
            $totals[$userIndex] = $userTotal;
        }

        // Determine winners (highest total points)
        $maxScore = max($totals);
        foreach ($totals as $index => $total) {
            if ($total === $maxScore) {
                $winners[] = $index;
            }
        }
        $_SESSION['winners'] = $winners;

        // Check for tiebreaker
        if (count($winners) > 1 && isset($_SESSION['tiebreaker_results'])) {
            $tiebreakerActive = true;
            $latestTiebreakerRound = count($_SESSION['tiebreaker_results']) - 1;
            $tiebreakerTotals = [];
            $allTiebreakerRevealed = !in_array(false, $_SESSION['tiebreaker_revealed'][$latestTiebreakerRound] ?? []);

            if ($allTiebreakerRevealed) {
                foreach ($winners as $userIndex) {
                    $tiebreakerTotal = $_SESSION['tiebreaker_results'][$latestTiebreakerRound][$userIndex]['total'];
                    $tiebreakerTotals[$userIndex] = $tiebreakerTotal;
                }
                $maxTiebreakerScore = max($tiebreakerTotals);
                foreach ($tiebreakerTotals as $index => $total) {
                    if ($total === $maxTiebreakerScore) {
                        $tiebreakerWinners[] = $index;
                    }
                }
                // Update winners if tiebreaker resolved
                if (count($tiebreakerWinners) === 1) {
                    $winners = $tiebreakerWinners;
                    $_SESSION['winners'] = $winners;
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezultati igre - Poker Room</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Rezultati igre - Poker Room - Runda <?php echo ($currentRound + 1); ?></h1>
    <div class="table-container">
        <div class="player-row">
            <?php
            foreach ($_SESSION['users'] as $index => $user) {
                echo '<div class="player" id="player-' . $index . '">';
                echo '<h2>' . htmlspecialchars($user['name']) . '</h2>';
                echo '<div class="round" id="round-' . $index . '-' . $currentRound . '">';
                if (isset($_SESSION['card_results'][$currentRound][$index])) {
                    foreach ($_SESSION['card_results'][$currentRound][$index]['cards'] as $cardIndex => $card) {
                        echo '<span class="card-container">';
                        $imgSrc = "slike/" . strtolower($card['suit']) . "_" . strtolower($card['value']) . ".png";
                        $altText = "Karta {$card['value']} {$card['suit']}";
                        echo '<img class="card ' . ($_SESSION['revealed'][$index] ? '' : 'hidden') . '" src="' . 
                             ($_SESSION['revealed'][$index] ? $imgSrc : 'slike/back_dark.png') . '" alt="' . 
                             ($_SESSION['revealed'][$index] ? $altText : 'Face-down card') . '" ' . 
                             'data-src="' . $imgSrc . '" data-alt="' . $altText . '" onerror="this.src=\'slike/fallback.png\';">';
                        echo '</span>';
                    }
                }
                echo '</div>';
                if ($_SESSION['revealed'][$index] && isset($_SESSION['card_results'][$currentRound][$index])) {
                    echo '<p class="points">Točke: ' . $_SESSION['card_results'][$currentRound][$index]['total'] . '</p>';
                }
                echo '<button class="button reveal-button" ' . ($_SESSION['revealed'][$index] ? 'disabled' : '') . 
                     ' onclick="revealCards(' . $index . ')">Razkrij karte</button>';
                if ($_SESSION['revealed'][$index]) {
                    $userTotal = 0;
                    for ($r = 0; $r <= $currentRound; $r++) {
                        if (isset($_SESSION['card_results'][$r][$index])) {
                            $userTotal += $_SESSION['card_results'][$r][$index]['total'];
                        }
                    }
                    echo '<p class="total-score"><strong>Skupno: ' . $userTotal . '</strong></p>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <?php
    // Display tiebreaker rounds if active
    if ($tiebreakerActive && isset($_SESSION['tiebreaker_results'])) {
        foreach ($_SESSION['tiebreaker_results'] as $tiebreakerRound => $results) {
            $allTiebreakerRevealed = !in_array(false, $_SESSION['tiebreaker_revealed'][$tiebreakerRound]);
            echo '<div class="tiebreaker-container">';
            echo '<h2>Runda preboja ' . ($tiebreakerRound + 1) . '</h2>';
            echo '<div class="player-row">';
            foreach ($winners as $userIndex) {
                echo '<div class="player" id="tiebreaker-player-' . $userIndex . '-' . $tiebreakerRound . '">';
                echo '<h2>' . htmlspecialchars($_SESSION['users'][$userIndex]['name']) . '</h2>';
                echo '<div class="round" id="tiebreaker-round-' . $userIndex . '-' . $tiebreakerRound . '">';
                if (isset($results[$userIndex])) {
                    foreach ($results[$userIndex]['cards'] as $cardIndex => $card) {
                        echo '<span class="card-container">';
                        $imgSrc = "slike/" . strtolower($card['suit']) . "_" . strtolower($card['value']) . ".png";
                        $altText = "Karta {$card['value']} {$card['suit']}";
                        echo '<img class="card ' . ($_SESSION['tiebreaker_revealed'][$tiebreakerRound][$userIndex] ? '' : 'hidden') . '" src="' . 
                             ($_SESSION['tiebreaker_revealed'][$tiebreakerRound][$userIndex] ? $imgSrc : 'slike/back_dark.png') . '" alt="' . 
                             ($_SESSION['tiebreaker_revealed'][$tiebreakerRound][$userIndex] ? $altText : 'Face-down card') . '" ' . 
                             'data-src="' . $imgSrc . '" data-alt="' . $altText . '" onerror="this.src=\'slike/fallback.png\';">';
                        echo '</span>';
                    }
                }
                echo '</div>';
                if ($_SESSION['tiebreaker_revealed'][$tiebreakerRound][$userIndex] && isset($results[$userIndex])) {
                    echo '<p class="points">Točke: ' . $results[$userIndex]['total'] . '</p>';
                }
                echo '<button class="button reveal-button" ' . ($_SESSION['tiebreaker_revealed'][$tiebreakerRound][$userIndex] ? 'disabled' : '') . 
                     ' onclick="revealTiebreakerCards(' . $userIndex . ', ' . $tiebreakerRound . ')">Razkrij karte</button>';
                echo '</div>';
            }
            echo '</div></div>';
        }
    }

    // Display winners or tiebreaker prompt
    if ($allRevealed && !empty($winners)) {
        if (count($winners) > 1 && count($tiebreakerWinners) !== 1) {
            echo '<h2 class="winner">Neodločeno! Več zmagovalcev:</h2>';
            foreach ($winners as $winIdx) {
                echo '<p class="winner">' . htmlspecialchars($_SESSION['users'][$winIdx]['name']) . '</p>';
            }
            if (!isset($_SESSION['tiebreaker_results']) || (isset($_SESSION['tiebreaker_results']) && count($tiebreakerWinners) > 1)) {
                echo '<button class="button tiebreaker-button" onclick="window.location.href=\'' . 
                     $_SERVER['PHP_SELF'] . '?tiebreaker\'">Naslednja runda preboja</button>';
            }
        } else {
            echo '<h2 class="winner">' . (count($winners) > 1 ? 'Zmagovalci' : 'Zmagovalec') . ':</h2>';
            foreach ($winners as $winIdx) {
                echo '<p class="winner">' . htmlspecialchars($_SESSION['users'][$winIdx]['name']) . '</p>';
            }
        }
    }
    ?>

    <div>
        <?php
        if ($numRounds > 1 && $currentRound < $numRounds - 1 && !$tiebreakerActive) {
            echo '<button class="button new-cards-button" onclick="window.location.href=\'' . 
                 $_SERVER['PHP_SELF'] . '?new_cards\'">Nove karte</button>';
        }
        ?>
        <button class="button" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?reset'">Nazaj</button>
    </div>

    <script src="script.js"></script>
</body>
</html>
<?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poker Room - Začetek</title>
    <link rel="stylesheet" href="form.css">
</head>
<body>
    <h1>Poker Room</h1>
    <form method="POST" id="gameForm">
        <label>Število uporabnikov (1–3):
            <input type="number" name="num_users" min="1" max="3" value="3" required aria-label="Število uporabnikov">
        </label>
        <label>Število kart na rundo (1–5):
            <input type="number" name="num_cards" min="1" max="5" value="3" required aria-label="Število kart na rundo">
        </label>
        <label>Število rund (1–5):
            <input type="number" name="num_rounds" min="1" max="5" value="1" required aria-label="Število rund">
        </label>
        <div id="userFields"></div>
        <button class="button" type="submit" id="submitButton" disabled>Začni igro</button>
        <button type="button" class="button rules-button" onclick="toggleRules()">Prikaži pravila</button>
    </form>
    <div class="rules-container" id="rulesContainer">
        <h3>Pravila igre</h3>
        <p>- Vsak igralec dobi izbrano število kart v vsaki rundi.</p>
        <p>- Vrednosti kart: 2–10 so nominalne, A = 11, J/Q/K = 10.</p>
        <p>- Točke v rundi so seštevek vrednosti kart, prikazan po razkritju.</p>
        <p>- Zmagovalec je igralec z najvišjim skupnim seštevkom točk po vseh rundah.</p>
        <p>- Razkrijte karte z gumbom "Razkrij karte". Točke in skupni seštevek se prikažejo po razkritju.</p>
        <p>- Če je več zmagovalcev, dobijo dodatne runde preboja, dokler en igralec ne doseže višjega seštevka.</p>
        <p class="credentials">Made by Luka Dragan in 9.5.2025</p>
    </div>

    <script src="script.js"></script>
</body>
</html>