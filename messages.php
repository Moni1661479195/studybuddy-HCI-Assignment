<?php // Force cache refresh @ 1678886400
require_once 'session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'header.php';
?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
    </style>
    <div class="dashboard-container">
        <div class="dashboard-card">
            <div class="messages-header">
                <h1>Messages</h1>
            </div>
            <div id="conversations-list" class="conversations-list">
                <div class="loader">
                    <i class="fas fa-spinner fa-spin"></i> Loading conversations...
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const conversationsList = document.getElementById('conversations-list');
        const currentUserId = <?php echo $_SESSION['user_id']; ?>; // Get current user ID

        async function fetchConversations() {
            const response = await fetch('api/DOES_NOT_EXIST.php');
            const responseText = await response.text(); // Get the raw text first

            try {
                const data = JSON.parse(responseText); // Manually try to parse
                if (data.success) {
                    renderConversations(data.conversations);
                } else {
                    conversationsList.innerHTML = `<div class="loader">${data.message}</div>`;
                }
            } catch (error) {
                // If JSON parsing fails, display the raw text
                console.error("Error parsing JSON or rendering conversations:", error);
                conversationsList.innerHTML = `<div class="loader"><strong>An error occurred on the server.</strong><br><pre style="text-align: left; background: #eee; padding: 10px; white-space: pre-wrap;">${responseText}</pre></div>`;
            }
        }

        function renderConversations(conversations) {
            if (conversations.length === 0) {
                conversationsList.innerHTML = '<div class="loader">No conversations yet.</div>';
                return;
            }

            conversationsList.innerHTML = ''; // Clear loader

            const groupConversations = conversations.filter(convo => convo.room_type === 'group');
            const directConversations = conversations.filter(convo => convo.room_type === 'direct');

            if (groupConversations.length > 0) {
                conversationsList.innerHTML += '<h2 style="font-size: 1.5rem; color: #1f2937; margin-top: 1.5rem; margin-bottom: 1rem;">Group Messages</h2>';
                groupConversations.forEach(convo => {
                    conversationsList.appendChild(createConversationItem(convo));
                });
            }

            if (directConversations.length > 0) {
                conversationsList.innerHTML += '<h2 style="font-size: 1.5rem; color: #1f2937; margin-top: 1.5rem; margin-bottom: 1rem;">Private Messages</h2>';
                directConversations.forEach(convo => {
                    conversationsList.appendChild(createConversationItem(convo));
                });
            }

            if (groupConversations.length === 0 && directConversations.length === 0) {
                conversationsList.innerHTML = '<div class="loader">No conversations yet.</div>';
            }
        }

        function createConversationItem(convo) {
            const item = document.createElement('a');
            item.href = convo.room_type === 'group' 
                ? `group-chat.php?room_id=${convo.room_id}` 
                : `chat.php?room_id=${convo.room_id}`;
            item.className = 'conversation-item';

            const displayName = convo.name || 'Unknown Conversation';
            const avatarClass = convo.room_type === 'group' ? 'group' : 'direct';
            const avatarIcon = convo.room_type === 'group' 
                ? '<i class="fas fa-users"></i>' 
                : `<span>${displayName.substring(0, 2).toUpperCase()}</span>`;

            const lastMessage = convo.last_message 
                ? (convo.last_message_sender ? `${convo.last_message_sender}: ${convo.last_message}` : convo.last_message)
                : 'No messages yet';

            const lastMessageTime = convo.last_message_time
                ? new Date(convo.last_message_time.replace(' ', 'T')).toLocaleString()
                : '';
            
            // Check for new messages (last message ID is greater than last read message ID)
            const newMessageBadge = (convo.last_message_id && convo.user_last_read_message_id !== null && convo.last_message_id > convo.user_last_read_message_id)
                ? '<span class="new-message-badge">New</span>'
                : '';

            item.innerHTML = `
                <div class="convo-avatar ${avatarClass}">
                    ${avatarIcon}
                </div>
                <div class="convo-details">
                    <div class="convo-name">${displayName} ${newMessageBadge}</div>
                    <div class="convo-last-message">${lastMessage}</div>
                </div>
                <div class="convo-meta">
                    ${lastMessageTime}
                </div>
            `;
            return item;
        }

        fetchConversations();
    });
    </script>
