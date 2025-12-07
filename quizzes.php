<?php
require_once 'session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect legacy/share links to the immersive quiz page
if (isset($_GET['take_quiz_id'])) {
    $redirect_id = (int)$_GET['take_quiz_id'];
    header("Location: take_quiz.php?id=" . $redirect_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Arena - Study Buddy</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/modern_auth.css">
    <link rel="stylesheet" href="assets/css/study-plans.css">
    <link rel="stylesheet" href="assets/css/quizzes.css">
</head>
<body class="bg-gray-100">

<?php include 'header.php'; ?>

<div class="dashboard-container mt-24 md:mt-28">
        <div class="dashboard-card">
        <div id="notification-area" style="margin-bottom: 1rem;"></div>
        <div class="page-header">
            <h1>Quiz Arena</h1>
            <div class="header-actions flex gap-4">
                <button class="flex items-center justify-center gap-2 px-6 py-3 bg-purple-600 text-white font-semibold rounded-lg shadow-md hover:bg-purple-700 hover:shadow-lg transition-all duration-300" onclick="openAiQuizModal()">
                    <i class="fas fa-robot"></i> Create with AI
                </button>
                <button class="btn btn-primary btn-create-header" onclick="initCreateQuiz()">
                    <i class="fas fa-plus"></i> Create New Quiz
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="relative max-w-md mx-auto mb-8">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input 
                type="text" 
                id="quizSearchInput" 
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-blue-300 focus:shadow-outline-blue sm:text-sm transition duration-150 ease-in-out" 
                placeholder="Search quizzes by title or subject..."
            >
        </div>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab(this, 'public')">Public Quizzes</button>
            <button class="tab-button" onclick="switchTab(this, 'my-quizzes')">My Quizzes</button>
        </div>
        <div id="quiz-content">
            <div id="loading-state" class="loading"><i class="fas fa-spinner fa-spin"></i><p>Loading Quizzes...</p></div>
            <div id="quiz-grid" class="quiz-grid" style="display: none;"></div>
            <div id="empty-state" class="empty-state" style="display: none;"><p>No quizzes found.</p></div>
        </div>
    </div>
</div>

<!-- AI Quiz Generator Modal -->
<div id="aiQuizModal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <form id="aiQuizForm" onsubmit="handleAiCreateQuiz(event)">
            <div class="modal-header">
                <h2 id="ai-modal-title"><i class="fas fa-robot"></i> AI Quiz Generator</h2>
                <button type="button" class="modal-close" onclick="closeAiQuizModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <p class="text-gray-600 mb-6" style="color: #4b5563; margin-bottom: 1.5rem;">Describe what you want to learn, and the AI will generate a multiple-choice quiz for you.</p>
                <div class="form-group">
                    <label for="ai-quiz-subject">What subject are you studying?</label>
                    <input type="text" id="ai-quiz-subject" placeholder="e.g., WW2 History, JavaScript Fundamentals" required>
                </div>
                <div class="form-group">
                    <label for="ai-quiz-weaknesses">What are your weak points or specific topics to focus on?</label>
                    <textarea id="ai-quiz-weaknesses" rows="3" placeholder="e.g., The Pacific Theater, async/await, DOM manipulation" required></textarea>
                </div>
                <div class="form-group">
                    <label for="ai-quiz-num-questions">Number of Questions</label>
                    <input type="number" id="ai-quiz-num-questions" value="5" min="5" max="10" required>
                    <small class="text-gray-500">Enter a number between 5 and 10.</small>
                </div>
            </div>
            <div class="modal-footer">
                <div class="validation-message" id="ai-validation-message"></div>
                <div id="ai-quiz-actions" style="display: flex; justify-content: flex-end; width: 100%; gap: 0.5rem;">
                     <button type="button" class="btn" onclick="closeAiQuizModal()">Cancel</button>
                     <button type="submit" class="btn btn-primary" id="ai-generate-btn">
                        <i class="fas fa-magic"></i> Generate Quiz
                     </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Create/Edit Quiz Modal -->
<div id="createQuizModal" class="modal-overlay">
    <div class="modal-container">
        <form id="createQuizForm" onsubmit="event.preventDefault();">
            <div class="modal-header">
                <h2 id="modal-title">Create New Quiz</h2>
                <button type="button" class="modal-close" onclick="closeCreateQuizModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="step-1-metadata">
                    <div class="form-group"><label for="quiz-title">Quiz Title</label><input type="text" id="quiz-title" placeholder="e.g., Algebra Basics" required></div>
                    <div class="form-group"><label for="quiz-subject">Subject</label><input type="text" id="quiz-subject" placeholder="e.g., Mathematics" required></div>
                    <div class="form-group"><label for="quiz-skill-level">Target Skill Level</label><select id="quiz-skill-level"><option value="any">Any</option><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option></select></div>
                    <div class="form-group"><label for="quiz-description">Description</label><textarea id="quiz-description" rows="3" placeholder="A brief summary of what this quiz covers."></textarea></div>
                </div>
                <div id="step-2-questions" style="display: none;">
                    <div id="question-list"></div>
                    <button type="button" class="btn btn-primary" onclick="addQuestion()" id="add-question-btn"><i class="fas fa-plus"></i> Add Question</button>
                </div>
            </div>
            <div class="modal-footer">
                <div class="validation-message" id="validation-message"></div>
                <button id="next-step-btn" type="button" class="btn btn-primary" onclick="handleNextStep()">Next: Add Questions</button>
                <div id="final-actions" style="display: none;">
                    <button type="button" class="btn" id="save-changes-btn" onclick="saveQuiz('draft')">Save Changes</button>
                    <button type="button" class="btn btn-primary" id="publish-quiz-btn" onclick="saveQuiz('published')">Publish Quiz</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Take Quiz Modal -->
<div id="takeQuizModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header"><h2 id="take-quiz-title">Quiz in Progress</h2><button type="button" class="modal-close" onclick="closeTakeQuizModal()"><i class="fas fa-times"></i></button></div>
        <div class="modal-body">
            <div id="quiz-progress"></div>
            <div id="take-quiz-question"></div>
            <div id="answer-options" class="answer-options"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" id="prev-question-btn" onclick="renderQuestion(currentQuestionIndex - 1)">Previous</button>
            <button type="button" class="btn btn-primary" id="next-question-btn" onclick="renderQuestion(currentQuestionIndex + 1)">Next</button>
            <button type="button" class="btn btn-primary" id="finish-quiz-btn" onclick="finishQuiz()" style="display:none;">Finish & See Results</button>
        </div>
    </div>
</div>

<!-- Share Quiz Modal -->
<div id="shareQuizModal" class="modal-overlay" style="display: none;">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h2><i class="fas fa-share-alt"></i> Share Quiz</h2>
            <button type="button" class="modal-close" onclick="closeShareQuizModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p class="text-gray-600 mb-4">Select a friend or group to share this quiz with:</p>
            <div class="form-group">
                <label for="shareRecipientType">Share with:</label>
                <select id="shareRecipientType" onchange="toggleShareRecipientList()" class="w-full p-2 border rounded">
                    <option value="user">Friend</option>
                    <option value="group">Study Group</option>
                </select>
            </div>
            <div class="form-group mt-4">
                <label for="shareRecipientId">Select Recipient:</label>
                <select id="shareRecipientId" class="w-full p-2 border rounded">
                    <option value="">Loading...</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" onclick="closeShareQuizModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmShareBtn" onclick="handleShareQuiz()">
                <i class="fas fa-paper-plane"></i> Send
            </button>
        </div>
    </div>
</div>

<!-- Results Modal -->
<div id="resultsModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header"><h2>Quiz Results</h2><button type="button" class="modal-close" onclick="closeResultsModal()"><i class="fas fa-times"></i></button></div>
        <div class="modal-body">
            <div id="results-summary"><p>Your Score:</p><div id="results-score"></div></div>
            <div id="results-details"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-primary" onclick="closeResultsModal()">Close</button></div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    // --- STATE & UTILITY ---
let currentQuizId = null;
let currentShareQuizId = null; // For sharing
let questions = [];
let activeQuizData = null;
let userAnswers = {};
let currentQuestionIndex = 0;
let currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// --- NOTIFICATION SYSTEM ---
function showNotification(message, type = 'success') {
    const notificationArea = document.getElementById('notification-area');
    if (!notificationArea) return;

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${type}`;
    alertDiv.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i> ${message}`;
    
    notificationArea.innerHTML = ''; // Clear previous notifications
    notificationArea.appendChild(alertDiv);

    // Automatically hide after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// --- INITIALIZATION ---
document.addEventListener('DOMContentLoaded', () => {
    loadPublicQuizzes();

    // Search functionality
    const searchInput = document.getElementById('quizSearchInput');
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const activeTab = document.querySelector('.tab-button.active');
                if (activeTab && activeTab.textContent.includes('Public')) {
                    loadPublicQuizzes(e.target.value);
                } else {
                    loadMyQuizzes(e.target.value);
                }
            }, 300);
        });
    }

    // Check for shared quiz URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const takeQuizId = urlParams.get('take_quiz_id');
    if (takeQuizId) {
        takeQuiz(takeQuizId);
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// --- UI & TAB LOGIC ---
function switchTab(element, tabName) {
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');
    const searchInput = document.getElementById('quizSearchInput');
    const query = searchInput ? searchInput.value : '';
    
    if (tabName === 'public') {
        loadPublicQuizzes(query);
    } else if (tabName === 'my-quizzes') {
        loadMyQuizzes(query);
    }
}

async function loadPublicQuizzes(searchQuery = '') {
    const grid = document.getElementById('quiz-grid');
    const loading = document.getElementById('loading-state');
    const empty = document.getElementById('empty-state');
    grid.style.display = 'none';
    empty.style.display = 'none';
    loading.style.display = 'block';

    try {
        let url = 'api/quiz_api.php?action=get_public_quizzes';
        if (searchQuery) {
            url += '&search=' + encodeURIComponent(searchQuery);
        }
        const response = await fetch(url);
        const data = await response.json();
        loading.style.display = 'none';
        if (data.success && data.quizzes.length > 0) {
            grid.innerHTML = '';
            data.quizzes.forEach(quiz => grid.appendChild(createQuizCard(quiz)));
            grid.style.display = 'grid';
        } else {
            empty.style.display = 'block';
            empty.innerHTML = `<p>${data.message || 'No public quizzes available yet. Why not create the first one?'}</p>`;
        }
    } catch (error) {
        console.error('Error loading quizzes:', error);
        loading.style.display = 'none';
        empty.style.display = 'block';
        empty.innerHTML = '<p>Error loading quizzes. Please try again later.</p>';
    }
}

async function loadMyQuizzes(searchQuery = '') {
    const grid = document.getElementById('quiz-grid');
    const loading = document.getElementById('loading-state');
    const empty = document.getElementById('empty-state');
    grid.style.display = 'none';
    empty.style.display = 'none';
    loading.style.display = 'block';

    try {
        let url = 'api/quiz_api.php?action=get_my_quizzes';
        if (searchQuery) {
            url += '&search=' + encodeURIComponent(searchQuery);
        }
        const response = await fetch(url);
        const data = await response.json();
        loading.style.display = 'none';
        if (data.success && data.quizzes.length > 0) {
            grid.innerHTML = '';
            data.quizzes.forEach(quiz => grid.appendChild(createQuizCard(quiz, true))); // Pass true for isMyQuiz
            grid.style.display = 'grid';
        } else {
            empty.style.display = 'block';
            empty.innerHTML = `<p>${data.message || 'You have not created any quizzes yet.'}</p>`;
        }
    } catch (error) {
        console.error('Error loading my quizzes:', error);
        loading.style.display = 'none';
        empty.style.display = 'block';
        empty.innerHTML = '<p>Error loading your quizzes. Please try again later.</p>';
    }
}

function createQuizCard(quiz, isMyQuiz = false) {
    const card = document.createElement('div');
    card.className = 'quiz-card';
    const skillLevel = quiz.skill_level.charAt(0).toUpperCase() + quiz.skill_level.slice(1);
    const authorName = `${quiz.first_name || ''} ${quiz.last_name || ''}`.trim() || 'Unknown';
    let quizActionsHtml = '';

    if (isMyQuiz) {
        quizActionsHtml = `
            <button class="btn btn-primary" onclick="editQuiz(${quiz.id})">${quiz.status === 'draft' ? 'Continue Editing' : 'Edit Quiz'}</button>
            <button class="btn btn-danger" onclick="deleteQuiz(${quiz.id})">Delete</button>
        `;
    } else {
        quizActionsHtml = `<button class="btn btn-primary" onclick="takeQuiz(${quiz.id})">Start Quiz</button>`;
    }

    // Share button for everyone (if published) or owner
    // If it's a draft, maybe don't share? Assuming only published quizzes are listed in public, and my-quizzes shows drafts.
    // Let's allow sharing if it's published.
    let shareButton = '';
    if (quiz.status === 'published') {
        shareButton = `
            <button class="btn btn-sm" style="background-color: #10b981; color: white; padding: 0.5rem 0.8rem; border-radius: 0.5rem; margin-left: auto;" onclick="openShareQuizModal(${quiz.id})" title="Share Quiz">
                <i class="fas fa-share-alt"></i>
            </button>
        `;
    }

    card.innerHTML = `
        <div class="quiz-header"><h3>${escapeHtml(quiz.title)}</h3><div class="quiz-subject">${escapeHtml(quiz.subject)}</div></div>
        <p class="quiz-description">${escapeHtml(quiz.description) || 'No description provided.'}</p>
        <div class="quiz-meta">
            ${isMyQuiz ? `<span class="meta-item" title="Status"><i class="fas fa-info-circle"></i> ${quiz.status === 'draft' ? 'Draft' : 'Published'}</span>` : `<span class="meta-item" title="Author"><i class="fas fa-user"></i> ${escapeHtml(authorName)}</span>`}
            <span class="meta-item" title="Skill Level"><i class="fas fa-chart-line"></i> ${skillLevel}</span>
            <span class="meta-item" title="Questions"><i class="fas fa-question-circle"></i> ${quiz.question_count}</span>
        </div>
        <div class="quiz-actions" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
            ${quizActionsHtml}
            ${shareButton}
        </div>
    `;
    return card;
}

// --- SHARE QUIZ MODAL ---
function openShareQuizModal(quizId) {
    currentShareQuizId = quizId;
    const modal = document.getElementById('shareQuizModal');
    modal.style.removeProperty('display'); // Remove inline display:none
    modal.classList.add('active');
    toggleShareRecipientList();
}

function closeShareQuizModal() {
    const modal = document.getElementById('shareQuizModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
    currentShareQuizId = null;
}

function toggleShareRecipientList() {
    const type = document.getElementById('shareRecipientType').value;
    const select = document.getElementById('shareRecipientId');
    
    select.innerHTML = '<option>Loading...</option>';
    select.disabled = true;

    // Re-use the share_study_plan API for getting recipients as the logic is identical (friends/groups)
    // But to be safe and modular, let's assume we might need a specific one. 
    // For now, let's reuse share_study_plan.php?action=get_recipients since it is generic enough.
    fetch(`api/share_study_plan.php?action=get_recipients&type=${type}`)
        .then(res => res.json())
        .then(data => {
            select.innerHTML = '';
            select.disabled = false;
            
            if (data.success && data.recipients.length > 0) {
                data.recipients.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name;
                    select.appendChild(opt);
                });
            } else {
                select.innerHTML = '<option value="">No recipients found</option>';
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error loading list</option>';
        });
}

function handleShareQuiz() {
    if (!currentShareQuizId) return;
    
    const type = document.getElementById('shareRecipientType').value;
    const recipientId = document.getElementById('shareRecipientId').value;
    const btn = document.getElementById('confirmShareBtn');

    if (!recipientId) {
        showNotification('Please select a recipient', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    fetch('api/share_quiz.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            quiz_id: currentShareQuizId,
            recipient_type: type,
            recipient_id: recipientId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Quiz shared successfully!', 'success');
            closeShareQuizModal();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Network error. Please try again.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
    });
}

// Placeholder for editQuiz function
async function editQuiz(quizId) {
    showQuizModal(); // Open the modal first

    const response = await fetch(`api/quiz_api.php?action=get_quiz_for_editing&quiz_id=${quizId}`);
    const data = await response.json();

    if (!data.success) {
        showNotification('Error loading quiz for editing: ' + data.message, 'error');
        closeCreateQuizModal(); // Close modal on error
        return;
    }

    const quiz = data.quiz;
    currentQuizId = quiz.id;
    questions = quiz.questions.map(q => ({
        id: `q_${q.id}`, // Ensure unique IDs for frontend management
        text: q.question_text,
        options: q.options.map(opt => ({
            text: opt.text,
            is_correct: opt.is_correct
        }))
    }));

    // Populate metadata fields
    document.getElementById('quiz-title').value = quiz.title;
    document.getElementById('quiz-subject').value = quiz.subject;
    document.getElementById('quiz-skill-level').value = quiz.skill_level;
    document.getElementById('quiz-description').value = quiz.description;

    // Set modal title
    document.getElementById('modal-title').textContent = `Edit Quiz: ${escapeHtml(quiz.title)} (${quiz.status === 'draft' ? 'Draft' : 'Published'})`;

    // Switch to questions step
    document.getElementById('step-1-metadata').style.display = 'none';
    document.getElementById('step-2-questions').style.display = 'block';
    document.getElementById('next-step-btn').style.display = 'none';
    document.getElementById('final-actions').style.display = 'flex';

    // Set initial state of buttons for editing
    document.getElementById('save-changes-btn').textContent = 'Save Changes';
    if (quiz.status === 'published') {
        document.getElementById('publish-quiz-btn').style.display = 'none'; // Hide publish button if already published
    } else {
        document.getElementById('publish-quiz-btn').style.display = 'inline-flex';
    }

    renderQuestions(); // Render the loaded questions
}

async function deleteQuiz(quizId) {
    if (!confirm('Are you sure you want to delete this quiz? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch('api/quiz_api.php?action=delete_quiz', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `quiz_id=${quizId}`
        });
        const data = await response.json();

        if (data.success) {
            showNotification('Quiz deleted successfully!', 'success');
            loadMyQuizzes(); // Reload my quizzes after deletion
        } else {
            showNotification('Error deleting quiz: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting quiz:', error);
        showNotification('An error occurred while deleting the quiz.', 'error');
    }
}

// --- CREATE QUIZ MODAL LOGIC ---
function showQuizModal() {
    document.getElementById('createQuizModal').classList.add('active');
    const modalBody = document.querySelector('#createQuizModal .modal-body');
    modalBody.style.overflowY = 'auto';
    modalBody.style.maxHeight = '60vh';
    document.getElementById('validation-message').textContent = '';
    document.getElementById('validation-message').classList.remove('active'); // Ensure validation message is hidden initially
}

function initCreateQuiz() {
    document.getElementById('modal-title').textContent = 'Create New Quiz';
    document.getElementById('step-1-metadata').style.display = 'block';
    document.getElementById('step-2-questions').style.display = 'none';
    document.getElementById('next-step-btn').style.display = 'inline-flex';
    document.getElementById('final-actions').style.display = 'flex'; // Ensure final-actions is visible
    document.getElementById('question-list').innerHTML = '';
    document.getElementById('createQuizForm').reset();
    questions = [];
    currentQuizId = null;
    addQuestion();
    showQuizModal();

    // Set initial state of buttons for new quiz
    document.getElementById('save-changes-btn').textContent = 'Save as Draft';
    document.getElementById('publish-quiz-btn').style.display = 'inline-flex';
}

function closeCreateQuizModal() { document.getElementById('createQuizModal').classList.remove('active'); }

async function handleNextStep() {
    const title = document.getElementById('quiz-title').value.trim();
    const subject = document.getElementById('quiz-subject').value.trim();
    if (!title || !subject) {
        document.getElementById('validation-message').textContent = 'Quiz title and subject are required.';
        document.getElementById('validation-message').classList.add('active');
        return;
    }
    document.getElementById('validation-message').textContent = '';
    document.getElementById('validation-message').classList.remove('active');

    if (!currentQuizId) {
        const response = await fetch('api/quiz_api.php?action=create_draft_quiz', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title: title, subject: subject, description: document.getElementById('quiz-description').value.trim(), skill_level: document.getElementById('quiz-skill-level').value })
        });
        const data = await response.json();
        if (!data.success) {
            document.getElementById('validation-message').textContent = 'Error: ' + (data.message || 'Failed to create quiz draft.');
            document.getElementById('validation-message').classList.add('active');
            return;
        }
        currentQuizId = data.quiz_id;
    }

    document.getElementById('step-1-metadata').style.display = 'none';
    document.getElementById('step-2-questions').style.display = 'block';
    document.getElementById('next-step-btn').style.display = 'none';
    document.getElementById('final-actions').style.display = 'flex';
    // Set initial state of buttons for new quiz
    document.getElementById('save-changes-btn').textContent = 'Save as Draft';
    document.getElementById('publish-quiz-btn').style.display = 'inline-flex';
}

function addQuestion() {
    if (questions.length >= 10) { showNotification('Maximum of 10 questions reached.', 'info'); return; }
    const questionId = `q_${Date.now()}`;
    // Initialize with 3 options
    questions.push({ id: questionId, text: '', options: [{text: '', is_correct: true}, {text: '', is_correct: false}, {text: '', is_correct: false}] });
    renderQuestions();
}

function removeQuestion(id) {
    if (questions.length <= 1) { showNotification('You must have at least 1 question.', 'info'); return; } // Changed minimum questions
    questions = questions.filter(q => q.id !== id);
    renderQuestions();
}

function renderQuestions() {
    const container = document.getElementById('question-list');
    container.innerHTML = '';
    questions.forEach((q, index) => {
        const questionEl = document.createElement('div');
        questionEl.className = 'question-card';
        let optionsHtml = q.options.map((opt, optIndex) => `
            <div class="option-group">
                <input type="radio" name="correct_${q.id}" ${opt.is_correct ? 'checked' : ''} onchange="updateCorrectAnswer('${q.id}', ${optIndex})">
                <input type="text" placeholder="Option ${optIndex + 1}" value="${escapeHtml(opt.text)}" oninput="updateOptionText('${q.id}', ${optIndex}, this.value)">
                ${q.options.length > 2 ? `<button type="button" class="btn btn-danger btn-sm" onclick="removeOption('${q.id}', ${optIndex})"><i class="fas fa-minus"></i></button>` : ''}
            </div>`).join('');
        
        const addOptionButton = q.options.length < 4 ? `<button type="button" class="btn btn-primary btn-sm" onclick="addOption('${q.id}')"><i class="fas fa-plus"></i> Add Option</button>` : '';

        questionEl.innerHTML = `
            <div class="question-header"><h4>Question ${index + 1}</h4><button type="button" class="btn btn-danger" onclick="removeQuestion('${q.id}')"><i class="fas fa-trash"></i></button></div>
            <div class="form-group"><textarea rows="2" placeholder="Enter your question..." oninput="updateQuestionText('${q.id}', this.value)" required>${escapeHtml(q.text)}</textarea></div>
            <div><label>Options (select the correct one)</label>${optionsHtml}</div>
            <div style="margin-top: 1rem;">${addOptionButton}</div>`;
        container.appendChild(questionEl);
    });
    document.getElementById('add-question-btn').style.display = questions.length < 10 ? 'inline-flex' : 'none';
}

function updateQuestionText(id, text) { questions.find(q => q.id === id).text = text; }
function updateOptionText(id, optIndex, text) { questions.find(q => q.id === id).options[optIndex].text = text; }
function updateCorrectAnswer(id, correctIndex) { questions.find(q => q.id === id).options.forEach((o, i) => o.is_correct = i === correctIndex); }

function addOption(questionId) {
    const question = questions.find(q => q.id === questionId);
    if (question && question.options.length < 4) {
        question.options.push({text: '', is_correct: false});
        renderQuestions();
    }
}

function removeOption(questionId, optIndex) {
    const question = questions.find(q => q.id === questionId);
    if (question && question.options.length > 2) {
        question.options.splice(optIndex, 1);
        // Ensure at least one option is correct if the removed one was correct
        if (!question.options.some(opt => opt.is_correct) && question.options.length > 0) {
            question.options[0].is_correct = true;
        }
        renderQuestions();
    }
}

async function saveQuiz(status) {
    const validationDiv = document.getElementById('validation-message');
    try {
        if (questions.length < 5) { validationDiv.textContent = 'You must have at least 5 questions.'; validationDiv.classList.add('active'); return; }
        for (const q of questions) {
            if (!q.text.trim()) { validationDiv.textContent = 'All question fields must be filled.'; validationDiv.classList.add('active'); return; }
            const filledOptions = q.options.filter(opt => opt.text.trim() !== '');
            if (filledOptions.length < 2 || filledOptions.length > 4) {
                validationDiv.textContent = 'Each question must have between 2 and 4 options.'; validationDiv.classList.add('active'); return;
            }
            // Ensure at least one option is marked as correct among the filled options
            if (!filledOptions.some(opt => opt.is_correct)) {
                validationDiv.textContent = 'Each question must have at least one correct answer.'; validationDiv.classList.add('active'); return;
            }
        }
        validationDiv.textContent = '';
        validationDiv.classList.remove('active'); // Clear active class when validation passes

        const finalQuestions = questions.map(q => ({
            text: q.text,
            options: q.options.filter(opt => opt.text.trim() !== '').map(opt => ({ text: opt.text, is_correct: opt.is_correct }))
        }));
        const saveResponse = await fetch('api/quiz_api.php?action=save_quiz_questions', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ quiz_id: currentQuizId, questions: finalQuestions }) });
        const saveData = await saveResponse.json();
        if (!saveData.success) {
            validationDiv.textContent = 'Error saving questions: ' + (saveData.message || 'Unknown error.');
            validationDiv.classList.add('active');
            return;
        }

        if (status === 'published') {
            const pubResponse = await fetch('api/quiz_api.php?action=publish_quiz', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ quiz_id: currentQuizId }) });
            const pubData = await pubResponse.json();
            if (!pubData.success) {
                validationDiv.textContent = 'Error publishing quiz: ' + (pubData.message || 'Unknown error.');
                validationDiv.classList.add('active');
                return;
            }
            showNotification('Quiz published successfully!', 'success');
        } else { showNotification('Quiz saved as a draft.', 'success'); }

        closeCreateQuizModal();
        loadPublicQuizzes();
    } catch (error) {
        console.error('Unexpected error in saveQuiz:', error);
        validationDiv.textContent = 'An unexpected error occurred: ' + error.message;
        validationDiv.classList.add('active');
    }
}

// --- TAKE QUIZ & RESULTS LOGIC ---
function takeQuiz(quizId) {
    // Redirect to the immersive quiz page
    window.location.href = `take_quiz.php?id=${quizId}`;
}

function closeTakeQuizModal() { document.getElementById('takeQuizModal').classList.remove('active'); }

function renderQuestion(index) {
    currentQuestionIndex = index;
    const question = activeQuizData.questions[index];
    document.getElementById('quiz-progress').textContent = `Question ${index + 1} of ${activeQuizData.questions.length}`;
    document.getElementById('take-quiz-question').textContent = question.question_text;
    const optionsContainer = document.getElementById('answer-options');
    optionsContainer.innerHTML = '';
    question.options.forEach(opt => {
        const optionEl = document.createElement('div');
        optionEl.className = 'answer-option';
        optionEl.textContent = opt.option_text;
        optionEl.onclick = () => selectAnswer(question.id, opt.id, optionEl);
        if (userAnswers[question.id] == opt.id) optionEl.classList.add('selected');
        optionsContainer.appendChild(optionEl);
    });
    document.getElementById('prev-question-btn').style.display = index > 0 ? 'inline-flex' : 'none';
    document.getElementById('next-question-btn').style.display = index < activeQuizData.questions.length - 1 ? 'inline-flex' : 'none';
    document.getElementById('finish-quiz-btn').style.display = index === activeQuizData.questions.length - 1 ? 'inline-flex' : 'none';
}

function selectAnswer(questionId, optionId, element) {
    userAnswers[questionId] = optionId;
    element.parentElement.querySelectorAll('.answer-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
}

async function finishQuiz() {
    if (Object.keys(userAnswers).length !== activeQuizData.questions.length) { showNotification('Please answer all questions before finishing.', 'error'); return; }
    const response = await fetch('api/quiz_api.php?action=submit_quiz', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ quiz_id: activeQuizData.id, answers: userAnswers }) });
    const resultsData = await response.json();
    if (resultsData.success) {
        closeTakeQuizModal();
        showResults(resultsData);
    } else { showNotification('Error submitting quiz: ' + resultsData.message, 'error'); }
}

function showResults(data) {
    document.getElementById('results-score').textContent = `${data.score} / ${data.total}`;
    const detailsContainer = document.getElementById('results-details');
    detailsContainer.innerHTML = '';
    activeQuizData.questions.forEach(q => {
        const result = data.results.find(r => r.question_id == q.id);
        const card = document.createElement('div');
        card.className = `question-card ${result.is_correct ? 'correct' : 'incorrect'}`;
        let optionsHtml = '';
        q.options.forEach(opt => {
            let detail = '';
            if (opt.id == result.correct_option_id) detail = ' <span class="correct-answer">(Correct Answer)</span>';
            if (userAnswers[q.id] == opt.id && !result.is_correct) detail += ' <span class="user-answer">(Your Answer)</span>';
            optionsHtml += `<div>${escapeHtml(opt.option_text)}${detail}</div>`;
        });
        card.innerHTML = `<h4>${escapeHtml(q.question_text)}</h4><div>${optionsHtml}</div>`;
        detailsContainer.appendChild(card);
    });
    document.getElementById('resultsModal').classList.add('active');
}

function closeResultsModal() { document.getElementById('resultsModal').classList.remove('active'); }

// --- AI QUIZ MODAL LOGIC ---
function openAiQuizModal() {
    const modal = document.getElementById('aiQuizModal');
    modal.style.removeProperty('display');
    modal.classList.add('active');
    document.getElementById('ai-validation-message').textContent = '';
    document.getElementById('aiQuizForm').reset();
}

function closeAiQuizModal() {
    const modal = document.getElementById('aiQuizModal');
    modal.style.display = 'none';
    modal.classList.remove('active');
}

async function handleAiCreateQuiz(event) {
    event.preventDefault();
    const validationMsg = document.getElementById('ai-validation-message');
    const generateBtn = document.getElementById('ai-generate-btn');

    const subject = document.getElementById('ai-quiz-subject').value.trim();
    const weaknesses = document.getElementById('ai-quiz-weaknesses').value.trim();
    const numQuestions = parseInt(document.getElementById('ai-quiz-num-questions').value, 10);

    if (!subject || !weaknesses) {
        validationMsg.textContent = 'Please fill out the subject and weaknesses.';
        return;
    }
    if (isNaN(numQuestions) || numQuestions < 5 || numQuestions > 10) {
        validationMsg.textContent = 'Number of questions must be between 5 and 10.';
        return;
    }
    validationMsg.textContent = '';
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

    try {
        const response = await fetch('api/gemini_quiz_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject, weaknesses, numQuestions }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'An unknown API error occurred.' }));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.quiz) {
            populateQuizFormWithAiData(data.quiz);
            closeAiQuizModal();
            showNotification('AI quiz generated successfully! Please review and save.', 'success');
        } else {
            throw new Error(data.message || 'Failed to generate quiz from AI.');
        }

    } catch (error) {
        console.error('AI Quiz Generation Error:', error);
        validationMsg.textContent = error.message;
        showNotification(error.message, 'error');
    } finally {
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="fas fa-magic"></i> Generate Quiz';
    }
}

async function populateQuizFormWithAiData(quiz) {
    // 1. Reset and show the main quiz modal
    initCreateQuiz(); 

    // 2. Populate metadata
    document.getElementById('quiz-title').value = quiz.title || 'AI Generated Quiz';
    document.getElementById('quiz-subject').value = quiz.subject || '';
    document.getElementById('quiz-description').value = quiz.description || '';

    try {
        // 3. Create a draft quiz
        const response = await fetch('api/quiz_api.php?action=create_draft_quiz', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                title: quiz.title, 
                subject: quiz.subject, 
                description: quiz.description, 
                skill_level: 'any' 
            })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message);
        
        currentQuizId = data.quiz_id;

        // 4. Populate questions
        if (quiz.questions && quiz.questions.length > 0) {
            questions = quiz.questions.map(q => ({
                id: `q_${Date.now()}_${Math.random()}`,
                text: q.question_text,
                options: q.options.map(opt => ({
                    text: opt.text,
                    is_correct: opt.is_correct
                }))
            }));
        } else {
            questions = [];
            addQuestion();
        }

        // 5. Switch view
        document.getElementById('step-1-metadata').style.display = 'none';
        document.getElementById('step-2-questions').style.display = 'block';
        document.getElementById('next-step-btn').style.display = 'none';
        document.getElementById('final-actions').style.display = 'flex';
        document.getElementById('save-changes-btn').textContent = 'Save as Draft';

        // 6. Render
        renderQuestions();

    } catch (error) {
        showNotification('Error setting up AI quiz: ' + error.message, 'error');
        closeCreateQuizModal();
    }
}

</script>

</body>
</html>