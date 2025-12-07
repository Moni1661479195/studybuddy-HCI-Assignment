<?php
require_once 'session.php';
require_once 'lib/db.php';

// 1. Security & Validation
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo '<div class="p-8 text-center text-red-500">Access Denied</div>';
    exit();
}

if (!isset($_GET['id'])) {
    echo '<div class="p-8 text-center text-red-500">Invalid Report ID</div>';
    exit();
}

$report_id = (int)$_GET['id'];
$db = get_db();

// 2. Fetch Report Data
$stmt = $db->prepare("
    SELECT 
        r.*,
        reporter.first_name AS reporter_first, reporter.last_name AS reporter_last, reporter.email AS reporter_email,
        reported.first_name AS reported_first, reported.last_name AS reported_last, reported.email AS reported_email
    FROM reports r
    JOIN users reporter ON r.reporter_id = reporter.id
    JOIN users reported ON r.reported_user_id = reported.id
    WHERE r.id = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo '<div class="p-8 text-center text-gray-500">Report not found.</div>';
    exit();
}

// 3. Prepare Data
$screenshots = json_decode($report['screenshot_path'], true);
$status_colors = [
    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'resolved' => 'bg-green-100 text-green-800 border-green-200',
    'dismissed' => 'bg-gray-100 text-gray-800 border-gray-200'
];
$status_class = $status_colors[$report['status']] ?? 'bg-gray-100 text-gray-800';

?>

<div class="bg-white">
    
    <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Report Details <span class="text-gray-400 text-lg">#<?php echo $report['id']; ?></span></h2>
            <p class="text-sm text-gray-500 mt-1">Submitted on <?php echo date('F j, Y \a\t g:i A', strtotime($report['created_at'])); ?></p>
        </div>
        <span class="px-4 py-1.5 rounded-full text-sm font-bold border <?php echo $status_class; ?> uppercase tracking-wide shadow-sm">
            <?php echo htmlspecialchars($report['status']); ?>
        </span>
    </div>

    <div class="p-8 space-y-8">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50/50 rounded-xl p-5 border border-blue-100 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-16 h-16 bg-blue-100 rounded-bl-full -mr-8 -mt-8 z-0 opacity-50"></div>
                <div class="relative z-10">
                    <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-3 flex items-center">
                        <i class="fas fa-user-shield mr-2"></i> Reporter
                    </h3>
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 font-bold text-xl">
                            <?php echo strtoupper(substr($report['reporter_first'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 text-lg leading-tight">
                                <?php echo htmlspecialchars($report['reporter_first'] . ' ' . $report['reporter_last']); ?>
                            </p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($report['reporter_email']); ?></p>
                        </div>
                    </div>
                    <a href="user_profile.php?id=<?php echo $report['reporter_id']; ?>" class="mt-4 inline-block text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                        View Profile &rarr;
                    </a>
                </div>
            </div>

            <div class="bg-red-50/50 rounded-xl p-5 border border-red-100 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-16 h-16 bg-red-100 rounded-bl-full -mr-8 -mt-8 z-0 opacity-50"></div>
                <div class="relative z-10">
                    <h3 class="text-xs font-bold text-red-600 uppercase tracking-wider mb-3 flex items-center">
                        <i class="fas fa-user-times mr-2"></i> Reported User
                    </h3>
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-full bg-red-200 flex items-center justify-center text-red-700 font-bold text-xl">
                            <?php echo strtoupper(substr($report['reported_first'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 text-lg leading-tight">
                                <?php echo htmlspecialchars($report['reported_first'] . ' ' . $report['reported_last']); ?>
                            </p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($report['reported_email']); ?></p>
                        </div>
                    </div>
                    <a href="user_profile.php?id=<?php echo $report['reported_user_id']; ?>" class="mt-4 inline-block text-xs font-medium text-red-600 hover:text-red-800 hover:underline">
                        View Profile &rarr;
                    </a>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-quote-left mr-2 text-gray-400"></i> Reason for Report
            </h3>
            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 text-gray-700 leading-relaxed italic relative shadow-inner">
                "<?php echo nl2br(htmlspecialchars($report['reason'])); ?>"
            </div>
        </div>

        <div>
            <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-images mr-2 text-gray-400"></i> Evidence Screenshots
            </h3>
            <?php if ($screenshots && is_array($screenshots) && count($screenshots) > 0): ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($screenshots as $screenshot): ?>
                        <div class="group relative aspect-square rounded-lg overflow-hidden border border-gray-200 shadow-sm cursor-pointer screenshot-thumbnail bg-gray-100">
                            <img src="<?php echo htmlspecialchars($screenshot); ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" alt="Evidence">
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                                <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 transform scale-75 group-hover:scale-100 transition-all"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-300 text-gray-400">
                    <i class="far fa-image text-3xl mb-2"></i>
                    <p class="text-sm">No screenshots provided.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-lg">
        <button onclick="document.querySelector('.close-detail-modal').click()" class="px-6 py-2.5 rounded-lg text-gray-600 font-medium hover:bg-gray-200 transition-colors">
            Close
        </button>
        
        <?php if ($report['status'] === 'pending'): ?>
            <form method="POST" action="report_inbox.php" class="inline-flex gap-3">
                <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                
                <button type="submit" name="action" value="dismiss" class="px-6 py-2.5 rounded-lg bg-white border border-gray-300 text-gray-700 font-medium shadow-sm hover:bg-gray-50 hover:text-red-600 transition-all flex items-center" onclick="return confirm('Dismiss this report?');">
                    <i class="fas fa-times mr-2"></i> Dismiss
                </button>
                
                <button type="submit" name="action" value="resolve" class="px-6 py-2.5 rounded-lg bg-blue-600 text-white font-bold shadow-md hover:bg-blue-700 hover:shadow-lg transform hover:-translate-y-0.5 transition-all flex items-center" onclick="return confirm('Mark as resolved?');">
                    <i class="fas fa-check mr-2"></i> Resolve Issue
                </button>
            </form>
        <?php else: ?>
            <span class="inline-flex items-center px-4 py-2 text-sm text-gray-500 italic">
                This report has been <?php echo $report['status']; ?>.
            </span>
        <?php endif; ?>
    </div>

</div>