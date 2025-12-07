<?php
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$quiz_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$quiz_id) {
    die("Invalid Quiz ID.");
}

// Fetch quiz basic info for the title/meta before loading JS
$db = get_db();
try {
    $stmt = $db->prepare("SELECT title, subject FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz_info) {
        die("Quiz not found.");
    }
} catch (Exception $e) {
    die("Error loading quiz.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taking Quiz: <?php echo htmlspecialchars($quiz_info['title']); ?> - Study Buddy</title>
    
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
        
        .quiz-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
            width: 100%;
        }

        .question-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 3rem;
            width: 100%;
            position: relative;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .progress-bar-container {
            width: 100%;
            height: 6px;
            background-color: #e5e7eb;
            border-radius: 3px;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background-color: #4f46e5;
            width: 0%;
            transition: width 0.3s ease;
        }

        .question-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 2rem;
            line-height: 1.4;
        }

        .options-grid {
            display: grid;
            gap: 1rem;
        }

        .option-btn {
            text-align: left;
            padding: 1.25rem 1.5rem;
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            background: white;
            font-size: 1.1rem;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .option-btn:hover {
            border-color: #4f46e5;
            background-color: #eef2ff;
            color: #4f46e5;
        }

        .option-btn.selected {
            border-color: #4f46e5;
            background-color: #4f46e5;
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .controls {
            display: flex;
            justify-content: space-between;
            margin-top: 2.5rem;
            width: 100%;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }
        .btn-secondary:hover { background: #e5e7eb; }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }
        .btn-primary:hover { background: #4338ca; }

        /* Results Styles */
        .results-container {
            text-align: center;
            width: 100%;
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #4f46e5;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 800;
            margin: 0 auto 2rem;
            box-shadow: 0 10px 30px rgba(79, 70, 229, 0.3);
        }
        .result-detail-item {
            background: #f9fafb;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            text-align: left;
            border-left: 5px solid #e5e7eb;
        }
        .result-detail-item.correct { border-left-color: #10b981; background: #ecfdf5; }
        .result-detail-item.incorrect { border-left-color: #ef4444; background: #fef2f2; }
        
        .correct-tag { color: #059669; font-weight: 600; font-size: 0.9rem; margin-left: 0.5rem; }
        .your-tag { color: #dc2626; font-weight: 600; font-size: 0.9rem; margin-left: 0.5rem; }

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
    <div class="quiz-container">
        <!-- Loading State -->
        <div id="loading-state" class="text-center py-12">
            <i class="fas fa-circle-notch fa-spin text-4xl text-indigo-600 mb-4"></i>
            <p class="text-gray-500">Preparing your challenge...</p>
        </div>

        <!-- Quiz Area -->
        <div id="quiz-area" class="question-card" style="display: none;">
            <div class="progress-bar-container">
                <div id="progress-bar" class="progress-bar-fill"></div>
            </div>
            
            <div class="flex justify-between items-center mb-4 text-sm font-semibold text-gray-400 uppercase tracking-wide">
                <span id="question-number">Question 1</span>
                <span id="total-questions">of 10</span>
            </div>

            <h2 id="question-text" class="question-text"></h2>

            <div id="options-container" class="options-grid">
                <!-- Options injected here -->
            </div>

            <div class="controls">
                <button id="next-btn" class="btn btn-primary" onclick="nextQuestion()">
                    Next <i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button id="finish-btn" class="btn btn-primary" style="display: none; background-color: #10b981;" onclick="submitQuiz()">
                    Finish <i class="fas fa-check ml-2"></i>
                </button>
            </div>
        </div>

        <!-- Results Area -->
        <div id="results-area" class="question-card results-container" style="display: none;">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Challenge Complete!</h2>
            <div class="score-circle">
                <span id="final-score">0</span>
            </div>
            <p class="text-gray-600 mb-8 text-lg">You scored <span id="score-text">0/0</span></p>
            
            <div id="detailed-results" class="text-left space-y-4 mb-8">
                <!-- Details here -->
            </div>

            <!-- Removed "Back to Arena" button -->
            <button class="btn btn-primary inline-block" onclick="window.location.href = 'quizzes.php';">
                Back to Quiz Arena
            </button>
        </div>
    </div>

<script>
    const quizId = <?php echo $quiz_id; ?>;
    let quizData = null;
    let currentQuestionIndex = 0;
    let userAnswers = {};

    document.addEventListener('DOMContentLoaded', loadQuiz);

    async function loadQuiz() {
        try {
            const response = await fetch(`api/quiz_api.php?action=get_quiz_for_taking&quiz_id=${quizId}`);
            const data = await response.json();
            
            if (!data.success) {
                alert(data.message || "Failed to load quiz.");
                // Redirect to quizzes.php if quiz fails to load
                window.location.href = 'quizzes.php';
                return;
            }

            quizData = data.quiz;
            renderQuizUI();
        } catch (error) {
            console.error(error);
            alert("An error occurred.");
            window.location.href = 'quizzes.php';
        }
    }

    function renderQuizUI() {
        document.getElementById('loading-state').style.display = 'none';
        document.getElementById('quiz-area').style.display = 'block';
        document.getElementById('total-questions').textContent = `of ${quizData.questions.length}`;
        renderQuestion(0);
    }

    function renderQuestion(index) {
        currentQuestionIndex = index;
        const question = quizData.questions[index];
        
        // Update Progress
        const progressPercent = ((index + 1) / quizData.questions.length) * 100;
        document.getElementById('progress-bar').style.width = `${progressPercent}%`;
        document.getElementById('question-number').textContent = `Question ${index + 1}`;

        // Content
        document.getElementById('question-text').textContent = question.question_text;
        
        // Options
        const container = document.getElementById('options-container');
        container.innerHTML = '';
        
        question.options.forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.textContent = opt.option_text;
            if (userAnswers[question.id] == opt.id) {
                btn.classList.add('selected');
            }
            btn.onclick = () => selectAnswer(question.id, opt.id);
            container.appendChild(btn);
        });

        // Buttons
        const nextBtn = document.getElementById('next-btn');
        const finishBtn = document.getElementById('finish-btn');

        if (index === quizData.questions.length - 1) {
            nextBtn.style.display = 'none';
            finishBtn.style.display = 'inline-flex';
        } else {
            nextBtn.style.display = 'inline-flex';
            finishBtn.style.display = 'none';
        }
    }

    function selectAnswer(questionId, optionId) {
        userAnswers[questionId] = optionId;
        renderQuestion(currentQuestionIndex); // Re-render to update styles
    }

    function nextQuestion() {
        if (currentQuestionIndex < quizData.questions.length - 1) {
            renderQuestion(currentQuestionIndex + 1);
        }
    }

    // Previous button logic removed
    // function prevQuestion() {
    //     if (currentQuestionIndex > 0) {
    //         renderQuestion(currentQuestionIndex - 1);
    //     }
    // }

    async function submitQuiz() {
        // Validation
        if (Object.keys(userAnswers).length < quizData.questions.length) {
            if(!confirm("You haven't answered all questions. Are you sure you want to finish?")) {
                return;
            }
        }

        // Disable button
        document.getElementById('finish-btn').disabled = true;
        document.getElementById('finish-btn').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const response = await fetch('api/quiz_api.php?action=submit_quiz', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quiz_id: quizId, answers: userAnswers })
            });
            const result = await response.json();
            
            if (result.success) {
                showResults(result);
            } else {
                alert('Error submitting: ' + result.message);
                document.getElementById('finish-btn').disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Network error.');
        }
    }

    function showResults(result) {
        document.getElementById('quiz-area').style.display = 'none';
        document.getElementById('results-area').style.display = 'block';
        
        document.getElementById('final-score').textContent = Math.round((result.score / result.total) * 100) + '%';
        document.getElementById('score-text').textContent = `${result.score} / ${result.total}`;

        const container = document.getElementById('detailed-results');
        container.innerHTML = '';

        quizData.questions.forEach(q => {
            const res = result.results.find(r => r.question_id == q.id);
            const isCorrect = res && res.is_correct;
            
            const item = document.createElement('div');
            item.className = `result-detail-item ${isCorrect ? 'correct' : 'incorrect'}`;
            
            let optionsHtml = '<ul class="mt-2 space-y-1">';
            q.options.forEach(opt => {
                let status = '';
                // Logic to show correct/incorrect labels
                if (res.correct_option_id == opt.id) status = '<span class="correct-tag"><i class="fas fa-check"></i> Correct Answer</span>';
                if (userAnswers[q.id] == opt.id && !isCorrect) status += '<span class="your-tag"><i class="fas fa-times"></i> Your Answer</span>';
                
                // Bold the correct answer or user selected answer
                const isSelected = userAnswers[q.id] == opt.id;
                const isActualCorrect = res.correct_option_id == opt.id;
                const style = (isSelected || isActualCorrect) ? 'font-weight: 600; color: #1f2937;' : 'color: #6b7280;';

                optionsHtml += `<li style="${style}">• ${opt.option_text} ${status}</li>`;
            });
            optionsHtml += '</ul>';

            item.innerHTML = `
                <div class="font-bold text-gray-800 mb-1">${q.question_text}</div>
                ${optionsHtml}
            `;
            container.appendChild(item);
        });
    }
</script>

</body>
</html>
