// assets/js/study-chat.js
// Study Session Chat with Long Polling

class StudyChat {
    constructor(sessionId, userId, userName) {
        this.sessionId = sessionId;
        this.userId = userId;
        this.userName = userName;
        this.messages = [];
        this.lastMessageId = 0;
        this.isConnected = false;
        this.pollingInterval = null;
        this.typingTimeout = null;
        this.sessionStartTime = Date.now();
        
        // DOM elements
        this.messagesContainer = document.getElementById('messages-container');
        this.messageInput = document.getElementById('message-input');
        this.sendButton = document.getElementById('send-button');
        this.connectionStatus = document.getElementById('connection-status');
        this.typingIndicator = document.getElementById('typing-indicator');
        this.participantsList = document.getElementById('participants-list');
        this.sessionTimer = document.getElementById('session-timer');
        
        this.init();
    }
    
    // Initialize chat
    init() {
        this.attachEventListeners();
        this.connect();
        this.loadMessages();
        this.loadParticipants();
        this.startSessionTimer();
    }
    
    // Attach event listeners
    attachEventListeners() {
        // Send message on button click
        this.sendButton.addEventListener('click', () => this.sendMessage());
        
        // Send message on Enter key
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Typing indicator
        this.messageInput.addEventListener('input', () => {
            this.notifyTyping();
        });
    }
    
    // Connect to chat (start polling)
    connect() {
        this.updateConnectionStatus('connecting');
        
        // Start polling for new messages
        this.startPolling();
        
        // Send join notification
        this.sendSystemMessage('joined');
        
        this.isConnected = true;
        this.updateConnectionStatus('connected');
    }
    
    // Start polling for new messages
    startPolling() {
        // Poll every 2 seconds
        this.pollingInterval = setInterval(() => {
            this.loadNewMessages();
        }, 2000);
    }
    
    // Stop polling
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
    
    // Disconnect from chat
    disconnect() {
        this.stopPolling();
        this.sendSystemMessage('left');
        this.isConnected = false;
        this.updateConnectionStatus('disconnected');
    }
    
    // Load initial messages
    async loadMessages() {
        try {
            const response = await fetch(`api/chat.php?action=get_messages&session_id=${this.sessionId}`);
            const result = await response.json();
            
            if (result.success && result.messages) {
                result.messages.forEach(msg => {
                    this.displayMessage(msg);
                    if (msg.id > this.lastMessageId) {
                        this.lastMessageId = msg.id;
                    }
                });
            }
        } catch (error) {
            console.error('Failed to load messages:', error);
        }
    }
    
    // Load new messages (polling)
    async loadNewMessages() {
        if (!this.isConnected) return;
        
        try {
            const response = await fetch(
                `api/chat.php?action=get_new_messages&session_id=${this.sessionId}&last_id=${this.lastMessageId}`
            );
            const result = await response.json();
            
            if (result.success && result.messages && result.messages.length > 0) {
                result.messages.forEach(msg => {
                    this.displayMessage(msg);
                    if (msg.id > this.lastMessageId) {
                        this.lastMessageId = msg.id;
                    }
                });
                
                // Play notification sound for new messages from others
                if (result.messages.some(m => m.user_id !== this.userId)) {
                    this.playNotificationSound();
                }
            }
        } catch (error) {
            console.error('Failed to load new messages:', error);
            this.updateConnectionStatus('disconnected');
            
            // Try to reconnect after 5 seconds
            setTimeout(() => {
                if (!this.isConnected) {
                    this.connect();
                }
            }, 5000);
        }
    }
    
    // Send message
    async sendMessage() {
        const content = this.messageInput.value.trim();
        if (!content) return;
        
        // Disable input while sending
        this.messageInput.disabled = true;
        this.sendButton.disabled = true;
        
        try {
            const response = await fetch('api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send_message',
                    session_id: this.sessionId,
                    message: content,
                    message_type: 'text'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Clear input
                this.messageInput.value = '';
                
                // Message will appear through polling
            } else {
                alert('Failed to send message. Please try again.');
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            alert('Failed to send message. Please check your connection.');
        } finally {
            this.messageInput.disabled = false;
            this.sendButton.disabled = false;
            this.messageInput.focus();
        }
    }
    
    // Send system message
    async sendSystemMessage(type) {
        try {
            await fetch('api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send_system_message',
                    session_id: this.sessionId,
                    system_type: type
                })
            });
        } catch (error) {
            console.error('Failed to send system message:', error);
        }
    }
    
    // Display message in UI
    displayMessage(msg) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${msg.user_id === this.userId ? 'own' : ''} ${msg.message_type === 'system' ? 'system' : ''}`;
        
        if (msg.message_type === 'system') {
            messageDiv.innerHTML = `
                <div class="message-content">
                    <div class="message-bubble">${this.escapeHtml(msg.message)}</div>
                </div>
            `;
        } else {
            const initials = msg.user_name ? msg.user_name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
            const time = this.formatTime(new Date(msg.created_at));
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${initials}</div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">${this.escapeHtml(msg.user_name || 'Unknown')}</span>
                        <span class="message-time">${time}</span>
                    </div>
                    <div class="message-bubble">${this.escapeHtml(msg.message)}</div>
                </div>
            `;
        }
        
        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }
    
    // Notify typing
    notifyTyping() {
        // Clear previous timeout
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }
        
        // Send typing notification
        fetch('api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'notify_typing',
                session_id: this.sessionId
            })
        }).catch(e => console.log('Typing notification failed:', e));
        
        // Reset after 3 seconds
        this.typingTimeout = setTimeout(() => {
            this.typingTimeout = null;
        }, 3000);
    }
    
    // Load participants
    async loadParticipants() {
        try {
            const response = await fetch(`api/chat.php?action=get_participants&session_id=${this.sessionId}`);
            const result = await response.json();
            
            if (result.success && result.participants) {
                this.displayParticipants(result.participants);
            }
        } catch (error) {
            console.error('Failed to load participants:', error);
        }
    }
    
    // Display participants
    displayParticipants(participants) {
        this.participantsList.innerHTML = '';
        
        participants.forEach(participant => {
            const initials = participant.name.split(' ').map(n => n[0]).join('').toUpperCase();
            
            const participantDiv = document.createElement('div');
            participantDiv.className = 'participant';
            participantDiv.innerHTML = `
                <div class="participant-avatar">${initials}</div>
                <div class="participant-info">
                    <div class="participant-name">${this.escapeHtml(participant.name)}</div>
                    <div class="participant-status">
                        <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Online
                    </div>
                </div>
            `;
            
            this.participantsList.appendChild(participantDiv);
        });
    }
    
    // Update connection status
    updateConnectionStatus(status) {
        this.connectionStatus.className = `connection-status ${status}`;
        
        switch (status) {
            case 'connected':
                this.connectionStatus.innerHTML = '<i class="fas fa-check-circle"></i> Connected';
                break;
            case 'connecting':
                this.connectionStatus.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Connecting...';
                break;
            case 'disconnected':
                this.connectionStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Disconnected - Reconnecting...';
                break;
        }
    }
    
    // Start session timer
    startSessionTimer() {
        setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.sessionStartTime) / 1000);
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = elapsed % 60;
            
            this.sessionTimer.textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }
    
    // Scroll to bottom of messages
    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
    
    // Play notification sound
    playNotificationSound() {
        try {
            const audio = new Audio('/assets/sounds/message.mp3');
            audio.volume = 0.3;
            audio.play().catch(e => console.log('Audio play failed:', e));
        } catch (e) {
            console.log('Audio notification failed:', e);
        }
    }
    
    // Escape HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Format time
    formatTime(date) {
        return date.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
}

// Get session ID from URL
function getSessionIdFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

// Initialize chat when page loads
document.addEventListener('DOMContentLoaded', async () => {
    const sessionId = getSessionIdFromUrl();
    
    if (!sessionId) {
        alert('Invalid session ID');
        window.location.href = 'study-groups.php';
        return;
    }
    
    // Get user info from server
    try {
        const response = await fetch('api/get_current_user.php');
        const user = await response.json();
        
        if (user.success) {
            const userName = `${user.data.first_name} ${user.data.last_name}`;
            window.studyChat = new StudyChat(sessionId, user.data.id, userName);
        } else {
            alert('Failed to load user information');
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Failed to load user:', error);
        alert('Connection error. Please refresh the page.');
    }
});

// Disconnect when leaving page
window.addEventListener('beforeunload', () => {
    if (window.studyChat) {
        window.studyChat.disconnect();
    }
});