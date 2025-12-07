<?php
// pokedex.php - The Card Album / Gallery

require_once 'session.php';
require_once 'lib/db.php';

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db();

// 1. Fetch ALL cards and check if user owns them using LEFT JOIN
// This query gets every card in existence, and counts how many the user has.
$sql = "SELECT c.*, COUNT(uc.id) as owned_count 
        FROM cards c 
        LEFT JOIN user_cards uc ON c.id = uc.card_id AND uc.user_id = ?
        GROUP BY c.id
        ORDER BY FIELD(c.rarity, 'SSR', 'SR', 'R', 'N'), c.id ASC";

$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$all_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Group cards by Rarity for display
$collection = [
    'SSR' => [],
    'SR'  => [],
    'R'   => [],
    'N'   => []
];

$total_unique_cards = count($all_cards);
$collected_unique = 0;

foreach ($all_cards as $card) {
    $collection[$card['rarity']][] = $card;
    if ($card['owned_count'] > 0) {
        $collected_unique++;
    }
}

// Calculate Completion Percentage
$completion_rate = $total_unique_cards > 0 ? round(($collected_unique / $total_unique_cards) * 100) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Album - Study Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* --- Visual Styles --- */
        /* Unowned State: Grayscale & Dimmed */
        .card-locked { 
            filter: grayscale(100%); 
            opacity: 0.6; 
            transition: all 0.3s ease;
        }
        .card-locked:hover {
            opacity: 0.8; /* Slight highlight on hover even if locked */
        }

        /* Rarity Borders (Only show color if owned) */
        .owned.rarity-N { border-color: #e5e7eb; }
        .owned.rarity-R { border-color: #3b82f6; box-shadow: 0 0 8px rgba(59, 130, 246, 0.2); }
        .owned.rarity-SR { border-color: #a855f7; box-shadow: 0 0 12px rgba(168, 85, 247, 0.4); }
        .owned.rarity-SSR { border-color: #eab308; box-shadow: 0 0 15px rgba(234, 179, 8, 0.6); animation: pulse-gold 3s infinite; }

        @keyframes pulse-gold { 
            0% { box-shadow: 0 0 10px rgba(234, 179, 8, 0.4); } 
            50% { box-shadow: 0 0 20px rgba(234, 179, 8, 0.8); } 
            100% { box-shadow: 0 0 10px rgba(234, 179, 8, 0.4); } 
        }
    </style>
</head>
<body class="bg-gray-100">

<?php include 'header.php'; ?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-24 md:mt-32 mb-12 max-w-7xl">

    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-book-open text-blue-600"></i> Card Album
            </h1>
            <p class="text-gray-600 mt-1">Collect all cards to become the ultimate Study Master!</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 w-full md:w-1/3">
            <div class="flex justify-between text-sm font-bold text-gray-700 mb-2">
                <span>Collection Progress</span>
                <span><?php echo $collected_unique; ?> / <?php echo $total_unique_cards; ?> Cards</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-4 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-4 rounded-full transition-all duration-1000" 
                     style="width: <?php echo $completion_rate; ?>%"></div>
            </div>
            <div class="text-right text-xs text-gray-400 mt-1"><?php echo $completion_rate; ?>% Complete</div>
        </div>
    </div>

    <?php 
    // Define display order and colors
    $rarity_config = [
        'SSR' => ['title' => 'Legendary (SSR)', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-50'],
        'SR'  => ['title' => 'Epic (SR)',       'color' => 'text-purple-600', 'bg' => 'bg-purple-50'],
        'R'   => ['title' => 'Rare (R)',        'color' => 'text-blue-600',   'bg' => 'bg-blue-50'],
        'N'   => ['title' => 'Common (N)',      'color' => 'text-gray-600',   'bg' => 'bg-gray-100'],
    ];

    foreach ($rarity_config as $rarity_key => $config): 
        $cards = $collection[$rarity_key];
        if (empty($cards)) continue; // Skip empty categories
    ?>
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-6 border-b border-gray-200 pb-2">
                <span class="px-3 py-1 rounded-lg text-sm font-bold uppercase tracking-wider <?php echo $config['bg'] . ' ' . $config['color']; ?>">
                    <?php echo $rarity_key; ?>
                </span>
                <h2 class="text-xl font-bold text-gray-800"><?php echo $config['title']; ?></h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                <?php foreach ($cards as $card): 
                    $is_owned = $card['owned_count'] > 0;
                ?>
                    <div class="relative group">
                        <div class="bg-white rounded-xl overflow-hidden border-2 shadow-sm transition transform hover:-translate-y-1 
                            <?php echo $is_owned ? 'owned rarity-' . $card['rarity'] : 'card-locked border-gray-200'; ?>">
                            
                            <div class="aspect-w-1 aspect-h-1 w-full bg-gray-50 flex items-center justify-center overflow-hidden relative">
                                <?php if ($card['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($card['image_path']); ?>" class="object-cover w-full h-full">
                                <?php else: ?>
                                    <i class="fas fa-image text-gray-300 text-3xl"></i>
                                <?php endif; ?>

                                <?php if (!$is_owned): ?>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-10">
                                        <i class="fas fa-lock text-4xl text-gray-600 opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="p-3 text-center">
                                <h3 class="font-bold text-sm text-gray-800 truncate">
                                    <?php echo $is_owned ? htmlspecialchars($card['name']) : '???'; ?>
                                </h3>
                                
                                <div class="mt-1">
                                    <?php if ($is_owned): ?>
                                        <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full font-bold">
                                            x<?php echo $card['owned_count']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">Not discovered</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-12">
        <a href="flashcards.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 font-semibold transition">
            <i class="fas fa-arrow-left"></i> Back to Mission Center
        </a>
    </div>

</main>

<?php include 'footer.php'; ?>

</body>
</html>