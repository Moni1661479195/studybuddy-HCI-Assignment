<?php
// my_cards.php - User's Card Inventory with Link to Album

require_once 'session.php';
require_once 'lib/db.php';

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db();

// 1. Get User's Pack Count
$stmt = $db->prepare("SELECT card_packs FROM user_inventory WHERE user_id = ?");
$stmt->execute([$user_id]);
$pack_count = $stmt->fetchColumn() ?: 0;

// 2. Get User's Owned Cards (Grouped by Rarity)
$sql = "SELECT c.*, count(uc.card_id) as count 
        FROM user_cards uc 
        JOIN cards c ON uc.card_id = c.id 
        WHERE uc.user_id = ? 
        GROUP BY c.id 
        ORDER BY FIELD(c.rarity, 'SSR', 'SR', 'R', 'N'), c.id ASC";
$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$my_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collection - Study Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* --- 3D Card Flip Animation --- */
        .perspective-1000 { perspective: 1000px; }
        .transform-style-3d { transform-style: preserve-3d; }
        .backface-hidden { backface-visibility: hidden; }
        .rotate-y-180 { transform: rotateY(180deg); }
        
        .card-inner { transition: transform 0.6s; transform-style: preserve-3d; }
        .pack-card-container.flipped .card-inner { transform: rotateY(180deg); }
        
        /* Rarity Borders & Glows */
        .rarity-N { border-color: #e5e7eb; }
        .rarity-R { border-color: #3b82f6; box-shadow: 0 0 10px rgba(59, 130, 246, 0.2); }
        .rarity-SR { border-color: #a855f7; box-shadow: 0 0 15px rgba(168, 85, 247, 0.4); }
        .rarity-SSR { border-color: #eab308; box-shadow: 0 0 20px rgba(234, 179, 8, 0.6); animation: pulse-gold 2s infinite; }

        @keyframes pulse-gold {
            0% { box-shadow: 0 0 10px rgba(234, 179, 8, 0.4); }
            50% { box-shadow: 0 0 25px rgba(234, 179, 8, 0.8); }
            100% { box-shadow: 0 0 10px rgba(234, 179, 8, 0.4); }
        }
    </style>
</head>
<body class="bg-gray-100">

<?php include 'header.php'; ?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-24 md:mt-32 mb-12">

    <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-10 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">My Collection</h1>
            <p class="text-gray-600">Collect cards by completing tasks!</p>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="pokedex.php" class="hidden sm:flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-5 py-3 rounded-full shadow-sm hover:shadow-md hover:text-blue-600 transition group">
                <i class="fas fa-book-open text-blue-400 group-hover:text-blue-600 transition"></i>
                <span class="font-semibold">View Full Album</span>
            </a>

            <div class="bg-white p-2 pr-6 rounded-full shadow-lg flex items-center gap-4 border border-gray-100">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-xl">
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Inventory</p>
                    <p class="text-xl font-bold text-gray-800">
                        <span id="pack-count-display"><?php echo $pack_count; ?></span> Packs
                    </p>
                </div>
                <button onclick="openPack()" 
                        id="btn-open-pack"
                        <?php echo $pack_count > 0 ? '' : 'disabled'; ?>
                        class="ml-2 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-2 px-6 rounded-full shadow-md transition transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                    OPEN PACK
                </button>
            </div>
        </div>
    </div>

    <div class="sm:hidden mb-6">
        <a href="pokedex.php" class="flex items-center justify-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-3 rounded-xl shadow-sm w-full">
            <i class="fas fa-book-open text-blue-500"></i>
            <span class="font-semibold">View Full Album</span>
        </a>
    </div>

    <?php if (empty($my_cards)): ?>
        <div class="text-center py-20 bg-white rounded-xl shadow-sm border border-dashed border-gray-300">
            <i class="fas fa-folder-open text-6xl text-gray-200 mb-4"></i>
            <p class="text-gray-500 text-lg">You don't have any cards yet.</p>
            <p class="text-gray-400 mb-6">Complete tasks to earn packs!</p>
            <a href="flashcards.php" class="text-blue-500 font-bold hover:underline">Go to Missions &rarr;</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <?php foreach ($my_cards as $card): ?>
                <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition border-2 rarity-<?php echo $card['rarity']; ?> relative group">
                    
                    <?php if ($card['count'] > 1): ?>
                        <div class="absolute top-2 right-2 bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded-full z-10 shadow-sm">
                            x<?php echo $card['count']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="absolute top-2 left-2 text-xs font-bold px-2 py-0.5 rounded shadow-sm z-10
                        <?php 
                            if($card['rarity']=='N') echo 'bg-gray-200 text-gray-600';
                            elseif($card['rarity']=='R') echo 'bg-blue-100 text-blue-600';
                            elseif($card['rarity']=='SR') echo 'bg-purple-100 text-purple-600';
                            elseif($card['rarity']=='SSR') echo 'bg-yellow-100 text-yellow-700';
                        ?>">
                        <?php echo $card['rarity']; ?>
                    </div>

                    <div class="aspect-w-1 aspect-h-1 w-full bg-gray-50 flex items-center justify-center overflow-hidden">
                        <?php if ($card['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($card['image_path']); ?>" class="object-cover w-full h-full transition duration-500 group-hover:scale-110">
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center h-48 w-full text-gray-300">
                                <i class="fas fa-image text-4xl mb-2"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-3 text-center">
                        <h3 class="font-bold text-gray-800 text-sm truncate"><?php echo htmlspecialchars($card['name']); ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<div id="pack-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90 hidden opacity-0 transition-opacity duration-300">
    <div class="w-full max-w-5xl px-4 text-center">
        
        <div id="scene-pack" class="flex flex-col items-center justify-center">
            <div class="text-white text-2xl font-bold mb-8 animate-pulse">Opening Pack...</div>
            <i class="fas fa-box text-9xl text-yellow-500 animate-bounce"></i>
        </div>

        <div id="scene-cards" class="hidden grid grid-cols-2 md:grid-cols-5 gap-4">
            </div>

        <button onclick="closeModal()" id="btn-close-modal" class="hidden mt-10 bg-white text-gray-900 font-bold py-3 px-8 rounded-full shadow-lg hover:bg-gray-100 transition">
            Collect All & Close
        </button>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    // --- OPEN PACK LOGIC ---
    let currentPacks = <?php echo $pack_count; ?>;

    function openPack() {
        if (currentPacks <= 0) return;
        const btn = document.getElementById('btn-open-pack');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('api/open_pack.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentPacks = data.new_pack_count;
                document.getElementById('pack-count-display').innerText = currentPacks;
                showOpenAnimation(data.cards);
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = 'OPEN PACK';
            }
        })
        .catch(err => {
            console.error(err);
            alert("Network Error");
            btn.disabled = false;
            btn.innerHTML = 'OPEN PACK';
        });
    }

    function showOpenAnimation(cards) {
        const modal = document.getElementById('pack-modal');
        const scenePack = document.getElementById('scene-pack');
        const sceneCards = document.getElementById('scene-cards');
        const btnClose = document.getElementById('btn-close-modal');

        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.remove('opacity-0'), 10);

        scenePack.classList.remove('hidden');
        sceneCards.classList.add('hidden');
        btnClose.classList.add('hidden');
        sceneCards.innerHTML = '';

        cards.forEach((card) => {
            let borderClass = 'border-gray-300';
            let bgClass = 'bg-white';
            let glowEffect = '';
            
            if (card.rarity === 'R') { borderClass = 'border-blue-400'; glowEffect = 'shadow-[0_0_15px_rgba(59,130,246,0.5)]'; }
            if (card.rarity === 'SR') { borderClass = 'border-purple-500'; glowEffect = 'shadow-[0_0_20px_rgba(168,85,247,0.6)]'; }
            if (card.rarity === 'SSR') { borderClass = 'border-yellow-400'; glowEffect = 'shadow-[0_0_25px_rgba(234,179,8,0.8)] animate-pulse'; }

            const cardHTML = `
                <div class="pack-card-container w-full h-64 cursor-pointer perspective-1000" onclick="this.classList.toggle('flipped')">
                    <div class="card-inner w-full h-full relative transition-transform duration-700 transform-style-3d">
                        <div class="absolute w-full h-full bg-blue-600 rounded-xl border-4 border-white shadow-xl flex items-center justify-center backface-hidden">
                            <i class="fas fa-star text-white text-4xl opacity-50"></i>
                        </div>
                        <div class="absolute w-full h-full ${bgClass} rounded-xl border-4 ${borderClass} ${glowEffect} flex flex-col items-center p-2 rotate-y-180 backface-hidden overflow-hidden">
                            <div class="absolute top-2 right-2 text-xs font-bold px-2 py-0.5 bg-gray-900 text-white rounded">${card.rarity}</div>
                            <div class="w-full h-32 bg-gray-50 mb-2 rounded flex items-center justify-center overflow-hidden">
                                ${ card.image_path ? `<img src="${card.image_path}" class="object-cover w-full h-full">` : '<i class="fas fa-image text-gray-300 text-3xl"></i>' }
                            </div>
                            <h3 class="font-bold text-gray-800 text-sm text-center mt-2">${card.name}</h3>
                        </div>
                    </div>
                </div>
            `;
            sceneCards.insertAdjacentHTML('beforeend', cardHTML);
        });

        setTimeout(() => {
            scenePack.classList.add('hidden');
            sceneCards.classList.remove('hidden');
            btnClose.classList.remove('hidden');
            const cardElements = sceneCards.children;
            for (let i = 0; i < cardElements.length; i++) {
                setTimeout(() => { cardElements[i].classList.add('flipped'); }, i * 300 + 500);
            }
        }, 1500);
    }

    function closeModal() { location.reload(); }
</script>

</body>
</html>