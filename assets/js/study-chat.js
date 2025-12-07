// assets/js/study-chat.js
// Merged and updated for file upload functionality

document.addEventListener('DOMContentLoaded', async () => {
    const groupId = new URLSearchParams(window.location.search).get('id');

    if (!groupId) {
        alert('Invalid group ID');
        window.location.href = 'study-groups.php';
        return;
    }

    // ROOM_ID is passed from study-chat.php
    if (typeof ROOM_ID === 'undefined') {
        alert('Could not identify chat room. Returning to groups page.');
        window.location.href = 'study-groups.php';
        return;
    }

    try {
        const response = await fetch('api/get_current_user.php');
        const user = await response.json();

        if (user.success) {
            window.studyChat = new StudyChat(ROOM_ID, user.data.id, user.data.first_name, user.data.last_name);
        } else {
            alert('Failed to load user information');
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Failed to load user:', error);
        alert('Connection error. Please refresh the page.');
    }
});

class StudyChat {
    constructor(roomId, userId, userFirstName, userLastName) {
        this.roomId = roomId;
        this.userId = userId;
        this.currentUserInitials = (userFirstName[0] + userLastName[0]).toUpperCase();
        this.lastMessageId = 0;
        this.pollingInterval = null;

        // DOM elements
        this.messagesContainer = document.getElementById('messages-container');
        this.messageInput = document.getElementById('message-input');
        this.sendButton = document.getElementById('send-button');
        this.uploadButton = document.getElementById('upload-button');
        this.fileInput = document.getElementById('file-input');
        this.recordButton = document.getElementById('record-button');
        this.inputWrapper = document.getElementById('input-wrapper');
        this.recordingWrapper = document.getElementById('recording-wrapper');
        this.recordTimer = document.getElementById('record-timer');
        this.cancelRecordButton = document.getElementById('cancel-record-button');
        this.sendRecordButton = document.getElementById('send-record-button');

        this.isRecording = false;
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.timerInterval = null;

        // Group-specific elements
        this.participantsList = document.getElementById('participants-list');
        this.sessionTimer = document.getElementById('session-timer');
        this.connectionStatus = document.getElementById('connection-status');

        this.init();
    }

    init() {
        this.updateConnectionStatus('connected');
        this.attachEventListeners();
        this.fetchMessages(true); // Initial fetch
        this.startPolling();
        this.startSessionTimer();
        // Participants could be loaded here if needed
    }

    attachEventListeners() {
        this.sendButton.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // File upload listeners
        this.uploadButton.addEventListener('click', () => this.fileInput.click());
        this.fileInput.addEventListener('change', (e) => this.uploadFile(e.target.files[0]));

        // Record audio listeners
        this.cancelRecordButton.addEventListener('click', () => this.cancelRecording());
        this.sendRecordButton.addEventListener('click', () => this.sendRecording());
    }

    async startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.mediaRecorder = new MediaRecorder(stream);
            this.audioChunks = [];
            this.mediaRecorder.ondataavailable = event => {
                this.audioChunks.push(event.data);
            };
            this.mediaRecorder.onstop = () => {
                const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                const audioFile = new File([audioBlob], 'voice-message.webm', { type: 'audio/webm' });
                this.uploadFile(audioFile, true);
            };
            this.mediaRecorder.start();

            this.inputWrapper.style.display = 'none';
            this.recordingWrapper.style.display = 'flex';

            let seconds = 0;
            this.recordTimer.textContent = '00:00';
            this.timerInterval = setInterval(() => {
                seconds++;
                const minutes = Math.floor(seconds / 60).toString().padStart(2, '0');
                const secs = (seconds % 60).toString().padStart(2, '0');
                this.recordTimer.textContent = `${minutes}:${secs}`;
            }, 1000);

        } catch (err) {
            console.error('Error accessing microphone:', err);
            alert('Could not access microphone. Please ensure you have given permission.');
        }
    }

    stopRecording(discard = false) {
        if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
            this.mediaRecorder.stop();
        }
        if (this.mediaRecorder && this.mediaRecorder.stream) {
            this.mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }
        clearInterval(this.timerInterval);
        this.inputWrapper.style.display = 'flex';
        this.recordingWrapper.style.display = 'none';
    }

    cancelRecording() {
        this.stopRecording(true);
    }

    sendRecording() {
        this.stopRecording(false);
    }

    async uploadFile(file, isAudio = false) {
        if (!file) return;

        const formData = new FormData();
        formData.append('room_id', this.roomId);
        formData.append('file', file);

        if (!isAudio) {
            this.uploadButton.disabled = true;
        }

        try {
            const response = await fetch('api/upload_file.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (!data.success) {
                alert('Failed to upload file: ' + data.message);
            }
            // Success will be handled by the polling mechanism

        } catch (error) {
            console.error('Error uploading file:', error);
            alert('An error occurred while uploading the file.');
        } finally {
            if (!isAudio) {
                this.fileInput.value = ''; // Reset file input
                this.uploadButton.disabled = false;
            }
        }
    }

    async fetchMessages(isInitialLoad = false) {
        const sinceId = isInitialLoad ? 0 : this.lastMessageId;
        try {
            const response = await fetch(`api/chat_api.php?action=get_messages&room_id=${this.roomId}&last_id=${sinceId}`);
            const data = await response.json();
            if (data.success) {
                data.messages.forEach(msg => this.displayMessage(msg));
                if (isInitialLoad) {
                    this.scrollToBottom();
                }
            } else {
                console.error('Failed to fetch messages:', data.message);
            }
        } catch (error) {
            console.error('Error fetching messages:', error);
            this.updateConnectionStatus('disconnected');
        }
    }

    async sendMessage() {
        const messageText = this.messageInput.value.trim();
        if (messageText === '') return;

        const originalText = messageText;
        this.messageInput.value = '';
        this.sendButton.disabled = true;

        try {
            const response = await fetch('api/chat_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send_message', room_id: this.roomId, message: messageText })
            });
            const data = await response.json();
            if (!data.success) {
                this.messageInput.value = originalText; // Restore on failure
                alert('Failed to send message: ' + data.message);
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.messageInput.value = originalText;
        } finally {
            this.sendButton.disabled = false;
            this.messageInput.focus();
        }
    }

    displayMessage(msg) {
        const isOwn = parseInt(msg.user_id, 10) === this.userId;
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isOwn ? 'own' : ''}`;

        let messageBody = '';
        if (msg.message) {
            messageBody = `<div class="message-bubble">${this.escapeHtml(msg.message)}</div>`;
        } else if (msg.file_path) {
            const fileName = msg.file_path.split('/').pop();
            const fileExtension = fileName.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            const audioExtensions = ['webm', 'ogg', 'mp3', 'wav'];

            let fileDisplay;
            if (imageExtensions.includes(fileExtension)) {
                fileDisplay = `
                    <a href="${msg.file_path}" target="_blank" title="View full image">
                        <img src="${msg.file_path}" alt="${fileName}" style="max-width: 250px; border-radius: 10px; display: block;">
                    </a>`;
            } else if (audioExtensions.includes(fileExtension)) {
                fileDisplay = `<audio controls src="${msg.file_path}"></audio>`;
            } else {
                let iconClass = 'fas fa-file-alt'; // Default icon
                if (['pdf'].includes(fileExtension)) iconClass = 'fas fa-file-pdf';
                if (['doc', 'docx'].includes(fileExtension)) iconClass = 'fas fa-file-word';
                if (['xls', 'xlsx', 'csv'].includes(fileExtension)) iconClass = 'fas fa-file-excel';

                fileDisplay = `
                    <a href="${msg.file_path}" target="_blank" download class="file-download-link">
                        <i class="${iconClass}"></i> 
                        <span>${this.escapeHtml(fileName)}</span>
                    </a>`;
            }
            messageBody = `<div class="message-bubble file-bubble">${fileDisplay}</div>`;
        }

        const senderName = isOwn ? 'You' : msg.user_name;
        const initials = (msg.user_name || 'U').split(' ').map(n => n[0]).join('').toUpperCase();

        messageDiv.innerHTML = `
            <div class="message-avatar">${initials}</div>
            <div class="message-content">
                <div class="message-header">
                     <span class="message-sender">${this.escapeHtml(senderName)}</span>
                     <span class="message-time">${new Date(msg.created_at.replace(' ', 'T')).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                </div>
                ${messageBody}
            </div>
        `;
        this.messagesContainer.appendChild(messageDiv);
        this.lastMessageId = Math.max(this.lastMessageId, msg.id);
        this.scrollToBottom();
    }

    startPolling() {
        this.pollingInterval = setInterval(() => this.fetchMessages(), 2000);
    }

    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
    }

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
                this.connectionStatus.innerHTML = '<i class="fas fa-exclamation-circle"></i> Connection issue';
                break;
        }
    }

    startSessionTimer() {
        const startTime = Date.now();
        if(!this.sessionTimer) return;
        setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const h = String(Math.floor(elapsed / 3600)).padStart(2, '0');
            const m = String(Math.floor((elapsed % 3600) / 60)).padStart(2, '0');
            const s = String(elapsed % 60).padStart(2, '0');
            this.sessionTimer.textContent = `${h}:${m}:${s}`;
        }, 1000);
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    escapeHtml(text) {
        if (typeof text !== 'string') return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
