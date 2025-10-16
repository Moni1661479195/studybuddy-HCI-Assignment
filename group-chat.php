<?php
// group-chat.php - Group chat interface
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$room_id = (int)($_GET['room_id'] ?? 0);

if (!$room_id) {
    header("Location: my-groups.php");
    exit();
}

$db = get_db();

// Get room details
$room_stmt = $db->prepare("
    SELECT cr.*, sg.group_name as group_name, sg.group_id as group_id
    FROM chat_rooms cr
    LEFT JOIN study_groups sg ON cr.group_id = sg.group_id
    WHERE cr.id = ?
");
$room_stmt->execute([$room_id]);
$room = $room_stmt->fetch(PDO::FETCH_ASSOC);

if (!$room || $room['room_type'] !== 'group') {
    header("Location: my-groups.php");
    exit();
}

// Verify user is a group member
$member_check = $db->prepare("SELECT * FROM study_group_members WHERE group_id = ? AND user_id = ?");
$member_check->execute([$room['group_id'], $current_user_id]);
if (!$member_check->fetch()) {
    $_SESSION['error'] = "Access denied.";
    header("Location: my-groups.php");
    exit();
}

// Get current user info
$user_stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$user_stmt->execute([$current_user_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get the last message ID for this room
$last_message_id_in_room = 0;
$stmt_last_msg = $db->prepare("SELECT id FROM chat_messages WHERE room_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt_last_msg->execute([$room_id]);
$last_msg_result = $stmt_last_msg->fetch(PDO::FETCH_ASSOC);
if ($last_msg_result) {
    $last_message_id_in_room = (int)$last_msg_result['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['group_name']); ?> - Chat</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reuse styles from study-session.php chat interface */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            width: 100%;
            margin: 1rem auto;
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .chat-header {
            padding: 1.5rem;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h2 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            transition: all 0.3s;
        }

        .header-actions a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: #f8fafc;
        }

        .message {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .message.own .message-avatar {
            background: linear-gradient(45deg, #10b981, #059669);
        }

        .message-content {
            max-width: 70%;
        }

        .message-header {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.25rem;
        }

        .message.own .message-header {
            flex-direction: row-reverse;
        }

        .message-sender {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9rem;
        }

        .message-time {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .message-bubble {
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            word-wrap: break-word;
        }

        .message.own .message-bubble {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .message.system {
            justify-content: center;
        }

        .message.system .message-bubble {
            background: #fef3c7;
            color: #92400e;
            font-size: 0.9rem;
            text-align: center;
        }

        .message-input-container {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .message-input-wrapper {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .message-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 2rem;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .message-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .send-button {
            width: 48px;
            height: 48px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h2>
                <i class="fas fa-comments"></i> 
                <?php echo htmlspecialchars($room['group_name']); ?>
            </h2>
            <div class="header-actions">
                <a href="group-details.php?id=<?php echo $room['group_id']; ?>">
                    <i class="fas fa-info-circle"></i> Group Details
                </a>
            </div>
        </div>

        <div id="messages-container" class="messages-container">
            <!-- Messages loaded via JavaScript -->
        </div>

        <div class="message-input-container">
            <div class="message-input-wrapper">
                <input 
                    type="text" 
                    id="message-input" 
                    class="message-input" 
                    placeholder="Type your message..." 
                    autocomplete="off"
                >
                <button id="send-button" class="send-button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const messagesContainer = document.getElementById('messages-container');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');

        const roomId = <?php echo $room_id; ?>;
        const currentUserId = <?php echo $current_user_id; ?>;
        let lastMessageId = 0;

        function appendMessage(msg) {
            const isOwn = parseInt(msg.user_id, 10) === currentUserId;
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isOwn ? 'own' : ''}`;
            
            const initials = msg.user_name ? msg.user_name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
            const time = new Date(msg.created_at.replace(' ', 'T')).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            messageDiv.innerHTML = `
                <div class="message-avatar">${initials}</div>
                <div class="message-content">
                    ${!isOwn ? `<div class="message-sender">${msg.user_name}</div>` : ''}
                    <div class="message-bubble">${msg.message}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            messagesContainer.appendChild(messageDiv);
            lastMessageId = Math.max(lastMessageId, msg.id);
        }

        async function fetchMessages(sinceId = 0) {
            try {
                const response = await fetch(`api/chat_api.php?action=get_messages&room_id=${roomId}&last_id=${sinceId}`);
                const data = await response.json();
                if (data.success) {
                    data.messages.forEach(appendMessage);
                    if (sinceId === 0 && data.messages.length > 0) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                } else {
                    console.error('Failed to fetch messages:', data.message);
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }

        async function sendMessage() {
            const messageText = messageInput.value.trim();
            if (messageText === '') return;

            messageInput.value = '';
            sendButton.disabled = true;

            try {
                const response = await fetch('api/chat_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'send_message',
                        room_id: roomId,
                        message: messageText
                    })
                });
                const data = await response.json();
                if (!data.success) {
                    messageInput.value = messageText; // Restore message on failure
                    alert('Failed to send message: ' + data.message);
                }
                // The new message will be picked up by the polling
            } catch (error) {
                console.error('Error sending message:', error);
                messageInput.value = messageText;
            } finally {
                sendButton.disabled = false;
                messageInput.focus();
            }
        }

        sendButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Initial load and polling
        fetchMessages();
        setInterval(() => fetchMessages(lastMessageId), 3000);
    });
    </script>
</body>
</html>
<?php $db = null; ?>