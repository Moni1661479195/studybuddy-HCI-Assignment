<?php
// tasks.php - Updated with Claim Logic

require_once 'session.php';
require_once 'lib/db.php';
require_once 'includes/TaskLogic.php'; 

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$db = get_db(); // Use the standard PDO connection

// 1. Fetch Task Data using the PDO connection
$tasks = getUserTasks($db, $user_id);
$daily_tasks = $tasks['daily'];
$weekly_tasks = $tasks['weekly'];

// 2. Calculate Daily Task Progress
$daily_completed_count = 0;
$daily_is_claimed = false;

foreach ($daily_tasks as $dt) {
    if ($dt['is_completed']) $daily_completed_count++;
    if ($dt['is_claimed']) $daily_is_claimed = true;
}

$daily_progress_percent = (count($daily_tasks) > 0) ? ($daily_completed_count / count($daily_tasks)) * 100 : 0;
$daily_all_done = (count($daily_tasks) > 0 && $daily_completed_count >= count($daily_tasks));

// 3. Get User Card Pack Count using PDO
$stmt = $db->prepare("SELECT card_packs FROM user_inventory WHERE user_id = ?");
$stmt->execute([$user_id]);
$my_packs = $stmt->fetchColumn();
if ($my_packs === false) {
    $my_packs = 0; // User might not have an inventory record yet
}

// 4. Get Countdown Timers
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
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">

<?php include 'header.php'; ?>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 mt-24 md:mt-32 mb-12">
    
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Mission Center</h1>
            <p class="text-gray-600">Complete tasks to earn Card Packs!</p>
        </div>
        <div class="bg-white px-4 py-2 rounded-lg shadow flex items-center gap-3">
            <i class="fas fa-box-open text-blue-500 text-xl"></i>
            <div>
                <p class="text-xs text-gray-500 font-bold uppercase">My Packs</p>
                <p class="font-bold text-lg leading-none" id="pack-count"><?php echo $my_packs; ?></p> 
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
        <div class="bg-blue-600 p-4 md:p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-calendar-day"></i> Daily Tasks
                </h2>
                <p class="text-blue-100 text-sm mt-1">Complete all 5 to get a Card Pack!</p>
            </div>
            <div class="text-right">
                <p class="text-blue-200 text-xs font-bold uppercase">Resets in</p>
                <p class="font-mono text-xl font-bold"><?php echo $daily_timer; ?></p>
            </div>
        </div>

        <div class="px-6 py-6 border-b border-gray-100">
            <div class="flex justify-between text-sm font-semibold text-gray-600 mb-2">
                <span>Daily Progress</span>
                <span class="<?php echo $daily_all_done ? 'text-green-600' : 'text-blue-600'; ?>">
                    <?php echo $daily_completed_count; ?> / 5 Completed
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-blue-500 h-4 rounded-full transition-all duration-500" style="width: <?php echo $daily_progress_percent; ?>%"></div>
            </div>
            
            <div class="mt-4 text-center">
                <?php if ($daily_is_claimed): ?>
                    <button disabled class="bg-gray-100 text-gray-400 font-bold py-2 px-6 rounded-lg cursor-not-allowed">
                        <i class="fas fa-check mr-2"></i> Daily Reward Claimed
                    </button>
                <?php elseif ($daily_all_done): ?>
                    <button onclick="claimDailyReward(this)" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-6 rounded-lg shadow transition transform hover:scale-105">
                        <i class="fas fa-gift mr-2"></i> Claim Daily Reward
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="divide-y divide-gray-100">
            <?php foreach ($daily_tasks as $task): ?>
                <div class="p-4 md:px-6 flex items-center gap-4 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 flex-shrink-0">
                        <?php echo $task['is_completed'] ? '<i class="fas fa-check"></i>' : '<i class="fas fa-circle-notch"></i>'; ?>
                    </div>
                    <div class="flex-grow">
                        <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($task['title']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($task['description']); ?></p>
                    </div>
                    <div class="text-sm font-medium whitespace-nowrap">
                        <?php echo $task['is_completed'] ? '<span class="text-green-500">Done</span>' : '<span class="text-gray-400">'.$task['current_value'].' / '.$task['target_value'].'</span>'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-purple-600 p-4 md:p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-calendar-week"></i> Weekly Tasks
                </h2>
                <p class="text-purple-100 text-sm mt-1">Get 1 Pack for each completed task.</p>
            </div>
            <div class="text-right">
                <p class="text-purple-200 text-xs font-bold uppercase">Resets in</p>
                <p class="font-mono text-xl font-bold"><?php echo $weekly_timer; ?></p>
            </div>
        </div>

        <div class="divide-y divide-gray-100">
            <?php foreach ($weekly_tasks as $task): 
                $percent = ($task['target_value'] > 0) ? min(100, ($task['current_value'] / $task['target_value']) * 100) : 0;
            ?>
                <div class="p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-center gap-4 justify-between mb-3">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-purple-50 flex items-center justify-center text-purple-500">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($task['title']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($task['description']); ?></p>
                            </div>
                        </div>

                        <div class="flex-shrink-0">
                            <?php if ($task['is_claimed']): ?>
                                <button disabled class="bg-gray-100 text-gray-400 px-4 py-2 rounded-lg text-sm font-bold cursor-not-allowed">
                                    Claimed
                                </button>
                            <?php elseif ($task['is_completed']): ?>
                                <button onclick="claimTaskReward(this, <?php echo $task['id']; ?>)" class="bg-yellow-400 hover:bg-yellow-500 text-yellow-900 px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition">
                                    <i class="fas fa-box-open mr-1"></i> Claim Pack
                                </button>
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm font-bold">
                                    In Progress
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5 mt-2">
                        <div class="bg-purple-500 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <div class="text-right text-xs text-gray-400 mt-1">
                        <?php echo $task['current_value']; ?> / <?php echo $task['target_value']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</main>

<?php include 'footer.php'; ?>

<script>
/**
 * Claim the Daily Reward (5/5 tasks done)
 */
function claimDailyReward(btn) {
    if(!confirm("Claim your Daily Reward?")) return;
    
    // Disable button to prevent double clicks
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';

    fetch('api/claim_reward.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'claim_daily' })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("🎉 Congrats! You got a Card Pack!");
            location.reload(); // Reload to update UI and show new pack count
        } else {
            alert("Error: " + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-gift mr-2"></i> Claim Daily Reward';
        }
    })
    .catch(err => {
        console.error(err);
        alert("Network error occurred.");
    });
}

/**
 * Claim a Weekly Reward (Single task done)
 */
function claimTaskReward(btn, taskId) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('api/claim_reward.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'claim_task', task_id: taskId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("🎉 Pack claimed!");
            location.reload(); 
        } else {
            alert("Error: " + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-box-open mr-1"></i> Claim Pack';
        }
    });
}
</script>

</body>
</html>