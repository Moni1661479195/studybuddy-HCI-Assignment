<?php
require_once 'session.php';
require_once 'lib/matching.php';

// Prevent browser caching so Back button forces a fresh request (and triggers session check)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect legacy/share links to the new view_plan page
if (isset($_GET['view_plan_id'])) {
    $redirect_id = (int)$_GET['view_plan_id'];
    header("Location: view_plan.php?id=" . $redirect_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - My Study Plans</title>

        <script src="https://cdn.tailwindcss.com"></script>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
            <link rel="stylesheet" href="assets/css/modern_auth.css">
            <link rel="stylesheet" href="assets/css/study-plans.css">

<style>
    /* 1. Define new animations (fade in and scale up) */
    @keyframes modalFadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    @keyframes modalScaleUp {
        from {
            opacity: 0;
            transform: scale(0.95); /* Start from 95% size */
        }
        to {
            opacity: 1;
            transform: scale(1); /* Scale up to 100% */
        }
    }

    /* 2. Override .modal-overlay (from study-plans.css) */
    .modal-overlay {
        /* This is your new background: semi-transparent black + blur.
           It will override background: white; in study-plans.css
        */
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);

        /* Key: Add fade-in animation */
        animation: modalFadeIn 0.3s ease-out;
    }

    /* 3. Override .modal-content (from study-plans.css) */
    .modal-content {
        /* Key: Replace the invalid "slideUp" animation in your CSS file with the new "scale up" animation */
        animation: modalScaleUp 0.3s ease-out;
    }
</style>

    </head>
<body>
    <?php include 'header.php'; ?>

<div class="dashboard-container bg-gray-100 pt-32 pb-16">
<div class="dashboard-card bg-white rounded-2xl shadow-xl max-w-4xl mx-auto p-8 md:p-12">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 text-center mb-2">My Study Plans</h1>
<p class="text-lg text-gray-600 text-center mb-8">Organize your learning and track your progress.</p>

            <!-- Search Bar -->
            <div class="relative max-w-md mx-auto mb-8">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input 
                    type="text" 
                    id="planSearchInput" 
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-blue-300 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out" 
                    placeholder="Search your plans..."
                >
            </div>

            <div class="dashboard-section">
                <h2>Your Active Plans</h2>
                
                <!-- Loading state -->
                <div id="loading" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading your study plans...</p>
                </div>

                <!-- Plans container -->
                <div id="plans-container" class="study-plans-grid" style="display: none;"></div>

                <!-- Empty state -->
                <div id="empty-state" class="empty-state" style="display: none;">
                    <i class="fas fa-clipboard-list"></i>
                    <p>You haven't created any study plans yet.</p>
                    <p style="font-size: 0.95rem; color: #9ca3af;">Start planning your success today!</p>
                </div>

                <!-- Create new plan button -->
<div class="flex items-center justify-center gap-4">
    <button onclick="createNewPlan()" class="flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 hover:shadow-lg transition-all duration-300">
        <i class="fas fa-plus"></i> Create New Plan
    </button>
    <button onclick="openAiModal()" class="flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 text-white font-semibold rounded-lg shadow-md hover:bg-purple-700 hover:shadow-lg transition-all duration-300">
        <i class="fas fa-robot"></i> Create with AI
    </button>
</div>
                </div>
            </div>

            <p style="margin-top: 1.5rem; text-align: center;">
                <a href="dashboard.php" style="color: #667eea; text-decoration: none; font-weight: 500;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </p>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- ============================================ -->
    <!-- AI ASSISTANT MODAL -->
    <!-- ============================================ -->
    <div id="aiAssistantModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-robot"></i> AI Study Plan Assistant</h2>
                <button class="modal-close-btn" onclick="closeAiModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="aiPlanForm" onsubmit="handleAiCreatePlan(event)">
                <div class="modal-body">
                    <p class="text-gray-600 mb-6">Describe your learning goals, and our AI will generate a tailored study plan for you.</p>
                    
                    <div class="form-section">
                        <div class="form-group full-width">
                            <label for="aiSubject">What subject are you studying?</label>
                            <input type="text" id="aiSubject" placeholder="e.g., Advanced Calculus, World History, React.js" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="aiWeaknesses">What are your weak points or specific topics to focus on?</label>
                            <textarea id="aiWeaknesses" placeholder="e.g., Integration by parts, the French Revolution, state management" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="aiDurationValue">How long is your study period?</label>
                            <div class="flex items-center gap-2">
                                <input type="number" id="aiDurationValue" class="w-24" value="2" min="1" required>
                                <select id="aiDurationUnit" class="flex-1">
                                    <option value="day(s)">Day(s)</option>
                                    <option value="week(s)" selected>Week(s)</option>
                                    <option value="month(s)">Month(s)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                     <div class="validation-message" id="aiValidationMessage"></div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAiModal()">Cancel</button>
                        <button type="submit" class="btn-save bg-purple-600 hover:bg-purple-700" id="aiSaveBtn">
                            <i class="fas fa-magic"></i> Generate Plan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- ============================================ -->
    <!-- CREATE PLAN MODAL - Step 2 -->
    <!-- ============================================ -->
    <div id="createPlanModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Create New Study Plan</h2>
                <button class="modal-close-btn" onclick="closeCreateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="createPlanForm" onsubmit="handleCreatePlan(event)">
                <div class="modal-body">
                    <!-- Plan Basic Information -->
                    <div class="form-section">
                        <h3 class="form-section-title">Plan Information</h3>
                        <div class="plan-basic-info">
                            <div class="form-group">
                                <label for="planName">
                                    Plan Name <span style="color: #ef4444;">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="planName" 
                                    name="planName" 
                                    placeholder="e.g., Math Final Exam Preparation"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="planDueDate">
                                    Due Date <span style="color: #ef4444;">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    id="planDueDate" 
                                    name="planDueDate"
                                    required
                                >
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="planDescription">
                                    Description (Optional)
                                </label>
                                <textarea 
                                    id="planDescription" 
                                    name="planDescription"
                                    placeholder="Describe your study plan goals and objectives..."
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tasks Section -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            Tasks <span style="color: #ef4444;">* (At least 1 required)</span>
                        </h3>
                        
                        <div id="tasksContainer" class="tasks-container">
                            <!-- Task items will be added here dynamically -->
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <button type="button" class="add-task-btn" onclick="addTask()">
                                <i class="fas fa-plus"></i> Add Task
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <div class="validation-message" id="validationMessage"></div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeCreateModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn-save" id="saveBtn">
                            <i class="fas fa-save"></i> Create Plan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- VIEW/EDIT PLAN MODAL - Step 3 -->
    <!-- ============================================ -->
    <div id="viewPlanModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="viewModalTitle">
                    <i class="fas fa-clipboard-list"></i> <span id="viewPlanName">Plan Details</span>
                </h2>
                <button class="modal-close-btn" onclick="closeCreateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editPlanForm" onsubmit="handleUpdatePlan(event)">
                <input type="hidden" id="editPlanId" name="editPlanId">
                
                <div class="modal-body">
                    <!-- View Mode -->
                    <div id="viewMode">
                        <!-- Plan Information Display -->
                        <div class="view-section">
                            <div class="view-header">
                                <h3>Plan Information</h3>
                                <span id="viewStatusBadge" class="plan-status"></span>
                            </div>
                            
                            <div class="view-info-grid">
                                <div class="info-item">
                                    <label><i class="fas fa-heading"></i> Plan Name</label>
                                    <div id="viewPlanNameDisplay" class="info-value"></div>
                                </div>
                                
                                <div class="info-item">
                                    <label><i class="fas fa-calendar-alt"></i> Due Date</label>
                                    <div id="viewDueDateDisplay" class="info-value"></div>
                                </div>
                                
                                <div class="info-item full-width">
                                    <label><i class="fas fa-align-left"></i> Description</label>
                                    <div id="viewDescriptionDisplay" class="info-value"></div>
                                </div>
                                
                                <div class="info-item">
                                    <label><i class="fas fa-chart-line"></i> Progress</label>
                                    <div class="progress-display">
                                        <div class="progress-bar">
                                            <div id="viewProgressFill" class="progress-fill"></div>
                                        </div>
                                        <span id="viewProgressText" class="progress-text"></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <label><i class="fas fa-tasks"></i> Tasks Completed</label>
                                    <div id="viewTasksCompletedDisplay" class="info-value"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tasks List Display -->
                        <div class="view-section">
                            <h3>Tasks</h3>
                            <div id="viewTasksList" class="tasks-view-list">
                                <!-- Tasks will be inserted here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div id="editMode" style="display: none;">
                        <!-- Plan Basic Information -->
                        <div class="form-section">
                            <h3 class="form-section-title">Plan Information</h3>
                            <div class="plan-basic-info">
                                <div class="form-group">
                                    <label for="editPlanName">
                                        Plan Name <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="editPlanName" 
                                        name="editPlanName" 
                                        placeholder="e.g., Math Final Exam Preparation"
                                        required
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="editPlanDueDate">
                                        Due Date <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input 
                                        type="date" 
                                        id="editPlanDueDate" 
                                        name="editPlanDueDate"
                                        required
                                    >
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="editPlanDescription">
                                        Description (Optional)
                                    </label>
                                    <textarea 
                                        id="editPlanDescription" 
                                        name="editPlanDescription"
                                        placeholder="Describe your study plan goals and objectives..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tasks Section -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                Tasks <span style="color: #ef4444;">* (At least 1 required)</span>
                            </h3>
                            
                            <div id="editTasksContainer" class="tasks-container">
                                <!-- Task items will be added here dynamically -->
                            </div>
                            
                            <div style="margin-top: 1rem;">
                                <button type="button" class="add-task-btn" onclick="addEditTask()">
                                    <i class="fas fa-plus"></i> Add Task
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" id="btnSharePlan" class="btn-primary" onclick="openShareModal()">
                        <i class="fas fa-share-alt"></i> Share
                    </button>
                    <div class="validation-message" id="editValidationMessage"></div>
                    <div class="modal-actions">
                        <!-- View Mode Actions -->
                        <div id="viewModeActions">
                            <button type="button" class="btn-cancel" onclick="closeViewModal()">
                                Close
                            </button>
                            <button type="button" class="btn-edit" onclick="switchToEditMode()">
                                <i class="fas fa-edit"></i> Edit Plan
                            </button>
                            <button type="button" class="btn-danger" onclick="confirmDeletePlanFromModal()">
                                <i class="fas fa-trash"></i> Delete Plan
                            </button>
                        </div>
                        
                        <!-- Edit Mode Actions -->
                        <div id="editModeActions" style="display: none;">
                            <button type="button" class="btn-cancel" onclick="cancelEdit()">
                                Cancel
                            </button>
                            <button type="submit" class="btn-save" id="updateBtn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SHARE PLAN MODAL -->
    <!-- ============================================ -->
    <div id="sharePlanModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-share-alt"></i> Share Study Plan</h2>
                <button class="modal-close-btn" onclick="closeShareModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <p class="text-gray-600 mb-4">Select a friend or group to share this plan with:</p>
                
                <div class="form-group full-width">
                    <label for="shareRecipientType">Share with:</label>
                    <select id="shareRecipientType" onchange="toggleShareRecipientList()" class="w-full p-2 border rounded">
                        <option value="user">Friend</option>
                        <option value="group">Study Group</option>
                    </select>
                </div>

                <div class="form-group full-width mt-4">
                    <label for="shareRecipientId">Select Recipient:</label>
                    <select id="shareRecipientId" class="w-full p-2 border rounded">
                        <!-- Options populated via JS -->
                        <option value="">Loading...</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeShareModal()">Cancel</button>
                    <button type="button" class="btn-save" id="confirmShareBtn" onclick="handleSharePlan()">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/study-plans.js" defer></script>
    <script src="assets/js/responsive.js" defer></script>
    <script src="/assets/js/pomodoro.js" defer></script>
</body>
</html>