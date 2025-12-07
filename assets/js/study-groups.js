document.addEventListener('DOMContentLoaded', () => {

    // --- MODAL CONTROLLERS ---

    // Create Group Modal
    const createGroupModal = document.getElementById('createGroupModal');
    if (createGroupModal) {
        window.openCreateModal = () => createGroupModal.classList.add('active');
        window.closeCreateModal = () => createGroupModal.classList.remove('active');
        createGroupModal.addEventListener('click', function(e) {
            if (e.target === this) {
                window.closeCreateModal();
            }
        });
    }

    // Quick Match Modal Controller
    class QuickMatchModal {
        constructor() {
            this.modal = document.getElementById('quick-match-modal');
            if (!this.modal) return; // Do nothing if modal doesn't exist

            this.searchingState = document.getElementById('searching-state');
            this.matchFoundState = document.getElementById('match-found-state');
            this.queueInfo = document.getElementById('queue-info');
            this.queueCount = document.getElementById('queue-count');
            
            this.matchedUser = null;
            this.searchInterval = null;
            this.queueCheckInterval = null;

            this.bindEvents();
        }

        bindEvents() {
            document.getElementById('start-quick-match-btn')?.addEventListener('click', () => this.open());
            document.getElementById('cancel-search')?.addEventListener('click', () => this.cancelSearch());
            document.getElementById('send-request-from-match')?.addEventListener('click', () => this.sendRequest());
            document.getElementById('view-profile-from-match')?.addEventListener('click', () => this.viewProfile());
            document.getElementById('close-match-modal')?.addEventListener('click', () => this.close());
        }

        open() {
            this.modal.classList.add('active');
            this.showSearching();
            this.startSearch();
        }

        close() {
            this.modal.classList.remove('active');
            this.stopSearch();
            this.matchedUser = null;
            const sendBtn = document.getElementById('send-request-from-match');
            if(sendBtn) {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Study Request';
            }
        }

        showSearching() {
            this.searchingState.style.display = 'block';
            this.matchFoundState.classList.remove('active');
        }

        showMatchFound(user) {
            this.matchedUser = user;
            this.searchingState.style.display = 'none';
            this.matchFoundState.classList.add('active');
            
            const userCard = document.getElementById('matched-user-card');
            const initials = (user.first_name[0] + user.last_name[0]).toUpperCase();
            
            userCard.innerHTML = `
                <div class="matched-user-avatar">${initials}</div>
                <div class="matched-user-info">
                    <div class="matched-user-name">${user.first_name} ${user.last_name}</div>
                    <div class="matched-user-email">Skill Level: ${user.skill_level || 'Not set'}</div>
                    <div class="matched-user-gender">Gender: ${user.gender || 'Not set'}</div>
                </div>
            `;
        }

        async startSearch() {
            const desiredSkillLevel = document.getElementById('desired-skill-level-select').value;
            const desiredGender = document.getElementById('desired-gender-select').value;
            try {
                const response = await fetch('api/quick_match.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'join_queue',
                        desiredSkillLevel: desiredSkillLevel,
                        desiredGender: desiredGender
                    })
                });
                const result = await response.json();
                if (result.success) {
                    this.searchInterval = setInterval(() => this.checkForMatch(), 3000);
                    this.queueCheckInterval = setInterval(() => this.updateQueueCount(), 5000);
                    this.updateQueueCount();
                } else {
                    throw new Error(result.message || 'Failed to join queue');
                }
            } catch (error) {
                console.error('Search error:', error);
                alert('Failed to start search. Please try again.');
                this.close();
            }
        }

        async checkForMatch() {
            try {
                const response = await fetch('api/quick_match.php?action=check_match');
                const result = await response.json();
                if (result.matched) {
                    this.stopSearch();
                    this.showMatchFound(result.user);
                }
            } catch (error) {
                console.error('Check match error:', error);
            }
        }

        async updateQueueCount() {
            try {
                const response = await fetch('api/quick_match.php?action=queue_count');
                const result = await response.json();
                if (this.queueInfo && this.queueCount) {
                    if (result.count > 1) {
                        this.queueInfo.style.display = 'block';
                        this.queueCount.textContent = result.count;
                    } else {
                        this.queueInfo.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Queue count error:', error);
            }
        }

        stopSearch() {
            clearInterval(this.searchInterval);
            this.searchInterval = null;
            clearInterval(this.queueCheckInterval);
            this.queueCheckInterval = null;
        }

        async cancelSearch() {
            try {
                await fetch('api/quick_match.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'leave_queue' })
                });
            } catch (error) {
                console.error('Cancel error:', error);
            } finally {
                this.close();
            }
        }

        async sendRequest() {
            if (!this.matchedUser) return;

            const sendBtn = document.getElementById('send-request-from-match');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            const formData = new FormData();
            formData.append('action', 'send_study_request');
            formData.append('receiver_id', this.matchedUser.id);

            try {
                // We send this request to the main study-groups page to leverage its action handling
                const response = await fetch('study-groups.php', { method: 'POST', body: formData });
                // We don't need to process the response, just know it was sent.
                sendBtn.innerHTML = '<i class="fas fa-check"></i> Request Sent!';
                setTimeout(() => this.close(), 2000);
            } catch (error) {
                console.error('Send request error:', error);
                alert('Failed to send the request. Please try again.');
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Study Request';
            }
        }

        viewProfile() {
            if (this.matchedUser) {
                window.location.href = `user_profile.php?id=${this.matchedUser.id}`;
            }
        }
    }

    // Initialize the modal controller
    new QuickMatchModal();






    // --- LIVE UPDATES & AJAX FORM SUBMISSIONS ---

    const dashboardCard = document.querySelector('.dashboard-card');
    if (!dashboardCard) return;

    const preserveScroll = (promise) => {
        const scrollY = window.scrollY;
        return promise.then(() => {
            window.scrollTo(0, scrollY);
        });
    };

    dashboardCard.addEventListener('submit', e => {
        const form = e.target;
        if (form.matches('form[action="study-groups.php"]') && !form.classList.contains('no-ajax')) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('study-groups.php', { method: 'POST', body: formData })
                .then(response => response.text()) // We still need to fetch the response to handle potential messages
                .then(() => {
                    // After the form submission, trigger an immediate update of all sections
                    checkForUpdates();
                })
                .then(() => preserveScroll(Promise.resolve()));
        }
    });

    const checkForUpdates = async () => {
        try {
            const response = await fetch(`ajax/check_updates.php?_=${new Date().getTime()}`);
            const data = await response.json();

            // Sections to update, now including groups and invitations
            const sections = {
                '#my-groups-section': data.my_groups_html,
                '#invitations-section': data.invitations_html,
                '#study-mates-section': data.study_mates_html,
                '#requests-section': data.received_requests_html,
                '#sent-requests-section': data.sent_requests_html,
                '#recommended-partners-section': data.recommendations_html
            };

            for (const [selector, html] of Object.entries(sections)) {
                const section = document.querySelector(selector);

                if (selector === '#requests-section') {
                    console.log("--- Checking #requests-section ---");
                    console.log("Old HTML:", section.innerHTML.trim());
                    console.log("New HTML:", html.trim());
                    console.log("HTML is different:", section.innerHTML.trim() !== html.trim());
                }

                if (section && html !== undefined && section.innerHTML.trim() !== html.trim()) {
                    section.innerHTML = html;
                    // If the recommended partners section was just updated, re-attach the event listener
                    if (selector === '#recommended-partners-section') {
                        const refreshButton = section.querySelector('#refresh-suggestions-button');
                        if (refreshButton) {
                            refreshButton.removeEventListener('click', checkForUpdates); // Prevent duplicates
                            refreshButton.addEventListener('click', checkForUpdates);
                        }
                    }
                }
            }

        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    };

    // Initial check for updates to populate all sections
    checkForUpdates();

    // Set up polling to check for updates every 5 seconds
    setInterval(checkForUpdates, 5000);

    // --- SEARCH SUGGESTIONS ---

    const searchInput = document.querySelector('input[name="search_user"]');
    const suggestionsContainer = document.getElementById('search-suggestions');
    const searchForm = document.getElementById('search-form');

    if (searchInput && suggestionsContainer && searchForm) {
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.trim();

            if (searchTerm.length < 1) {
                suggestionsContainer.style.display = 'none';
                return;
            }

            suggestionsContainer.style.display = 'block';
            suggestionsContainer.innerHTML = '<div class="suggestion-item">Loading...</div>';

            fetch(`ajax/search_suggestions.php?q=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsContainer.innerHTML = '';
                    if (data.suggestions && data.suggestions.length > 0) {
                        data.suggestions.forEach(user => {
                            const item = document.createElement('div');
                            item.classList.add('suggestion-item');

                            let avatarHtml = '';
                            if (user.profile_picture_path) {
                                avatarHtml = `<img src="${user.profile_picture_path}" alt="${user.first_name}'s avatar" class="user-avatar" style="width: 32px; height: 32px; margin-right: 10px;">`;
                            } else {
                                const initials = (user.first_name[0] + (user.last_name ? user.last_name[0] : '')).toUpperCase();
                                avatarHtml = `<div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.9rem; margin-right: 10px;">${initials}</div>`;
                            }

                            item.innerHTML = `
                                <div style="display: flex; align-items: center;">
                                    ${avatarHtml}
                                    <span>${user.first_name} ${user.last_name} (${user.email})</span>
                                </div>
                            `;

                            item.addEventListener('click', () => {
                                window.location.href = `user_profile.php?id=${user.id}`;
                            });
                            suggestionsContainer.appendChild(item);
                        });
                    } else {
                        suggestionsContainer.innerHTML = '<div class="suggestion-item" style="color: #6c757d;">No users found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    suggestionsContainer.innerHTML = `<div class="suggestion-item" style="color: #dc3545;">Error: ${error.message}</div>`;
                });
        });

        // Hide suggestions when clicking outside the form
        document.addEventListener('click', (e) => {
            if (!searchForm.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    }
});