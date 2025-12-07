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
$is_modal = isset($_GET['modal']) && $_GET['modal'] === 'true';

if (!$room_id) {
    if (!$is_modal) {
        header("Location: my-groups.php");
    } else {
        echo "<div style='text-align: center; padding: 20px; color: red;'>Error: Group chat ID missing.</div>";
    }
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
    if (!$is_modal) {
        $_SESSION['error'] = "Group chat room not found or access denied.";
        header("Location: my-groups.php");
    } else {
        echo "<div style='text-align: center; padding: 20px; color: red;'>Error: Group chat room not found or access denied.</div>";
    }
    exit();
}

// Verify user is a group member
$member_check = $db->prepare("SELECT * FROM study_group_members WHERE group_id = ? AND user_id = ?");
$member_check->execute([$room['group_id'], $current_user_id]);
if (!$member_check->fetch()) {
    if (!$is_modal) {
        $_SESSION['error'] = "Access denied.";
        header("Location: my-groups.php");
    } else {
        echo "<div style='text-align: center; padding: 20px; color: red;'>Error: Access denied. You are not a member of this group.</div>";
    }
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

if (!$is_modal) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['group_name']); ?> - Chat</title>

</head>
<body class="bg-gray-100">
<?php include 'header.php'; ?>
<?php } // End if (!$is_modal) ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .chat-container {
            width: 100%;
            height: calc(100vh - 2rem);
            max-width: 900px;
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .chat-header {
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .partner-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .partner-avatar { width: 40px; height: 40px; background: linear-gradient(45deg, #89f7fe, #66a6ff); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .partner-avatar-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .partner-details h2 { font-size: 1.2rem; color: #1f2937; }
        .partner-details p { color: #6b7280; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
        .online-indicator { width: 8px; height: 8px; border-radius: 50%; }
        .online-indicator.online { background: #28a745; }
        .online-indicator.offline { background: #9ca3af; }
        .header-actions a { color: #4b5563; text-decoration: none; font-size: 1.2rem; transition: color 0.3s; }
        .header-actions a:hover { color: #667eea; }
        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: rgba(249, 250, 251, 0.7);
        }
        .message { display: flex; gap: 0.75rem; margin-bottom: 1rem; max-width: 75%; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .message.own { margin-left: auto; flex-direction: row-reverse; }
        .message-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem; flex-shrink: 0; }
        .message.own .message-avatar { background: linear-gradient(45deg, #667eea, #764ba2); }
        .message:not(.own) .message-avatar { background: linear-gradient(45deg, #10b981, #059669); }
        .message-avatar-img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
        .message-content { display: flex; flex-direction: column; }
        .message.own .message-content { align-items: flex-end; }
        .message-bubble {
            padding: 0.75rem 1.25rem;
            border-radius: 1.25rem;
            word-wrap: break-word;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .message.own .message-bubble { background: linear-gradient(45deg, #667eea, #764ba2); color: white; border-bottom-right-radius: 0.25rem; }
        .message:not(.own) .message-bubble { background: white; color: #1f2937; border-bottom-left-radius: 0.25rem; }
        .message-time { font-size: 0.75rem; color: #9ca3af; margin-top: 0.35rem; }
        .message-input-container {
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.7);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            flex-shrink: 0;
        }
        .message-input-wrapper { display: flex; gap: 1rem; align-items: center; }
        #message-input { flex: 1; padding: 0.85rem 1.25rem; border: 1px solid #d1d5db; border-radius: 2rem; font-size: 1rem; background: white; transition: all 0.3s; }
        #message-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2); }
        #send-button, #upload-button, #record-button { width: 56px; height: 56px; border: none; border-radius: 50%; cursor: pointer; transition: all 0.3s; font-size: 1.4rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        #send-button { background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        #send-button:hover, #upload-button:hover, #record-button:hover { transform: scale(1.1); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        #upload-button, #record-button { background: #f0f0f0; color: #333; }
        .recording-wrapper { display: flex; align-items: center; justify-content: space-between; width: 100%; }
        .recording-status { display: flex; align-items: center; gap: 0.5rem; color: #d9534f; font-weight: 600; }
        #cancel-record-button, #send-record-button { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #4b5563; transition: color 0.3s; }
        #cancel-record-button:hover { color: #d9534f; }
        #send-record-button { color: #28a745; }
        #send-record-button:hover { color: #218838; }
        .file-bubble a {
            color: inherit;
            text-decoration: none;
        }
        .file-bubble img {
            max-width: 100%;
            border-radius: 0.75rem;
        }
</style>
    
<div class="chat-container" <?php if ($is_modal) echo 'style="height: 100%; border-radius: 0; box-shadow: none; background: #ffffff; backdrop-filter: none;"'; ?>>
    <header class="chat-header" <?php if ($is_modal) echo 'style="border-radius: 0; background: #f8fafc; border-bottom: 1px solid #e2e8f0;"'; ?>>
        <div class="partner-info">
            <div class="partner-avatar">
                <span><?php echo strtoupper(substr($room['group_name'], 0, 2)); ?></span>
            </div>
            <div class="partner-details">
                <h2><?php echo htmlspecialchars($room['group_name']); ?></h2>
                <p>Group Chat</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="group-details.php?id=<?php echo $room['group_id']; ?>" title="Group Details">
                <i class="fas fa-info-circle"></i>
            </a>
            <?php if (!$is_modal): ?>
                <a href="my-groups.php" title="Back to My Groups"><i class="fas fa-times-circle"></i></a>
            <?php endif; ?>
        </div>
    </header>
    
            <div id="messages-container" class="messages-container">
                <!-- Messages loaded via JavaScript -->
            </div>
    
            <div class="message-input-container" <?php if ($is_modal) echo 'style="border-radius: 0; background: #f8fafc; border-top: 1px solid #e2e8f0;"'; ?>>
                <div class="message-input-wrapper" id="input-wrapper">
                    <input type="file" id="file-input" style="display: none;" />
                    <button id="upload-button" title="Upload File"><i class="fas fa-paperclip"></i></button>
                    <button id="record-button" title="Record Audio"><i class="fas fa-microphone"></i></button>
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
                <div class="recording-wrapper" id="recording-wrapper" style="display: none;">
                    <button id="cancel-record-button" title="Cancel Recording"><i class="fas fa-trash"></i></button>
                    <div class="recording-status">
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <span id="record-timer">00:00</span>
                    </div>
                    <button id="send-record-button" title="Send Recording"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    
                <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script for chat functionality
        const messagesContainer = document.getElementById('messages-container');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        const uploadButton = document.getElementById('upload-button');
        const fileInput = document.getElementById('file-input');
        const recordButton = document.getElementById('record-button');

        const inputWrapper = document.getElementById('input-wrapper');
        const recordingWrapper = document.getElementById('recording-wrapper');
        const recordTimer = document.getElementById('record-timer');
        const cancelRecordButton = document.getElementById('cancel-record-button');
        const sendRecordButton = document.getElementById('send-record-button');

        let mediaRecorder;
        let audioChunks = [];
        let timerInterval;

        const roomId = <?php echo $room_id; ?>;
        const currentUserId = <?php echo $current_user_id; ?>;
        let lastMessageId = 0;

        recordButton.addEventListener('click', startRecording);
        cancelRecordButton.addEventListener('click', cancelRecording);
        sendRecordButton.addEventListener('click', sendRecording);

        async function startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };
                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    const audioFile = new File([audioBlob], 'voice-message.webm', { type: 'audio/webm' });
                    uploadFile(audioFile, true);
                };
                mediaRecorder.start();

                inputWrapper.style.display = 'none';
                recordingWrapper.style.display = 'flex';

                let seconds = 0;
                recordTimer.textContent = '00:00';
                timerInterval = setInterval(() => {
                    seconds++;
                    const minutes = Math.floor(seconds / 60).toString().padStart(2, '0');
                    const secs = (seconds % 60).toString().padStart(2, '0');
                    recordTimer.textContent = `${minutes}:${secs}`;
                }, 1000);

            } catch (err) {
                console.error('Error accessing microphone:', err);
                alert('Could not access microphone. Please ensure you have given permission.');
            }
        }

        function stopRecording(discard = false) {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
            if (mediaRecorder && mediaRecorder.stream) {
                mediaRecorder.stream.getTracks().forEach(track => track.stop());
            }
            clearInterval(timerInterval);
            inputWrapper.style.display = 'flex';
            recordingWrapper.style.display = 'none';
        }

        function cancelRecording() {
            stopRecording(true);
        }

        function sendRecording() {
            stopRecording(false);
        }

        async function uploadFile(file, isAudio = false) {
            if (!file) return;

            const formData = new FormData();
            formData.append('room_id', roomId);
            formData.append('file', file);

            if (!isAudio) {
                uploadButton.disabled = true;
            }

            try {
                const response = await fetch('api/upload_file.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    fetchMessages(lastMessageId);
                } else {
                    alert('Failed to upload file: ' + data.message);
                }
            } catch (error) {
                console.error('Error uploading file:', error);
                alert('An error occurred while uploading the file.');
            } finally {
                if (!isAudio) {
                    fileInput.value = '';
                    uploadButton.disabled = false;
                }
            }
        }

        function appendMessage(msg) {
            const isOwn = parseInt(msg.user_id, 10) === currentUserId;
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isOwn ? 'own' : ''}`;

            let avatarHtml;
            if (msg.profile_picture_path) {
                avatarHtml = `<img src="${msg.profile_picture_path}" alt="Avatar" class="message-avatar-img">`;
            } else {
                const initials = msg.user_name ? msg.user_name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
                avatarHtml = `<div class="message-avatar">${initials}</div>`;
            }
            
            let messageContent = '';
            if (msg.message) {
                messageContent = `<div class="message-bubble">${msg.message}</div>`;
            } else if (msg.file_path) {
                const fileName = msg.file_path.split('/').pop();
                const fileExtension = fileName.split('.').pop().toLowerCase();
                const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                const audioExtensions = ['webm', 'ogg', 'mp3', 'wav'];

                if (imageExtensions.includes(fileExtension)) {
                    messageContent = `<div class="message-bubble file-bubble">
                        <a href="${msg.file_path}" target="_blank" download>
                            <img src="${msg.file_path}" alt="${fileName}" style="max-width: 200px; border-radius: 10px;">
                        </a>
                    </div>`;
                } else if (audioExtensions.includes(fileExtension)) {
                    messageContent = `<div class="message-bubble file-bubble">
                        <audio controls src="${msg.file_path}"></audio>
                    </div>`;
                } else {
                    let iconClass = 'fas fa-file-alt';
                    if (['pdf'].includes(fileExtension)) {
                        iconClass = 'fas fa-file-pdf';
                    } else if (['doc', 'docx'].includes(fileExtension)) {
                        iconClass = 'fas fa-file-word';
                    }
                    else if (['xls', 'xlsx'].includes(fileExtension)) {
                        iconClass = 'fas fa-file-excel';
                    }
                    messageContent = `<div class="message-bubble file-bubble">
                        <a href="${msg.file_path}" target="_blank" download>
                            <i class="${iconClass}"></i> ${fileName}
                        </a>
                    </div>`;
                }
            }

            messageDiv.innerHTML = `
                ${avatarHtml}
                <div class="message-content">
                    ${messageContent}
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
                    const isScrolledToBottom = messagesContainer.scrollHeight - messagesContainer.clientHeight <= messagesContainer.scrollTop + 1;
                    
                    data.messages.forEach(appendMessage);

                    if (sinceId === 0 || isScrolledToBottom) {
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

            const originalText = messageText;
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
                if (data.success) {
                    fetchMessages(lastMessageId); // Fetch new messages immediately
                } else {
                    messageInput.value = originalText; // Restore message on failure
                    alert('Failed to send message: ' + data.message);
                }
            } catch (error) {
                console.error('Error sending message:', error);
                messageInput.value = originalText;
            }
            finally {
                sendButton.disabled = false;
                messageInput.focus();
            }
        }

        // Event Listeners
        sendButton.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        uploadButton.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => uploadFile(e.target.files[0]));

        // Initial load and polling
        fetchMessages(0); // Load all messages initially
        setInterval(() => fetchMessages(lastMessageId), 3000);
    });
                </script>
<?php if (!$is_modal) { ?>
<?php include 'footer.php'; ?>
</body>
</html>
<?php } // End if (!$is_modal) ?>
