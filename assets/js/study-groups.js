function openCreateModal() {
            document.getElementById('createGroupModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createGroupModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('createGroupModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateModal();
            }
        });

            // Quick Match Modal Controller
    class QuickMatchModal {
        constructor() {
            this.modal = document.getElementById('quick-match-modal');
            this.searchingState = document.getElementById('searching-state');
            this.matchFoundState = document.getElementById('match-found-state');
            this.queueInfo = document.getElementById('queue-info');
            this.queueCount = document.getElementById('queue-count');
            
            this.matchedUser = null; // Store matched user info
            this.searchInterval = null;
            this.queueCheckInterval = null;
        }
    
        open() {
            this.modal.classList.add('active');
            this.showSearching();
            this.startSearch();
        }
    
        close() {
            this.modal.classList.remove('active');
            this.stopSearch();
            this.matchedUser = null; // Clear user info
            // Restore button state for next use
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
            this.matchedUser = user; // Key: store the matched user
            this.searchingState.style.display = 'none';
            this.matchFoundState.classList.add('active');
            
            const userCard = document.getElementById('matched-user-card');
            const initials = (user.first_name[0] + user.last_name[0]).toUpperCase();
            
            userCard.innerHTML = `
                <div class="matched-user-avatar">${initials}</div>
                <div class="matched-user-info">
                    <div class="matched-user-name">${user.first_name} ${user.last_name}</div>
                    <div class="matched-user-email">Skill Level: ${user.skill_level || 'Not set'}</div>
                </div>
            `;
        }
    
        async startSearch() {
            const desiredSkillLevel = document.getElementById('desired-skill-level-select').value;
            try {
                const response = await fetch('api/quick_match.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'join_queue',
                        desiredSkillLevel: desiredSkillLevel
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
    
        // --- New Method: Send Study Request ---
        async sendRequest() {
            if (!this.matchedUser) return;
    
            const sendBtn = document.getElementById('send-request-from-match');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
            const formData = new FormData();
            formData.append('action', 'send_study_request');
            formData.append('receiver_id', this.matchedUser.id);
    
            try {
                const response = await fetch('study-groups.php', {
                    method: 'POST',
                    body: formData
                });
    
                // Regardless of backend response, show confirmation on frontend
                sendBtn.innerHTML = '<i class="fas fa-check"></i> Request Sent!';
                // Close modal after 2 seconds
                setTimeout(() => {
                    this.close();
                }, 2000);
    
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
    
    // Initialize modal
    const quickMatchModal = new QuickMatchModal();
    
    // --- Updated Event Listeners ---
    document.addEventListener('DOMContentLoaded', () => {
        // Listen for main button
        document.getElementById('start-quick-match-btn')?.addEventListener('click', () => {
            quickMatchModal.open();
        });
    
        // Listen for modal internal buttons
        document.getElementById('cancel-search')?.addEventListener('click', () => {
            quickMatchModal.cancelSearch();
        });
    
        document.getElementById('send-request-from-match')?.addEventListener('click', () => {
            quickMatchModal.sendRequest();
        });
    
        document.getElementById('view-profile-from-match')?.addEventListener('click', () => {
            quickMatchModal.viewProfile();
        });
        
        document.getElementById('close-match-modal')?.addEventListener('click', () => {
            quickMatchModal.close();
        });
    
        // ... Keep your existing AJAX form submission logic ...
        // ... For example: dashboardCard.addEventListener('submit', e => { ... });
    });
    
            // Expose to global scope for use in other scripts
            window.quickMatchModal = quickMatchModal;
    
            // Example usage: Add this to your quick match button
            // document.getElementById('quick-match-button').addEventListener('click', () => {
            //     quickMatchModal.open();
            // });

        document.addEventListener('DOMContentLoaded', () => {
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
    
            if (form.matches('form[action="study-groups.php"]') && form.id !== 'search-form' && !form.classList.contains('no-ajax')) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const action = formData.get('action');
    
                    let promise = null;
    
                    if (action === 'send_study_request' || action === 'cancel_study_request') {
                        let selector = form.closest('.search-results') ? '.search-results' : '#recommendations-section';
                        promise = fetch('study-groups.php', { method: 'POST', body: formData })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSearchSection = doc.querySelector(selector);
                                if (newSearchSection) {
                                    document.querySelector(selector).innerHTML = newSearchSection.innerHTML;
                                }
                                const newSentRequestsSection = doc.querySelector('#sent-requests-section');
                                if (newSentRequestsSection) {
                                    document.getElementById('sent-requests-section').innerHTML = newSentRequestsSection.innerHTML;
                                }
                            });
                    } else if (action === 'accept_request' || action === 'decline_request') {
                        promise = fetch('study-groups.php', { method: 'POST', body: formData })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newSection = doc.querySelector('#requests-section');
                                if (newSection) {
                                    document.querySelector('#requests-section').innerHTML = newSection.innerHTML;
                                }
                            });
                    } else if (formData.has('quick_match') || formData.has('cancel_match')) {
                        promise = fetch('study-groups.php', { method: 'POST', body: formData })
                            .then(response => response.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newCard = doc.querySelector('.dashboard-card');
                                if (newCard) {
                                    dashboardCard.innerHTML = newCard.innerHTML;
                                }
                            });
                    }
                    
                    if (promise) {
                        preserveScroll(promise);
                    }
                }
            });
    
                    // Use event delegation for the refresh button
    
                    dashboardCard.addEventListener('click', e => {
    
                        if (e.target && e.target.id === 'refresh-suggestions-button') {
    
                            const recommendationsSection = document.getElementById('recommendations-section');
    
                            if (recommendationsSection) {
    
                                // Show a loading indicator (optional)
    
                                recommendationsSection.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading recommendations...</div>';
    
            
    
                                // Fetch new content for the recommendations section
    
                                fetch('study-groups.php?partial=recommendations&refresh=1&_=' + new Date().getTime()) // Add unique timestamp
    
                                    .then(response => response.text())
    
                                    .then(html => {
    
                                        recommendationsSection.innerHTML = html;
    
                                    })
    
                                    .catch(error => {
    
                                        console.error('Error refreshing recommendations:', error);
    
                                        recommendationsSection.innerHTML = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Failed to load recommendations.</div>';
    
                                    });
    
                            }
    
                        }
    
                    });
    
            
    
                    // Live updates for invitations
    
                    const checkForUpdates = async () => {
                        try {
                            const response = await fetch(`ajax/check_updates.php?_=${new Date().getTime()}`);
                            const data = await response.json();

                            // Update received requests
                            const requestsSection = document.getElementById('requests-section');
                            if (requestsSection) {
                                let requestsHtml = '<h2 class="section-title"><i class="fas fa-inbox"></i> Received Study Requests</h2>';
                                if (data.received_requests && data.received_requests.length > 0) {
                                    requestsHtml += '<div class="requests-list">';
                                    data.received_requests.forEach(request => {
                                        const sender_initials = (request.first_name[0] + request.last_name[0]).toUpperCase();
                                        requestsHtml += `
                                            <div class="request-item">
                                                <div class="user-info">
                                                    <div class="user-avatar">${sender_initials}</div>
                                                    <div class="user-details">
                                                        <h4>${request.first_name} ${request.last_name}</h4>
                                                        <p>${request.email}</p>
                                                    </div>
                                                </div>
                                                <div class="request-actions">
                                                    <form action="study-groups.php" method="POST" style="display:inline;"><input type="hidden" name="action" value="accept_request"><input type="hidden" name="request_id" value="${request.request_id}"><button type="submit" class="cta-button primary small">Accept</button></form>
                                                    <form action="study-groups.php" method="POST" style="display:inline;"><input type="hidden" name="action" value="decline_request"><input type="hidden" name="request_id" value="${request.request_id}"><button type="submit" class="cta-button danger small">Decline</button></form>
                                                </div>
                                            </div>
                                        `;
                                    });
                                    requestsHtml += '</div>';
                                } else {
                                    requestsHtml += '<div class="alert info"><i class="fas fa-inbox"></i> No pending study requests.</div>';
                                }
                                requestsSection.innerHTML = requestsHtml;
                            }
                        } catch (error) {
                            console.error('Error checking for updates:', error);
                        }
                    };
    
            
    
                    setInterval(checkForUpdates, 1000); // Poll every 1 second
    
                });
            }
        });

        const searchInput = document.querySelector('input[name="search_user"]');
        const suggestionsContainer = document.getElementById('search-suggestions');
        const searchForm = document.getElementById('search-form');

        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.trim();

            if (searchTerm.length < 2) {
                suggestionsContainer.style.display = 'none';
                return;
            }

            // For debugging, make the container visible immediately
            suggestionsContainer.style.display = 'block';
            suggestionsContainer.innerHTML = '<div class="suggestion-item">Loading...</div>';

            fetch(`ajax/search_suggestions.php?q=${encodeURIComponent(searchTerm)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    suggestionsContainer.innerHTML = ''; // Clear "Loading..."
                    if (data.suggestions && data.suggestions.length > 0) {
                        data.suggestions.forEach(user => {
                            const suggestionItem = document.createElement('div');
                            suggestionItem.classList.add('suggestion-item');
                            suggestionItem.textContent = `${user.first_name} ${user.last_name} (${user.email})`;
                            suggestionItem.addEventListener('click', () => {
                                window.location.href = `user_profile.php?id=${user.id}`;
                            });
                            suggestionsContainer.appendChild(suggestionItem);
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

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchForm.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });