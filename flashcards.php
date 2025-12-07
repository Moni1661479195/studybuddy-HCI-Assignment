<?php
// flashcards.php - Mission, Rewards & Showcase

require_once 'session.php';
require_once 'lib/db.php';
require_once 'includes/TaskLogic.php'; 

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db();

// 1. Fetch Tasks
$tasks = getUserTasks($db, $user_id);
$daily_tasks = $tasks['daily'];
$weekly_tasks = $tasks['weekly'];

// 2. Fetch Card Pack Count
$stmt = $db->prepare("SELECT card_packs FROM user_inventory WHERE user_id = ?");
$stmt->execute([$user_id]);
$pack_count = $stmt->fetchColumn() ?: 0;

// 3. Fetch "Showcase" Cards (Top 6 rarest cards)
$showcase_sql = "SELECT c.* FROM user_cards uc 
                 JOIN cards c ON uc.card_id = c.id 
                 WHERE uc.user_id = ? 
                 ORDER BY FIELD(c.rarity, 'SSR', 'SR', 'R', 'N'), uc.obtained_at DESC 
                 LIMIT 6";
$stmt = $db->prepare($showcase_sql);
$stmt->execute([$user_id]);
$showcase_cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Timers
$daily_timer = getTimeUntilRefresh('daily');
$weekly_timer = getTimeUntilRefresh('weekly');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Center - Study Buddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* --- Animations --- */
        .perspective-1000 { perspective: 1000px; }
        .transform-style-3d { transform-style: preserve-3d; }
        .backface-hidden { backface-visibility: hidden; }
        .rotate-y-180 { transform: rotateY(180deg); }
        .card-inner { transition: transform 0.6s; transform-style: preserve-3d; }
        .pack-card-container.flipped .card-inner { transform: rotateY(180deg); }
        
        /* Rarity Styling */
        .rarity-N { border-color: #e5e7eb; }
        .rarity-R { border-color: #3b82f6; box-shadow: 0 0 8px rgba(59, 130, 246, 0.15); }
        .rarity-SR { border-color: #a855f7; box-shadow: 0 0 12px rgba(168, 85, 247, 0.3); }
        .rarity-SSR { border-color: #eab308; box-shadow: 0 0 15px rgba(234, 179, 8, 0.5); animation: pulse-gold 3s infinite; }
        @keyframes pulse-gold { 0% { box-shadow: 0 0 10px rgba(234, 179, 8, 0.4); } 50% { box-shadow: 0 0 20px rgba(234, 179, 8, 0.7); } 100% { box-shadow: 0 0 10px rgba(234, 179, 8, 0.4); } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

<?php include 'header.php'; ?>

<main class="flex-grow container mx-auto px-4 mt-24 md:mt-32 mb-12">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
                <div class="bg-blue-600 p-4 text-white flex justify-between items-center">
                    <div>
                        <h2 class="font-bold text-sm"><i class="fas fa-sun mr-2"></i> Daily Tasks</h2>
                    </div>
                    <div class="text-right text-xs">
                        <p class="opacity-80">Resets in</p>
                        <p class="font-mono font-bold"><?php echo $daily_timer; ?></p>
                    </div>
                </div>
                <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                    <?php foreach ($daily_tasks as $task): ?>
                        <div class="p-3">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-semibold text-gray-700 truncate w-32"><?php echo htmlspecialchars($task['title']); ?></span>
                                <?php if($task['is_completed']): ?>
                                    <i class="fas fa-check-circle text-green-500 text-xs"></i>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400"><?php echo $task['current_value']; ?>/<?php echo $task['target_value']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1">
                                <div class="bg-blue-500 h-1 rounded-full" style="width: <?php echo ($task['target_value'] > 0) ? min(100, ($task['current_value']/$task['target_value'])*100) : 0; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="p-2 text-center bg-gray-50 border-t border-gray-100">
                         <a href="tasks.php" class="text-xs font-bold text-blue-500 hover:text-blue-700">Claim Rewards &rarr;</a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
                <div class="bg-purple-600 p-4 text-white flex justify-between items-center">
                    <div>
                        <h2 class="font-bold text-sm"><i class="fas fa-calendar-week mr-2"></i> Weekly</h2>
                    </div>
                    <div class="text-right text-xs">
                         <p class="font-mono font-bold"><?php echo $weekly_timer; ?></p>
                    </div>
                </div>
                <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                    <?php foreach ($weekly_tasks as $task): ?>
                        <div class="p-3">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-semibold text-gray-700 truncate w-32"><?php echo htmlspecialchars($task['title']); ?></span>
                                <?php if($task['is_completed']): ?>
                                    <i class="fas fa-star text-purple-500 text-xs"></i>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400"><?php echo $task['current_value']; ?>/<?php echo $task['target_value']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1">
                                <div class="bg-purple-500 h-1 rounded-full" style="width: <?php echo ($task['target_value'] > 0) ? min(100, ($task['current_value']/$task['target_value'])*100) : 0; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-6">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6 min-h-[500px]">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">🏆 Top Collection Showcase</h2>
                    <p class="text-gray-500 text-sm">Your rarest and most recent discoveries</p>
                </div>

                <?php if (empty($showcase_cards)): ?>
                    <div class="flex flex-col items-center justify-center h-64 text-gray-400 border-2 border-dashed border-gray-200 rounded-xl">
                        <i class="fas fa-layer-group text-5xl mb-4 text-gray-200"></i>
                        <p>No cards on display.</p>
                        <p class="text-sm">Open packs to fill your showcase!</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-3 gap-4">
                        <?php foreach ($showcase_cards as $card): ?>
                            <div class="bg-white rounded-lg border-2 rarity-<?php echo $card['rarity']; ?> p-2 shadow-sm hover:shadow-md transition transform hover:-translate-y-1 cursor-default">
                                <div class="aspect-w-1 aspect-h-1 w-full bg-gray-50 rounded mb-2 overflow-hidden flex items-center justify-center">
                                    <?php if ($card['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($card['image_path']); ?>" class="object-cover w-full h-full">
                                    <?php else: ?>
                                        <i class="fas fa-image text-gray-300"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="text-center">
                                    <div class="inline-block px-2 py-0.5 rounded text-[10px] font-bold mb-1
                                        <?php 
                                            if($card['rarity']=='N') echo 'bg-gray-200 text-gray-600';
                                            elseif($card['rarity']=='R') echo 'bg-blue-100 text-blue-600';
                                            elseif($card['rarity']=='SR') echo 'bg-purple-100 text-purple-600';
                                            elseif($card['rarity']=='SSR') echo 'bg-yellow-100 text-yellow-700';
                                        ?>">
                                        <?php echo $card['rarity']; ?>
                                    </div>
                                    <h4 class="text-xs font-bold text-gray-800 truncate"><?php echo htmlspecialchars($card['name']); ?></h4>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php for($i = count($showcase_cards); $i < 6; $i++): ?>
                            <div class="bg-gray-50 rounded-lg border-2 border-dashed border-gray-200 p-2 flex items-center justify-center opacity-50">
                                <i class="fas fa-plus text-gray-300"></i>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-8 text-center">
                    <a href="my_cards.php" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-800 font-medium transition">
                        View Full Collection <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>


        

        <div class="lg:col-span-3 space-y-6">
            
            <div class="bg-white rounded-xl shadow-md p-6 text-center border border-gray-200">
                <h3 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-4">Inventory</h3>
                <div class="flex justify-center items-baseline gap-2 mb-6">
                    <i class="fas fa-box text-yellow-500 text-2xl"></i>
                    <span class="text-4xl font-extrabold text-gray-800" id="pack-count-display"><?php echo $pack_count; ?></span>
                    <span class="text-lg text-gray-500">Packs</span>
                </div>
                <button onclick="openPack()" 
                        id="btn-open-pack"
                        <?php echo $pack_count > 0 ? '' : 'disabled'; ?>
                        class="w-full bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                    OPEN PACK
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <h3 class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-4">Stats</h3>
                <div class="space-y-3">
                    <?php 
                        // Count total cards (optional simple query)
                        $total_cards_sql = "SELECT count(*) FROM user_cards WHERE user_id = $user_id";
                        $total_collected = $db->query($total_cards_sql)->fetchColumn();
                    ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Cards Collected</span>
                        <span class="font-bold text-gray-800"><?php echo $total_collected; ?></span>
                    </div>
                     <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Completion</span>
                        <span class="font-bold text-blue-600">--%</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</main>

<div id="pack-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90 hidden opacity-0 transition-opacity duration-300">
    <div class="w-full max-w-5xl px-4 text-center">
        <div id="scene-pack" class="flex flex-col items-center justify-center">
            <div class="text-white text-2xl font-bold mb-8 animate-pulse">Opening Pack...</div>
            <i class="fas fa-box text-9xl text-yellow-500 animate-bounce"></i>
        </div>
        <div id="scene-cards" class="hidden grid grid-cols-2 md:grid-cols-5 gap-4"></div>
        <button onclick="closeModal()" id="btn-close-modal" class="hidden mt-10 bg-white text-gray-900 font-bold py-3 px-8 rounded-full shadow-lg hover:bg-gray-100 transition">
            Collect All & Close
        </button>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    let currentPacks = <?php echo $pack_count; ?>;

    function openPack() {
        if (currentPacks <= 0) return;
        const btn = document.getElementById('btn-open-pack');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opening...';

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