<div id="aibot-widget-container" class="aibot-widget-container">
    <!-- Circular Robot Icon (Toggle Button) -->
    <button id="aibot-toggle-button" class="aibot-toggle-button">
        <i class="fas fa-robot"></i> <!-- Font Awesome robot icon -->
    </button>

    <!-- Chat Window -->
    <div id="aibot-chat-window" class="aibot-chat-window hidden">
        <div class="aibot-chat-header">
            <h3>AI Assistant</h3>
            <button id="aibot-close-button" class="aibot-close-button">&times;</button>
        </div>
        <div id="aibot-messages" class="aibot-messages">
            <!-- Chat messages will be appended here -->
            <div class="aibot-message bot-message">
                <div class="aibot-avatar"><i class="fas fa-robot"></i></div>
                <div class="aibot-message-bubble">Hello! How can I help you today?</div>
            </div>
        </div>
        <div class="aibot-chat-input">
            <input type="text" id="aibot-user-input" placeholder="Type your message...">
            <button id="aibot-send-button"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>
