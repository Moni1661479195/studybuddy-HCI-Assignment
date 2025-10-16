// assets/js/study-timer.js
// Study Timer with Pomodoro Technique

class StudyTimer {
    constructor() {
        // Timer state
        this.duration = 25 * 60; // 25 minutes in seconds
        this.breakDuration = 5 * 60; // 5 minutes in seconds
        this.timeRemaining = this.duration;
        this.isRunning = false;
        this.isPaused = false;
        this.isBreak = false;
        this.intervalId = null;
        this.startTime = null;
        this.sessionId = null;
        
        // Statistics
        this.stats = {
            today: 0,
            week: 0,
            sessions: 0,
            pomodoros: 0
        };
        
        // DOM elements
        this.timeDisplay = document.getElementById('timer-time');
        this.progressBar = document.getElementById('timer-progress-bar');
        this.modeDisplay = document.getElementById('timer-mode');
        this.startButton = document.getElementById('timer-start');
        this.pauseButton = document.getElementById('timer-pause');
        this.resetButton = document.getElementById('timer-reset');
        this.focusDurationInput = document.getElementById('focus-duration');
        this.breakDurationInput = document.getElementById('break-duration');
        
        // Break modal elements
        this.breakModal = document.getElementById('break-modal');
        this.startBreakButton = document.getElementById('start-break');
        this.skipBreakButton = document.getElementById('skip-break');
        
        this.init();
    }
    
    // Initialize timer
    init() {
        this.loadSettings();
        this.loadStats();
        this.attachEventListeners();
        this.updateDisplay();
        this.requestNotificationPermission();
    }
    
    // Load settings from localStorage
    loadSettings() {
        const savedFocusDuration = localStorage.getItem('focusDuration');
        const savedBreakDuration = localStorage.getItem('breakDuration');
        
        if (savedFocusDuration) {
            this.duration = parseInt(savedFocusDuration) * 60;
            this.focusDurationInput.value = savedFocusDuration;
        }
        
        if (savedBreakDuration) {
            this.breakDuration = parseInt(savedBreakDuration) * 60;
            this.breakDurationInput.value = savedBreakDuration;
        }
        
        this.timeRemaining = this.duration;
    }
    
    // Save settings to localStorage
    saveSettings() {
        localStorage.setItem('focusDuration', this.focusDurationInput.value);
        localStorage.setItem('breakDuration', this.breakDurationInput.value);
        
        this.duration = parseInt(this.focusDurationInput.value) * 60;
        this.breakDuration = parseInt(this.breakDurationInput.value) * 60;
        
        if (!this.isRunning) {
            this.timeRemaining = this.isBreak ? this.breakDuration : this.duration;
            this.updateDisplay();
        }
    }
    
    // Attach event listeners
    attachEventListeners() {
        this.startButton.addEventListener('click', () => this.start());
        this.pauseButton.addEventListener('click', () => this.pause());
        this.resetButton.addEventListener('click', () => this.reset());
        
        this.focusDurationInput.addEventListener('change', () => this.saveSettings());
        this.breakDurationInput.addEventListener('change', () => this.saveSettings());
        
        this.startBreakButton.addEventListener('click', () => this.startBreakSession());
        this.skipBreakButton.addEventListener('click', () => this.skipBreak());
    }
    
    // Start timer
    async start() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        this.isPaused = false;
        this.startTime = Date.now() - ((this.isBreak ? this.breakDuration : this.duration) - this.timeRemaining) * 1000;
        
        // Create session on server
        if (!this.sessionId && !this.isBreak) {
            await this.createSession();
        }
        
        // Update UI
        this.startButton.disabled = true;
        this.pauseButton.disabled = false;
        
        // Start countdown
        this.intervalId = setInterval(() => this.tick(), 1000);
    }
    
    // Pause timer
    pause() {
        if (!this.isRunning) return;
        
        this.isRunning = false;
        this.isPaused = true;
        clearInterval(this.intervalId);
        
        // Update UI
        this.startButton.disabled = false;
        this.pauseButton.disabled = true;
        
        // Save progress to server
        this.saveProgress();
    }
    
    // Reset timer
    reset() {
        this.isRunning = false;
        this.isPaused = false;
        clearInterval(this.intervalId);
        
        // Reset time
        this.timeRemaining = this.isBreak ? this.breakDuration : this.duration;
        
        // Update UI
        this.startButton.disabled = false;
        this.pauseButton.disabled = true;
        this.updateDisplay();
        
        // End session if exists
        if (this.sessionId && !this.isBreak) {
            this.endSession();
        }
    }
    
    // Timer tick
    tick() {
        this.timeRemaining--;
        
        if (this.timeRemaining <= 0) {
            this.complete();
        } else {
            this.updateDisplay();
        }
    }
    
    // Timer completed
    async complete() {
        clearInterval(this.intervalId);
        this.isRunning = false;
        
        if (this.isBreak) {
            // Break completed
            this.notifyUser('Break is over!', 'Time to get back to studying.');
            this.isBreak = false;
            this.timeRemaining = this.duration;
            this.updateDisplay();
            this.updateModeDisplay();
            this.startButton.disabled = false;
            this.pauseButton.disabled = true;
        } else {
            // Focus session completed
            await this.completePomodoroSession();
            this.notifyUser('Focus session complete!', 'Great job! Time for a break.');
            this.stats.pomodoros++;
            this.updateStatsDisplay();
            this.showBreakModal();
        }
    }
    
    // Update display
    updateDisplay() {
        // Update time display
        const minutes = Math.floor(this.timeRemaining / 60);
        const seconds = this.timeRemaining % 60;
        this.timeDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Update progress bar
        const totalDuration = this.isBreak ? this.breakDuration : this.duration;
        const progress = ((totalDuration - this.timeRemaining) / totalDuration) * 100;
        this.progressBar.style.width = `${progress}%`;
    }
    
    // Update mode display
    updateModeDisplay() {
        if (this.isBreak) {
            this.modeDisplay.textContent = `Break Time (${this.breakDuration / 60} min)`;
        } else {
            this.modeDisplay.textContent = `Pomodoro Mode (${this.duration / 60} min focus)`;
        }
    }
    
    // Show break modal
    showBreakModal() {
        this.breakModal.classList.add('active');
    }
    
    // Hide break modal
    hideBreakModal() {
        this.breakModal.classList.remove('active');
    }
    
    // Start break session
    startBreakSession() {
        this.hideBreakModal();
        this.isBreak = true;
        this.timeRemaining = this.breakDuration;
        this.sessionId = null;
        this.updateModeDisplay();
        this.updateDisplay();
        this.start();
    }
    
    // Skip break
    skipBreak() {
        this.hideBreakModal();
        this.timeRemaining = this.duration;
        this.updateDisplay();
        this.startButton.disabled = false;
        this.pauseButton.disabled = true;
    }
    
    // Request notification permission
    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    // Notify user
    notifyUser(title, body) {
        // Browser notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: body,
                icon: '/assets/images/logo.png',
                badge: '/assets/images/badge.png'
            });
        }
        
        // Play sound notification
        try {
            const audio = new Audio('/assets/sounds/notification.mp3');
            audio.play().catch(e => console.log('Audio play failed:', e));
        } catch (e) {
            console.log('Audio notification failed:', e);
        }
    }
    
    // Create session on server
    async createSession() {
        try {
            const response = await fetch('api/study_timer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'start_session',
                    duration: this.duration,
                    activity_type: 'focus'
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.sessionId = result.session_id;
            }
        } catch (error) {
            console.error('Failed to create session:', error);
        }
    }
    
    // Save progress to server
    async saveProgress() {
        if (!this.sessionId || this.isBreak) return;
        
        const elapsed = this.duration - this.timeRemaining;
        
        try {
            await fetch('api/study_timer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'save_progress',
                    session_id: this.sessionId,
                    elapsed_time: elapsed
                })
            });
        } catch (error) {
            console.error('Failed to save progress:', error);
        }
    }
    
    // Complete pomodoro session
    async completePomodoroSession() {
        if (!this.sessionId) return;
        
        try {
            const response = await fetch('api/study_timer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'complete_session',
                    session_id: this.sessionId,
                    duration: this.duration,
                    completed: true
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.stats.today += Math.floor(this.duration / 60);
                this.stats.sessions++;
                this.updateStatsDisplay();
            }
            
            this.sessionId = null;
        } catch (error) {
            console.error('Failed to complete session:', error);
        }
    }
    
    // End session
    async endSession() {
        if (!this.sessionId) return;
        
        try {
            await fetch('api/study_timer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'end_session',
                    session_id: this.sessionId
                })
            });
            
            this.sessionId = null;
        } catch (error) {
            console.error('Failed to end session:', error);
        }
    }
    
    // Load statistics from server
    async loadStats() {
        try {
            const response = await fetch('api/study_timer.php?action=get_stats');
            const result = await response.json();
            
            if (result.success) {
                this.stats = {
                    today: result.data.today_minutes || 0,
                    week: result.data.week_minutes || 0,
                    sessions: result.data.sessions_count || 0,
                    pomodoros: result.data.pomodoros_count || 0
                };
                this.updateStatsDisplay();
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }
    
    // Update statistics display
    updateStatsDisplay() {
        document.getElementById('stat-today').textContent = `${this.stats.today}m`;
        document.getElementById('stat-week').textContent = `${this.stats.week}m`;
        document.getElementById('stat-sessions').textContent = this.stats.sessions;
        document.getElementById('stat-pomodoros').textContent = this.stats.pomodoros;
    }
    
    // Format time (seconds to mm:ss)
    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}

// Initialize timer when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.studyTimer = new StudyTimer();
});