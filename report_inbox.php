<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

$db = get_db();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id']) && isset($_POST['action'])) {
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['action'];
    $new_status = '';

    if ($action === 'resolve') {
        $new_status = 'resolved';
    } elseif ($action === 'dismiss') {
        $new_status = 'dismissed';
    }

    if (!empty($new_status)) {
        try {
            $stmt = $db->prepare("UPDATE reports SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $report_id]);
            header("Location: report_inbox.php?success=Report status updated.");
            exit();
        } catch (PDOException $e) {
            header("Location: report_inbox.php?error=Database error.");
            exit();
        }
    }
}

// Fetch all reports with user details
$stmt = $db->prepare("
    SELECT 
        r.id, r.reason, r.screenshot_path, r.created_at, r.status,
        reporter.id AS reporter_id,
        reporter.first_name AS reporter_first_name,
        reporter.last_name AS reporter_last_name,
        reported.id AS reported_user_id,
        reported.first_name AS reported_first_name,
        reported.last_name AS reported_last_name
    FROM reports r
    JOIN users reporter ON r.reporter_id = reporter.id
    JOIN users reported ON r.reported_user_id = reported.id
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group reports by status
$pending_reports = [];
$resolved_reports = [];
$dismissed_reports = [];

foreach ($reports as $report) {
    if ($report['status'] === 'pending') {
        $pending_reports[] = $report;
    } elseif ($report['status'] === 'resolved') {
        $resolved_reports[] = $report;
    } elseif ($report['status'] === 'dismissed') {
        $dismissed_reports[] = $report;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Inbox - Study Buddy</title>
    
    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
            body {
            font-family: 'Inter', sans-serif;
        }

        nav svg {
            width: 2.25rem !important;
            height: 2.25rem !important;
        }
        footer svg {
            width: 1.75rem !important;
            height: 1.75rem !important;
            max-width: 100%;
        }
        nav {
            z-index: 50 !important;
        }

        .modal {
    display: none;
    position: fixed;
    z-index: 2000 !important; 
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: hidden; 
    background-color: rgba(0, 0, 0, 0.9); 
    backdrop-filter: blur(5px); 
}
        .modal-content-img {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px;
            max-height: 90vh;
            object-fit: contain;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
        }
.close-modal {
    position: absolute;
    top: 30px;
    right: 30px;
    color: #f1f1f1;
    font-size: 50px; 
    font-weight: 300; 
    cursor: pointer;
    z-index: 2001;
    transition: color 0.2s;
}
.close-modal:hover {
    color: #bbb;
}

        .detail-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        
        .screenshot-thumbnail {
            transition: transform 0.2s;
        }
        .screenshot-thumbnail:hover {
            transform: scale(1.05);
        }
        
        .tab-link.active {
            color: #2563eb; /* blue-600 */
            border-bottom-color: #2563eb;
        }
        
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    
    <?php include 'header.php'; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex-grow w-full" style="margin-top: 180px; padding-bottom: 40px;">        
        <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
            
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Report Inbox</h1>
                <p class="text-gray-500 mt-2">Review and manage user reports.</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="flex border-b border-gray-200 mb-6">
                <div class="tab-link active py-3 px-6 cursor-pointer font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-colors" data-tab="pending">
                    Pending <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo count($pending_reports); ?></span>
                </div>
                <div class="tab-link py-3 px-6 cursor-pointer font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-colors" data-tab="resolved">
                    Resolved <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo count($resolved_reports); ?></span>
                </div>
                <div class="tab-link py-3 px-6 cursor-pointer font-semibold text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-colors" data-tab="dismissed">
                    Dismissed <span class="ml-2 bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo count($dismissed_reports); ?></span>
                </div>
            </div>

            <?php 
            function render_report_table($reports, $status) {
                if (empty($reports)) {
                    echo '<div class="text-center py-12 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">';
                    echo '<i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>';
                    echo "<p>No {$status} reports found.</p>";
                    echo '</div>';
                    return;
                }
            ?>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reporter</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reported User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Screenshots</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <?php if ($status !== 'pending'): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <?php endif; ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reports as $report): ?>
                                <tr class="hover:bg-gray-50 transition-colors report-row cursor-pointer" data-id="<?php echo $report['id']; ?>">
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-blue-600 hover:underline">
                                                <?php echo htmlspecialchars($report['reporter_first_name'] . ' ' . $report['reporter_last_name']); ?>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-red-600 hover:underline">
                                            <?php echo htmlspecialchars($report['reported_first_name'] . ' ' . $report['reported_last_name']); ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($report['reason']); ?>">
                                            <?php echo htmlspecialchars($report['reason']); ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $screenshots = json_decode($report['screenshot_path'], true);
                                        if ($screenshots && is_array($screenshots)): ?>
                                            <div class="flex -space-x-2 overflow-hidden">
                                                <?php foreach (array_slice($screenshots, 0, 3) as $screenshot): ?>
                                                    <img src="<?php echo htmlspecialchars($screenshot); ?>" class="screenshot-thumbnail inline-block h-10 w-10 rounded-full ring-2 ring-white object-cover cursor-zoom-in" alt="Evidence">
                                                <?php endforeach; ?>
                                                <?php if(count($screenshots) > 3): ?>
                                                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full ring-2 ring-white bg-gray-100 text-xs font-medium text-gray-500">
                                                        +<?php echo count($screenshots) - 3; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">None</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, H:i', strtotime($report['created_at'])); ?>
                                    </td>

                                    <?php if ($status !== 'pending'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if($report['status'] == 'resolved'): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Resolved</span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Dismissed</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button class="text-blue-600 hover:text-blue-900">
                                            View <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                        </button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php
            }
            ?>

            <div id="pending" class="tab-content active">
                <?php render_report_table($pending_reports, 'pending'); ?>
            </div>
            <div id="resolved" class="tab-content">
                <?php render_report_table($resolved_reports, 'resolved'); ?>
            </div>
            <div id="dismissed" class="tab-content">
                <?php render_report_table($dismissed_reports, 'dismissed'); ?>
            </div>

        </div>
    </div>

    <div id="imageModal" class="modal">
    <span class="close-modal">&times;</span>
    <img class="modal-content-img" id="modalImage">
</div>

    <div id="reportDetailModal" class="detail-modal">
        <div class="bg-white w-11/12 max-w-3xl mx-auto mt-20 rounded-lg shadow-2xl overflow-hidden relative animate-fade-in-down">
            <button class="close-detail-modal absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <div id="reportDetailContent" class="max-h-[80vh] overflow-y-auto">
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Get elements
    const imageModal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const closeImageModal = document.querySelector('.close-modal');

    // 2. Check if elements exist
    if (!imageModal || !modalImg) {
        console.error("Error: Modal elements not found! Please check if HTML IDs are 'imageModal' and 'modalImage'");
        return;
    }

    const reportDetailModal = document.getElementById('reportDetailModal');
    const reportDetailContent = document.getElementById('reportDetailContent');
    const closeDetailModal = document.querySelector('.close-detail-modal');

    // --- Core Functions ---
    function initializeScreenshotModal(container) {
        const thumbnails = container.querySelectorAll('.screenshot-thumbnail');
        console.log("Thumbnails found:", thumbnails.length);

        thumbnails.forEach(item => {
            item.addEventListener('click', event => {
                event.stopPropagation();
                
                // Attempt to find the img tag inside
                const imgElement = item.querySelector('img');
                
                if (imgElement) {
                    console.log("Click successful, image source is:", imgElement.src);
                    modalImg.src = imgElement.src;
                    imageModal.style.display = "block";
                } else {
                    console.error("Error: Thumbnail clicked, but no <img> tag found inside!");
                    console.log("Clicked element structure:", item.innerHTML);
                }
            });
        });
    }

    function initializeReportRowClick(container) {
            container.querySelectorAll('.report-row').forEach(row => {
            row.addEventListener('click', event => {
                if (event.target.closest('a') || event.target.closest('button') || event.target.closest('.screenshot-thumbnail')) {
                    return;
                }
                const reportId = row.dataset.id;
                
                reportDetailModal.style.display = 'block';
                // Loading indicator
                reportDetailContent.innerHTML = '<div class="p-12 text-center text-gray-500"><i class="fas fa-spinner fa-spin fa-3x mb-4"></i><p>Loading...</p></div>';

                fetch(`get_report_details.php?id=${reportId}`)
                    .then(response => response.text())
                    .then(html => {
                        reportDetailContent.innerHTML = html;
                        // Re-bind click events after content is loaded
                        initializeScreenshotModal(reportDetailContent);
                    })
                    .catch(err => {
                        console.error('Failed to load details:', err);
                    });
            });
        });
    }

    // Initialization
    initializeScreenshotModal(document.body);
    initializeReportRowClick(document.body);

    // Close Logic
    if (closeImageModal) {
        closeImageModal.addEventListener('click', () => imageModal.style.display = "none");
    }
    if (closeDetailModal) {
        closeDetailModal.addEventListener('click', () => reportDetailModal.style.display = "none");
    }

    window.addEventListener('click', event => {
        if (event.target == imageModal) imageModal.style.display = "none";
        if (event.target == reportDetailModal) reportDetailModal.style.display = "none";
    });

    // Tab Switching Logic
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
        });
    });
});
</script>
</body>
</html>