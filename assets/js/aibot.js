document.addEventListener('DOMContentLoaded', function() {
    const aibotWidgetContainer = document.getElementById('aibot-widget-container');
    const aibotToggleButton = document.getElementById('aibot-toggle-button');
    const aibotChatWindow = document.getElementById('aibot-chat-window');
    const aibotCloseButton = document.getElementById('aibot-close-button');
    const aibotMessages = document.getElementById('aibot-messages');
    const aibotUserInput = document.getElementById('aibot-user-input');
    const aibotSendButton = document.getElementById('aibot-send-button');

    // --- State Management with sessionStorage ---
    let chatHistory = JSON.parse(sessionStorage.getItem('aibotHistory')) || [];
    let isAibotOpen = false;
    let isProcessing = false;

    function saveHistory() {
        sessionStorage.setItem('aibotHistory', JSON.stringify(chatHistory));
    }

    // --- UI Toggling ---
    aibotToggleButton.addEventListener('click', toggleChatWindow);
    aibotCloseButton.addEventListener('click', toggleChatWindow);

    function toggleChatWindow() {
        isAibotOpen = !isAibotOpen;
        if (isAibotOpen) {
            aibotChatWindow.classList.remove('hidden');
            void aibotChatWindow.offsetWidth; // Force reflow
            aibotChatWindow.classList.add('visible');
            aibotToggleButton.style.display = 'none';
            aibotUserInput.focus();
            scrollToBottom();
            // If chat is empty, send initial greeting
            if (chatHistory.length === 0) {
                sendToAibot('INIT_GREETING', false); // Don't add INIT_GREETING to history
            }
        } else {
            aibotChatWindow.classList.remove('visible');
            aibotChatWindow.classList.add('hidden');
            aibotToggleButton.style.display = 'flex';
        }
    }

    // --- Message Display & History Management ---
    function displayMessage(role, content, shouldSave = true) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('aibot-message', role === 'user' ? 'user-message' : 'bot-message');

        const avatarElement = document.createElement('div');
        avatarElement.classList.add('aibot-avatar');
        avatarElement.innerHTML = role === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
        
        const bubbleElement = document.createElement('div');
        bubbleElement.classList.add('aibot-message-bubble');
        bubbleElement.innerHTML = content;

        if (role === 'user') {
            messageElement.appendChild(bubbleElement);
            messageElement.appendChild(avatarElement);
        } else {
            messageElement.appendChild(avatarElement);
            messageElement.appendChild(bubbleElement);
        }
        aibotMessages.appendChild(messageElement);
        scrollToBottom();

        if (shouldSave) {
            chatHistory.push({ role, content });
            saveHistory();
        }
    }

    function renderHistory() {
        aibotMessages.innerHTML = '';
        chatHistory.forEach(msg => {
            displayMessage(msg.role, msg.content, false); // Render without re-saving
        });
    }

    function scrollToBottom() {
        aibotMessages.scrollTop = aibotMessages.scrollHeight;
    }

    function showLoadingIndicator() {
        const loadingElement = document.createElement('div');
        loadingElement.id = 'aibot-loading-indicator';
        loadingElement.classList.add('aibot-message', 'bot-message');
        loadingElement.innerHTML = '<div class="aibot-avatar"><i class="fas fa-robot"></i></div><div class="aibot-message-bubble"><i class="fas fa-spinner fa-spin"></i></div>';
        aibotMessages.appendChild(loadingElement);
        scrollToBottom();
    }

    function removeLoadingIndicator() {
        const loadingElement = document.getElementById('aibot-loading-indicator');
        if (loadingElement) {
            loadingElement.remove();
        }
    }

    // --- Sending Messages ---
    aibotSendButton.addEventListener('click', handleUserInput);
    aibotUserInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleUserInput();
        }
    });

    function handleUserInput(message = null) {
        if (isProcessing) return;

        const directMessage = (typeof message === 'string' || message instanceof String) ? message : null;
        const userMessage = directMessage || aibotUserInput.value.trim();
        if (userMessage === '') return;

        displayMessage('user', userMessage); // This now also saves the user message
        aibotUserInput.value = '';
        
        sendToAibot(userMessage);
    }

    // --- Backend Communication ---
    async function sendToAibot(message, addToHistory = true) {
        isProcessing = true;
        showLoadingIndicator();

        try {
            const response = await fetch('api/gemini_chat_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    prompt: message,
                    history: chatHistory 
                }),
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const data = await response.json();
            removeLoadingIndicator();

            if (data.success) {
                displayMessage('bot', data.response); // This now also saves the bot message
                if (data.featureButtons && data.featureButtons.length > 0) {
                    displayFeatureButtons(data.featureButtons);
                }
            } else {
                displayMessage('bot', 'Oops! Something went wrong: ' + (data.message || 'Unknown error.'), false);
            }
        } catch (error) {
            console.error('Error communicating with AI bot backend:', error);
            removeLoadingIndicator();
            displayMessage('bot', 'I\'m having trouble connecting right now. Please try again later.', false);
        } finally {
            isProcessing = false;
        }
    }

    // --- Feature Buttons for Guest Mode ---
    function displayFeatureButtons(buttons) {
        const buttonsContainer = document.createElement('div');
        buttonsContainer.classList.add('aibot-feature-buttons');
        buttons.forEach(buttonText => {
            const button = document.createElement('button');
            button.classList.add('aibot-feature-button');
            button.textContent = buttonText;
            button.addEventListener('click', function() {
                // Remove buttons after one is clicked to prevent clutter
                buttonsContainer.remove();
                handleUserInput(buttonText);
            });
            buttonsContainer.appendChild(button);
        });
        aibotMessages.appendChild(buttonsContainer);
        scrollToBottom();
    }

    // --- Initial Load ---
    renderHistory();
});
