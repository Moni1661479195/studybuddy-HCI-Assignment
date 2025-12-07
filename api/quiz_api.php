<?php
require_once '../session.php';
require_once '../lib/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? null;

// Ensure user is logged in for actions that require it
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = get_db();

switch ($action) {
    case 'get_public_quizzes':
        try {
            $search = $_GET['search'] ?? '';
            $sql = "
                SELECT q.id, q.title, q.subject, q.description, q.skill_level, q.status, COUNT(qq.id) AS question_count, u.first_name, u.last_name
                FROM quizzes q
                LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
                JOIN users u ON q.author_id = u.id
                WHERE q.status = 'published'
            ";
            $params = [];
            if (!empty($search)) {
                $sql .= " AND (q.title LIKE ? OR q.subject LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $sql .= " GROUP BY q.id ORDER BY q.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'quizzes' => $quizzes]);
        } catch (Exception $e) {
            error_log("Get Public Quizzes Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to retrieve public quizzes.']);
        }
        break;

    case 'create_draft_quiz':
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $stmt = $db->prepare("INSERT INTO quizzes (author_id, title, subject, description, skill_level, status) VALUES (?, ?, ?, ?, ?, 'draft')");
            $stmt->execute([$current_user_id, $data['title'], $data['subject'], $data['description'], $data['skill_level']]);
            $quiz_id = $db->lastInsertId();
            echo json_encode(['success' => true, 'quiz_id' => $quiz_id]);
        } catch (Exception $e) {
            error_log("Create Draft Quiz Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to create quiz draft.']);
        }
        break;

    case 'save_quiz_questions':
        $data = json_decode(file_get_contents('php://input'), true);
        $quiz_id = $data['quiz_id'] ?? 0;
        $questions = $data['questions'] ?? [];

        // Authorization check: ensure user owns the quiz
        $stmt = $db->prepare("SELECT author_id FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
        $author_id = $stmt->fetchColumn();
        if ($author_id === false || (int)$author_id !== $current_user_id) {
            // If $author_id is false, the quiz was not found.
            // If it doesn't match, it's an ownership issue.
            // Both are authorization failures from the user's perspective.
            echo json_encode(['success' => false, 'message' => 'Authorization failed. Quiz not found or you do not have ownership.']);
            exit();
        }

        try {
            $db->beginTransaction();
            // Delete old questions and options for this quiz
            $stmt_del_opts = $db->prepare("DELETE FROM quiz_question_options WHERE question_id IN (SELECT id FROM quiz_questions WHERE quiz_id = ?)");
            $stmt_del_opts->execute([$quiz_id]);
            $stmt_del_qs = $db->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
            $stmt_del_qs->execute([$quiz_id]);

            // Insert new questions and options
            $stmt_q = $db->prepare("INSERT INTO quiz_questions (quiz_id, question_text, sort_order) VALUES (?, ?, ?)");
            $stmt_o = $db->prepare("INSERT INTO quiz_question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");

            foreach ($questions as $index => $question) {
                $stmt_q->execute([$quiz_id, $question['text'], $index]);
                $question_id = $db->lastInsertId();
                foreach ($question['options'] as $option) {
                    $stmt_o->execute([$question_id, $option['text'], $option['is_correct']]);
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Quiz saved successfully.']);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Save Quiz Questions Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while saving questions.']);
        }
        break;

    case 'publish_quiz':
        $data = json_decode(file_get_contents('php://input'), true);
        $quiz_id = $data['quiz_id'] ?? 0;

        if (!$quiz_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Quiz ID.']);
            exit();
        }

        try {
            // Authorization check: ensure user owns the quiz
            $stmt_auth = $db->prepare("SELECT author_id FROM quizzes WHERE id = ?");
            $stmt_auth->execute([$quiz_id]);
            if ($stmt_auth->fetchColumn() != $current_user_id) {
                echo json_encode(['success' => false, 'message' => 'Authorization failed. You do not own this quiz.']);
                exit();
            }

            // Update quiz status to published
            $stmt_publish = $db->prepare("UPDATE quizzes SET status = 'published' WHERE id = ?");
            $stmt_publish->execute([$quiz_id]);

            // Task: Quiz Master
            require_once __DIR__ . '/../includes/TaskLogic.php';
            updateTaskProgress($db, $current_user_id, 'weekly_create_quiz');

            echo json_encode(['success' => true, 'message' => 'Quiz published successfully.']);
        } catch (Exception $e) {
            error_log("Publish Quiz Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while publishing the quiz.']);
        }
        break;

    case 'get_quiz_for_taking':
        $quiz_id = $_GET['quiz_id'] ?? 0;
        if (!$quiz_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Quiz ID.']);
            exit();
        }
        try {
            $stmt_q = $db->prepare("SELECT id, title, subject, description FROM quizzes WHERE id = ? AND status = 'published'");
            $stmt_q->execute([$quiz_id]);
            $quiz = $stmt_q->fetch(PDO::FETCH_ASSOC);

            if (!$quiz) {
                echo json_encode(['success' => false, 'message' => 'Quiz not found or not published.']);
                exit();
            }

            $stmt_qs = $db->prepare("SELECT id, question_text FROM quiz_questions WHERE quiz_id = ? ORDER BY sort_order");
            $stmt_qs->execute([$quiz_id]);
            $questions = $stmt_qs->fetchAll(PDO::FETCH_ASSOC);

            $stmt_o = $db->prepare("SELECT id, option_text FROM quiz_question_options WHERE question_id = ?");
            foreach ($questions as $key => $q) {
                $stmt_o->execute([$q['id']]);
                $questions[$key]['options'] = $stmt_o->fetchAll(PDO::FETCH_ASSOC);
            }
            $quiz['questions'] = $questions;
            echo json_encode(['success' => true, 'quiz' => $quiz]);
        } catch (Exception $e) {
            error_log("Get Quiz For Taking Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to load quiz.']);
        }
        break;

    case 'submit_quiz':
        $data = json_decode(file_get_contents('php://input'), true);
        $quiz_id = $data['quiz_id'] ?? 0;
        $user_answers = $data['answers'] ?? [];

        try {
            $score = 0;
            $total_questions = 0;
            $results = [];

            $stmt_q = $db->prepare("SELECT id FROM quiz_questions WHERE quiz_id = ? ORDER BY sort_order");
            $stmt_q->execute([$quiz_id]);
            $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);
            $total_questions = count($questions);

            $stmt_o = $db->prepare("SELECT id, is_correct FROM quiz_question_options WHERE question_id = ?");

            foreach ($questions as $index => $question) {
                $question_id = $question['id'];
                $stmt_o->execute([$question_id]);
                $options = $stmt_o->fetchAll(PDO::FETCH_ASSOC);
                $correct_option_id = null;
                foreach($options as $opt) {
                    if ($opt['is_correct']) {
                        $correct_option_id = $opt['id'];
                        break;
                    }
                }

                $user_answer_id = $user_answers[$question_id] ?? null;
                $is_user_correct = ($user_answer_id == $correct_option_id);
                if ($is_user_correct) {
                    $score++;
                }
                $results[] = ['question_id' => $question_id, 'is_correct' => $is_user_correct, 'correct_option_id' => $correct_option_id];
            }

            // Save the attempt
            $stmt_attempt = $db->prepare("INSERT INTO quiz_attempts (quiz_id, user_id, score, total_questions) VALUES (?, ?, ?, ?)");
            $stmt_attempt->execute([$quiz_id, $current_user_id, $score, $total_questions]);

            // Task: Quick Practice
            require_once __DIR__ . '/../includes/TaskLogic.php';
            updateTaskProgress($db, $current_user_id, 'daily_quiz');

            echo json_encode(['success' => true, 'score' => $score, 'total' => $total_questions, 'results' => $results]);

        } catch (Exception $e) {
            error_log("Submit Quiz Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while submitting your answers.']);
        }
        break;

    case 'get_my_quizzes':
        try {
            $search = $_GET['search'] ?? '';
            $sql = "
                SELECT q.id, q.title, q.subject, q.description, q.skill_level, q.status, COUNT(qq.id) AS question_count
                FROM quizzes q
                LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
                WHERE q.author_id = ?
            ";
            $params = [$current_user_id];
            if (!empty($search)) {
                $sql .= " AND (q.title LIKE ? OR q.subject LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $sql .= " GROUP BY q.id ORDER BY q.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'quizzes' => $quizzes]);
        } catch (Exception $e) {
            error_log("Get My Quizzes Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to retrieve your quizzes.']);
        }
        break;

    case 'get_quiz_for_editing':
        $quiz_id = $_GET['quiz_id'] ?? 0;
        if (!$quiz_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Quiz ID.']);
            exit();
        }

        try {
            // Fetch quiz metadata and authorize
            $stmt_quiz = $db->prepare("SELECT id, title, subject, description, skill_level, status FROM quizzes WHERE id = ? AND author_id = ?");
            $stmt_quiz->execute([$quiz_id, $current_user_id]);
            $quiz = $stmt_quiz->fetch(PDO::FETCH_ASSOC);

            if (!$quiz) {
                echo json_encode(['success' => false, 'message' => 'Quiz not found or unauthorized.']);
                exit();
            }

            // Fetch questions
            $stmt_questions = $db->prepare("SELECT id, question_text FROM quiz_questions WHERE quiz_id = ? ORDER BY sort_order");
            $stmt_questions->execute([$quiz_id]);
            $questions = $stmt_questions->fetchAll(PDO::FETCH_ASSOC);

            // Fetch options for each question
            $stmt_options = $db->prepare("SELECT id, option_text, is_correct FROM quiz_question_options WHERE question_id = ?");
            foreach ($questions as $q_key => $question) {
                $stmt_options->execute([$question['id']]);
                $options = $stmt_options->fetchAll(PDO::FETCH_ASSOC);
                // Map option_text to text and id to id for consistency with frontend structure
                $questions[$q_key]['options'] = array_map(function($opt) {
                    return ['id' => $opt['id'], 'text' => $opt['option_text'], 'is_correct' => (bool)$opt['is_correct']];
                }, $options);
            }

            $quiz['questions'] = $questions;
            echo json_encode(['success' => true, 'quiz' => $quiz]);

        } catch (Exception $e) {
            error_log("Get Quiz For Editing Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to retrieve quiz for editing.']);
        }
        break;

    case 'delete_quiz':
        $quiz_id = $_POST['quiz_id'] ?? 0;
        if (!$quiz_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Quiz ID.']);
            exit();
        }

        try {
            $db->beginTransaction();

            // Authorize: ensure user owns the quiz
            $stmt_auth = $db->prepare("SELECT author_id FROM quizzes WHERE id = ?");
            $stmt_auth->execute([$quiz_id]);
            if ($stmt_auth->fetchColumn() != $current_user_id) {
                echo json_encode(['success' => false, 'message' => 'Authorization failed. You do not own this quiz.']);
                exit();
            }

            // Get all question IDs for this quiz
            $stmt_q_ids = $db->prepare("SELECT id FROM quiz_questions WHERE quiz_id = ?");
            $stmt_q_ids->execute([$quiz_id]);
            $question_ids = $stmt_q_ids->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($question_ids)) {
                $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
                // Delete options
                $stmt_del_opts = $db->prepare("DELETE FROM quiz_question_options WHERE question_id IN ($placeholders)");
                $stmt_del_opts->execute($question_ids);
                // Delete questions
                $stmt_del_qs = $db->prepare("DELETE FROM quiz_questions WHERE quiz_id = ?");
                $stmt_del_qs->execute([$quiz_id]);
            }

            // Delete quiz attempts
            $stmt_del_attempts = $db->prepare("DELETE FROM quiz_attempts WHERE quiz_id = ?");
            $stmt_del_attempts->execute([$quiz_id]);

            // Delete the quiz itself
            $stmt_del_quiz = $db->prepare("DELETE FROM quizzes WHERE id = ?");
            $stmt_del_quiz->execute([$quiz_id]);

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Quiz deleted successfully.']);

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Delete Quiz Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the quiz.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}
