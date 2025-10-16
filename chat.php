<?php
// chat.php - Direct chat interface
require_once 'session.php';
require_once __DIR__ . '/lib/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$room_id = (int)($_GET['room_id'] ?? 0);

if (!$room_id) {
    header("Location: study-partners.php");
    exit();
}

$db = get_db();

// --- Get Room & Partner Details ---
$room_stmt = $db->prepare("
    SELECT 
        cr.*,
        CASE
            WHEN cr.partner1_id = ? THEN cr.partner2_id
            ELSE cr.partner1_id
        END as partner_id
    FROM chat_rooms cr
    WHERE cr.id = ? AND cr.room_type = 'direct'
    AND (cr.partner1_id = ? OR cr.partner2_id = ?)
");
$room_stmt->execute([$current_user_id, $room_id, $current_user_id, $current_user_id]);
$room = $room_stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    $_SESSION['error'] = "Chat room not found or access denied.";
    header("Location: study-partners.php");
    exit();
}

// Get partner's info
$partner_id = (int)$room['partner_id'];
$partner_stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$partner_stmt->execute([$partner_id]);
$partner = $partner_stmt->fetch(PDO::FETCH_ASSOC);

// Get current user's info
$user_stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$user_stmt->execute([$current_user_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
$current_user_initials = strtoupper(substr($current_user['first_name'], 0, 1) . substr($current_user['last_name'], 0, 1));

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
    <title>Chat with <?php echo htmlspecialchars($partner['first_name']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Styles from group-chat.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 900px; /* Adjusted for a more compact 1-on-1 chat */
            width: 100%;
            margin: 1rem auto;
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
        }
        .chat-header {
            padding: 1.25rem 1.5rem; /* Adjusted padding */
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-header h2 {
            font-size: 1.25rem; /* Adjusted font size */
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .header-actions a {
            color: white;
            text-decoration: none;
            font-size: 1.25rem; /* Back arrow */
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
            max-width: 75%;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .message.own {
            margin-left: auto;
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
            display: flex;
            flex-direction: column;
        }
        .message.own .message-content { align-items: flex-end; }
        .message-sender {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            color: #374151;
        }
        .message-bubble {
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 1.25rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            word-wrap: break-word;
        }
        .message.own .message-bubble {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        .message-time {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
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
        #message-input {
            flex: 1;
            padding: 0.75rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 2rem;
            font-size: 1rem;
            transition: all 0.3s;
        }
        #message-input:focus {
            outline: none;
            border-color: #667eea;
        }
        #send-button {
            width: 48px;
            height: 48px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        #send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <header class="chat-header">
            <h2>
                <a href="messages.php" class="header-actions"><i class="fas fa-arrow-left"></i></a>
                Chat with <?php echo htmlspecialchars($partner['first_name'] . ' ' . $partner['last_name']); ?>
            </h2>
            <div class="header-actions">
                <a href="user_profile.php?id=<?php echo $partner_id; ?>" title="View Profile">
                    <i class="fas fa-user-circle"></i>
                </a>
            </div>
        </header>

        <main id="messages-container" class="messages-container"></main>

        <footer class="message-input-container">
            <div class="message-input-wrapper">
                <input type="text" id="message-input" placeholder="Type a message..." autocomplete="off">
                <button id="send-button"><i class="fas fa-paper-plane"></i></button>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messages-container');
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');

            const roomId = <?php echo $room_id; ?>;
            const currentUserId = <?php echo $current_user_id; ?>;
            const currentUserInitials = "<?php echo $current_user_initials; ?>";
            let lastMessageId = 0;

            function appendMessage(msg) {
                const isOwn = parseInt(msg.user_id, 10) === currentUserId;
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isOwn ? 'own' : ''}`;
                
                messageDiv.innerHTML = `
                    <div class="message-avatar">${isOwn ? currentUserInitials : msg.user_name.substring(0, 2).toUpperCase()}</div>
                    <div class="message-content">
                        ${!isOwn ? `<div class="message-sender">${msg.user_name}</div>` : ''}
                        <div class="message-bubble">${msg.message}</div>
                        <div class="message-time">${new Date(msg.created_at.replace(' ', 'T')).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
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
                        if (sinceId === 0) { // Scroll to bottom only on initial load
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
                        // Restore message if sending failed
                        messageInput.value = messageText;
                        alert('Failed to send message: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    messageInput.value = messageText;
                } finally {
                    sendButton.disabled = false;
                }
            }

            sendButton.addEventListener('click', sendMessage);
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });

            // Initial load and polling
            fetchMessages();
            setInterval(() => fetchMessages(lastMessageId), 3000); // Poll for new messages every 3 seconds

            // Mark messages as read when entering the chat
            const lastMessageIdInRoom = <?php echo $last_message_id_in_room; ?>;
            if (lastMessageIdInRoom > 0) {
                fetch('api/mark_as_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        room_id: roomId,
                        last_message_id: lastMessageIdInRoom
                    })
                }).catch(error => console.error('Error marking messages as read:', error));
            }
        });
    </script>
</body>
</html>