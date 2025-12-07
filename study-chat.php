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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Session - Chat</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Chat Container */
        .chat-container {
            display: flex;
            height: calc(100vh - 80px);
            max-width: 1400px;
            margin: 2rem auto;
            gap: 1rem;
            padding: 0 1rem;
        }

        /* Sidebar */
        .sidebar {
            width: 300px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1.5rem;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .sidebar h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .participant {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .participant-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .participant-info {
            flex: 1;
        }

        .participant-name {
            font-weight: 600;
            color: #1f2937;
        }

        .participant-status {
            font-size: 0.85rem;
            color: #10b981;
        }

        /* Main Chat Area */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        /* Chat Header */
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
        }

        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-button {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 0.5rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Messages Area */
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
            animation: messageSlideIn 0.3s ease;
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        }

        .message.own .message-bubble {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        /* System message */
        .message.system {
            justify-content: center;
        }

        .message.system .message-bubble {
            background: #fef3c7;
            color: #92400e;
            font-size: 0.9rem;
            text-align: center;
        }

        /* Message Input */
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
            transition: all 0.3s ease;
        }

        .message-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .send-button, #upload-button, #record-button {
            width: 56px;
            height: 56px;
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .send-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }

        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: scale(1);
        }

        #upload-button, #record-button {
            background: #f0f0f0;
            color: #333;
        }

        .recording-wrapper { display: flex; align-items: center; justify-content: space-between; width: 100%; }
        .recording-status { display: flex; align-items: center; gap: 0.5rem; color: #d9534f; font-weight: 600; }
        #cancel-record-button, #send-record-button { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #4b5563; transition: color 0.3s; }
        #cancel-record-button:hover { color: #d9534f; }
        #send-record-button { color: #28a745; }
        #send-record-button:hover { color: #218838; }

        /* Typing indicator */
        .typing-indicator {
            display: none;
            padding: 0.5rem 1rem;
            color: #6b7280;
            font-size: 0.9rem;
            font-style: italic;
        }

        .typing-indicator.active {
            display: block;
        }

        /* Connection status */
        .connection-status {
            padding: 0.5rem 1rem;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .connection-status.connected {
            background: #d1fae5;
            color: #065f46;
        }

        .connection-status.disconnected {
            background: #fee2e2;
            color: #991b1b;
        }

        .connection-status.connecting {
            background: #fef3c7;
            color: #92400e;
        }

        @media (max-width: 968px) {
            .chat-container {
                flex-direction: column;
                height: auto;
            }

            .sidebar {
                width: 100%;
            }

            .message-content {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h3><i class="fas fa-users"></i> Participants</h3>
            <div id="participants-list">
                <!-- Will be populated by JavaScript -->
            </div>

            <!-- Timer Widget (if integrated) -->
            <div style="margin-top: 2rem;">
                <h3><i class="fas fa-clock"></i> Session Time</h3>
                <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 0.75rem; margin-top: 0.5rem;">
                    <div id="session-timer" style="font-size: 2rem; font-weight: 700; color: #667eea;">00:00:00</div>
                </div>
            </div>
        </aside>

        <!-- Main Chat -->
        <main class="chat-main">
            <!-- Connection Status -->
            <div id="connection-status" class="connection-status connecting">
                <i class="fas fa-circle-notch fa-spin"></i> Connecting to chat...
            </div>

            <!-- Chat Header -->
            <header class="chat-header">
                <h2><i class="fas fa-comments"></i> Study Session Chat</h2>
                <div class="chat-actions">
                    <button class="action-button" onclick="location.href='study-groups.php'">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                </div>
            </header>

            <!-- Messages -->
            <div id="messages-container" class="messages-container">
                <!-- Messages will be added here -->
            </div>

            <!-- Typing Indicator -->
            <div id="typing-indicator" class="typing-indicator">
                <i class="fas fa-ellipsis-h"></i> Someone is typing...
            </div>

            <!-- Message Input -->
            <div class="message-input-container">
                <div class="message-input-wrapper" id="input-wrapper">
                    <input type="file" id="file-input" style="display: none;" />
                    <button id="upload-button" title="Upload File" class="send-button"><i class="fas fa-paperclip"></i></button>
                    <button id="record-button" title="Record Audio" class="send-button"><i class="fas fa-microphone"></i></button>
                    <input 
                        type="text" 
                        id="message-input" 
                        class="message-input" 
                        placeholder="Type a message..." 
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
        </main>
    </div>

    <script src="assets/js/study-chat.js"></script>
</body>
</html>