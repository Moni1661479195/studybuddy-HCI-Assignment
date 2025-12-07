<?php
// api/open_pack.php
header('Content-Type: application/json');

// 1. Load dependencies
require_once '../session.php';
require_once '../lib/db.php';

// 2. Check Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db(); // Using PDO connection

try {
    // 3. Start Transaction (Crucial for inventory integrity)
    $db->beginTransaction();

    // 4. Check if user has packs
    $stmt = $db->prepare("SELECT card_packs FROM user_inventory WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventory || $inventory['card_packs'] < 1) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'No packs available!']);
        exit();
    }

    // 5. Deduct 1 Pack
    $updateStmt = $db->prepare("UPDATE user_inventory SET card_packs = card_packs - 1 WHERE user_id = ?");
    $updateStmt->execute([$user_id]);

    // 6. Gacha Logic: Draw 5 Cards
    $drawn_cards = [];
    $cards_to_insert = [];

    // Fetch all available cards grouped by rarity to optimize performance
    // Structure: ['N' => [id, id...], 'R' => [id, id...]]
    $all_cards_stmt = $db->query("SELECT id, name, rarity, image_path FROM cards");
    $card_pool = ['N' => [], 'R' => [], 'SR' => [], 'SSR' => []];
    
    while ($row = $all_cards_stmt->fetch(PDO::FETCH_ASSOC)) {
        $card_pool[$row['rarity']][] = $row;
    }

    // Loop 5 times (1 pack = 5 cards)
    for ($i = 0; $i < 5; $i++) {
        // Generate random number 1-100 for rarity
        $rand = mt_rand(1, 100);
        $rarity = 'N';

        if ($rand <= 60) { $rarity = 'N'; }          // 60% Chance
        elseif ($rand <= 90) { $rarity = 'R'; }      // 30% Chance (60-90)
        elseif ($rand <= 99) { $rarity = 'SR'; }     // 9% Chance (90-99)
        else { $rarity = 'SSR'; }                    // 1% Chance (100)

        // Ensure we have cards of this rarity defined in DB
        if (empty($card_pool[$rarity])) {
            $rarity = 'N'; // Fallback to N if specific rarity pool is empty
        }

        // Pick a random card from the chosen rarity pool
        $random_index = array_rand($card_pool[$rarity]);
        $selected_card = $card_pool[$rarity][$random_index];

        $drawn_cards[] = $selected_card;
        
        // Prepare data for batch insert later (optional, but clean)
        // For simplicity, we can insert one by one inside transaction
        $insertStmt = $db->prepare("INSERT INTO user_cards (user_id, card_id) VALUES (?, ?)");
        $insertStmt->execute([$user_id, $selected_card['id']]);
    }

    // 7. Commit Transaction
    $db->commit();

    // 8. Return the results to Frontend
    echo json_encode([
        'success' => true,
        'new_pack_count' => $inventory['card_packs'] - 1,
        'cards' => $drawn_cards
    ]);

} catch (Exception $e) {
    // If anything goes wrong, undo changes
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>