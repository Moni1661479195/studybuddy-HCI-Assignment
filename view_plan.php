<?php
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$plan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$plan_id) {
    header("Location: study-plans.php");
    exit();
}

// Fetch basic plan info for title/meta (SEO/UX)
$db = get_db();
try {
    $stmt = $db->prepare("SELECT name FROM study_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $plan_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan_info) {
        die("Study Plan not found.");
    }
} catch (Exception $e) {
    die("Error loading plan.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan: <?php echo htmlspecialchars($plan_info['name']); ?> - Study Buddy</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .page-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* Vertically center if content is short */
            padding: 2rem;
            width: 100%;
        }

        .plan-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 3rem;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Header */
        .plan-title {
            font-size: 2rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.2;
        }

        .plan-meta {
            display: flex;
            gap: 1.5rem;
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .meta-item i { color: #4f46e5; }

        /* Progress */
        .progress-container {
            margin-bottom: 2rem;
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }
        .progress-track {
            width: 100%;
            height: 10px;
            background-color: #e5e7eb;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4f46e5, #6366f1);
            width: 0%;
            transition: width 0.5s ease-out;
        }

        /* Description */
        .description-box {
            background-color: #f9fafb;
            border-left: 4px solid #4f46e5;
            padding: 1.25rem;
            border-radius: 0.5rem;
            color: #374151;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        /* Tasks List */
        .tasks-header {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .task-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        .task-item:last-child { border-bottom: none; }
        .task-item:hover { background-color: #fafafa; }

        .task-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            color: transparent;
            margin-top: 0.1rem;
        }
        
        .task-item.completed .task-icon {
            background-color: #10b981;
            border-color: #10b981;
            color: white;
        }

        .task-content { flex: 1; }
        
        .task-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.05rem;
            margin-bottom: 0.25rem;
        }
        .task-item.completed .task-title {
            text-decoration: line-through;
            color: #9ca3af;
        }

        .task-desc {
            font-size: 0.9rem;
            color: #6b7280;
        }
        .task-date {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Footer */
        .footer-actions {
            margin-top: 3rem;
            text-align: center;
        }

        .btn {
            padding: 0.75rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-primary {
            background: #4f46e5;
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }
        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }

        /* Top Bar */
        .top-bar {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back-link {
            color: #6b7280;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }
        .back-link:hover { color: #1f2937; }
    </style>
</head>
<body>
    <div class="top-bar" style="display: none;">
    </div>
    <div class="page-container">
        
        <div id="loading-state" class="text-center py-12">
            <i class="fas fa-circle-notch fa-spin text-4xl text-indigo-600 mb-4"></i>
            <p class="text-gray-500">Loading study plan...</p>
        </div>

        <div id="plan-content" class="plan-card" style="display: none;">
            <h1 class="plan-title" id="plan-name"></h1>
            
            <div class="plan-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="due-date"></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-tasks"></i>
                    <span id="task-count"></span>
                </div>
                <div class="meta-item" id="status-badge-container">
                    <!-- Status Badge -->
                </div>
            </div>

            <div class="progress-container">
                <div class="progress-header">
                    <span>Progress</span>
                    <span id="progress-text">0%</span>
                </div>
                <div class="progress-track">
                    <div id="progress-bar" class="progress-fill"></div>
                </div>
            </div>

            <div id="description-box" class="description-box" style="display: none;">
                <p id="plan-desc"></p>
            </div>

            <h2 class="tasks-header">Tasks Breakdown</h2>
            <div id="tasks-list" class="tasks-list">
                <!-- Tasks injected here -->
            </div>

            <div class="footer-actions">
                <!-- You could add 'Clone Plan' feature here later -->
                <button onclick="window.location.href='study-plans.php'" class="btn btn-primary">
                    Go to My Plans
                </button>
            </div>
        </div>

    </div>

<script>
    const planId = <?php echo $plan_id; ?>;

    document.addEventListener('DOMContentLoaded', loadPlan);

    async function loadPlan() {
        try {
            const response = await fetch(`api/study-plans.php?action=get_plan&plan_id=${planId}`);
            const data = await response.json();
            
            if (!data.success) {
                alert(data.message || "Failed to load plan.");
                window.location.href = 'study-plans.php';
                return;
            }

            renderPlan(data.plan);
        } catch (error) {
            console.error(error);
            alert("An error occurred.");
        }
    }

    function renderPlan(plan) {
        document.getElementById('loading-state').style.display = 'none';
        document.getElementById('plan-content').style.display = 'block';

        // Title
        document.getElementById('plan-name').textContent = plan.name;
        
        // Date Logic
        const dueDate = new Date(plan.due_date);
        const today = new Date();
        today.setHours(0,0,0,0);
        dueDate.setHours(0,0,0,0);
        
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        let dateText = "Due: " + dueDate.toLocaleDateString('en-US', options);
        
        // Status / Days remaining
        let statusHtml = '';
        if (plan.status === 'completed') {
            statusHtml = '<span style="color: #10b981; font-weight: 600;"><i class="fas fa-check-circle"></i> Completed</span>';
        } else if (dueDate < today) {
            statusHtml = '<span style="color: #ef4444; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Overdue</span>';
        } else {
            const diffTime = Math.abs(dueDate - today);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            statusHtml = `<span style="color: #f59e0b; font-weight: 600;"><i class="fas fa-clock"></i> ${diffDays} days left</span>`;
        }

        document.getElementById('due-date').textContent = dateText;
        document.getElementById('status-badge-container').innerHTML = statusHtml;
        document.getElementById('task-count').textContent = `${plan.total_tasks} Tasks`;

        // Progress
        document.getElementById('progress-bar').style.width = `${plan.progress}%`;
        document.getElementById('progress-text').textContent = `${plan.progress}%`;

        // Description
        if (plan.description) {
            document.getElementById('description-box').style.display = 'block';
            document.getElementById('plan-desc').textContent = plan.description;
        }

        // Tasks
        const tasksContainer = document.getElementById('tasks-list');
        tasksContainer.innerHTML = '';

        plan.tasks.forEach(task => {
            const taskDiv = document.createElement('div');
            taskDiv.className = `task-item ${task.is_completed ? 'completed' : ''}`;
            
            let dueDateHtml = '';
            if (task.due_date) {
                dueDateHtml = `<div class="task-date"><i class="fas fa-calendar-day"></i> ${task.due_date}</div>`;
            }

            taskDiv.innerHTML = `
                <div class="task-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="task-content">
                    <div class="task-title">${escapeHtml(task.task_name)}</div>
                    <div class="task-desc">${escapeHtml(task.task_description || '')}</div>
                    ${dueDateHtml}
                </div>
            `;
            tasksContainer.appendChild(taskDiv);
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

</body>
</html>
