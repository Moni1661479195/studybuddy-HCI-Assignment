<?php
require_once 'session.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
    <?php include 'header.php'; ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - Messages</title>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/modern_auth.css">
    <link rel="stylesheet" href="assets/css/messages.css">
</head>
<body class="bg-gray-100">



<div class="dashboard-container mt-24 md:mt-28">
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

    <?php include 'footer.php'; ?>

<!-- Chat Modal -->
<div id="chatModal" class="modal-overlay">
    
    <div class="modal-container" style="display: flex; flex-direction: column; height: 85vh; max-height: 90vh;">
        
        <div class="modal-header">
            <button id="chat-close-button" class="close-button">&times;</button>
        </div>
        
        <div id="chatModalContent" class="modal-body" style="flex-grow: 1; overflow: hidden;">
            </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const conversationsList = document.getElementById('conversations-list');
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;
    const chatModal = document.getElementById('chatModal');
    const chatModalContent = document.getElementById('chatModalContent');
    const closeButton = document.getElementById('chat-close-button');

    if (closeButton) {
        closeButton.addEventListener('click', function() {
            closeChatModal();
        });
    }

    // Function to open the chat modal
    function openChatModal(chatUrl) {
        const url = new URL(chatUrl);
        url.searchParams.append('modal', 'true');
        chatModalContent.innerHTML = '<iframe id="chatIframe" src="' + url.toString() + '" frameborder="0" style="width: 100%; height: 100%;"></iframe>';
        chatModal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent scrolling on body
    }

    // Function to close the chat modal
    function closeChatModal() {
        chatModal.classList.remove('active');
        document.body.style.overflow = ''; // Restore body scrolling
        chatModalContent.innerHTML = ''; // Clear content
    }

    // Close modal when clicking outside the content
    chatModal.addEventListener('click', function(event) {
        if (event.target === chatModal) {
            closeChatModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && chatModal.classList.contains('active')) {
            closeChatModal();
        }
    });

    async function fetchConversations() {
        try {
            const response = await fetch('api/chat_api.php?action=get_conversations');
            const data = await response.json();
            if (data.success) {
                renderConversations(data.conversations);
            } else {
                conversationsList.innerHTML = `<div class="loader">${data.message}</div>`;
            }
        } catch (error) {
            console.error("Error fetching or rendering conversations:", error);
            conversationsList.innerHTML = `<div class="loader"><strong>An error occurred while loading messages.</strong></div>`;
        }
    }

    function formatRelativeTime(sqlTimestamp) {
        if (!sqlTimestamp) return '';
        const date = new Date(sqlTimestamp.replace(' ', 'T'));
        const now = new Date();
        const diffSeconds = Math.round((now - date) / 1000);
        const diffMinutes = Math.round(diffSeconds / 60);
        const diffHours = Math.round(diffMinutes / 60);
        const diffDays = Math.round(diffHours / 24);

        if (diffSeconds < 60) return 'Just now';
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString();
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
            const groupHeader = document.createElement('h2');
            groupHeader.style.cssText = 'font-size: 1.5rem; color: #1f2937; margin-top: 1.5rem; margin-bottom: 1rem;';
            groupHeader.textContent = 'Group Messages';
            conversationsList.appendChild(groupHeader);

            groupConversations.forEach(convo => {
                conversationsList.appendChild(createConversationItem(convo));
            });
        }

        if (directConversations.length > 0) {
            const directHeader = document.createElement('h2');
            directHeader.style.cssText = 'font-size: 1.5rem; color: #1f2937; margin-top: 1.5rem; margin-bottom: 1rem;';
            directHeader.textContent = 'Private Messages';
            conversationsList.appendChild(directHeader);

            directConversations.forEach(convo => {
                conversationsList.appendChild(createConversationItem(convo));
            });
        }
    }

    function createConversationItem(convo) {
        const isUnread = convo.last_message_id && convo.user_last_read_message_id !== null && convo.last_message_id > convo.user_last_read_message_id;
        const itemClass = isUnread ? 'conversation-item unread' : 'conversation-item';

        const item = document.createElement('a');
        item.href = convo.room_type === 'group' 
            ? `group-chat.php?room_id=${convo.room_id}` 
            : `chat.php?room_id=${convo.room_id}`;
        item.className = itemClass;

        // Prevent default navigation and open modal instead
        item.addEventListener('click', function(e) {
            e.preventDefault();
            openChatModal(this.href);
        });

        const displayName = convo.name || 'Unknown Conversation';
        const avatarClass = convo.room_type === 'group' ? 'group' : 'direct';
        
        let avatarContent;
        if (convo.profile_picture_path) {
            avatarContent = `<img src="${convo.profile_picture_path}" alt="Avatar" class="convo-avatar-img">`;
        } else {
            const avatarInitials = (convo.name || '?').substring(0, 2).toUpperCase();
            avatarContent = `<span>${avatarInitials}</span>`;
        }

        let lastMessageText = '<i>No messages yet.</i>';
        if (convo.last_message) {
            const senderPrefix = parseInt(convo.last_message_sender_id, 10) === currentUserId
                ? 'You: '
                : '';
            const truncatedMessage = convo.last_message.length > 40
                ? convo.last_message.substring(0, 40) + '...'
                : convo.last_message;
            lastMessageText = senderPrefix + truncatedMessage;
        }

        const lastMessageTime = formatRelativeTime(convo.last_message_time);
        const newMessageBadge = isUnread ? '<span class="new-message-badge">New</span>' : '';

        item.innerHTML = `
            <div class="convo-avatar ${avatarClass}">
                ${avatarContent}
            </div>
            <div class="convo-details">
                <div class="convo-name">${displayName} ${newMessageBadge}</div>
                <div class="convo-last-message">${lastMessageText}</div>
            </div>
            <div class="convo-meta">
                ${lastMessageTime}
            </div>
        `;
        return item;
    }

    fetchConversations();
    setInterval(fetchConversations, 5000);
});
</script>
</body>