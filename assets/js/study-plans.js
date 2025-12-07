
// ============================================ 
// GLOBAL VARIABLES 
// ============================================ 
let taskCounter = 0; // For create modal
let editTaskCounter = 0; // For edit modal
let currentPlanData = null; // Store current viewing plan

// ============================================ 
// STEP 1: LOAD AND DISPLAY PLANS 
// ============================================ 

/** 
 * Load study plans on page load
 */
document.addEventListener('DOMContentLoaded', function() { 
    loadStudyPlans();

    // Check for shared plan URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const sharedPlanId = urlParams.get('view_plan_id');
    if (sharedPlanId) {
        viewPlan(sharedPlanId);
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Search functionality
    const searchInput = document.getElementById('planSearchInput');
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadStudyPlans(e.target.value);
            }, 300); // Debounce 300ms
        });
    }
});

/** 
 * Load all study plans from API
 */
function loadStudyPlans(searchQuery = '') { 
    let url = 'api/study-plans.php?action=get_all';
    if (searchQuery) {
        url += '&search=' + encodeURIComponent(searchQuery);
    }

    fetch(url) 
        .then(response => response.json()) 
        .then(data => { 
            const loading = document.getElementById('loading');
            const container = document.getElementById('plans-container');
            const emptyState = document.getElementById('empty-state');
            
            loading.style.display = 'none';
            
            if (data.success && data.plans.length > 0) { 
                container.style.display = 'grid';
                emptyState.style.display = 'none';
                renderPlans(data.plans);
            } else { 
                container.style.display = 'none';
                emptyState.style.display = 'block';
                // Update empty state text if searching
                if (searchQuery) {
                    emptyState.innerHTML = '<i class="fas fa-search"></i><p>No plans found matching "' + escapeHtml(searchQuery) + '"</p>';
                } else {
                     emptyState.innerHTML = '<i class="fas fa-clipboard-list"></i><p>You haven\'t created any study plans yet.</p><p style="font-size: 0.95rem; color: #9ca3af;">Start planning your success today!</p>';
                }
            }
        })
        .catch(error => { 
            console.error('Error loading plans:', error);
            document.getElementById('loading').innerHTML =
                '<i class="fas fa-exclamation-circle"></i><p>Error loading study plans. Please refresh the page.</p>';
        });
}

/** 
 * Render study plans in the container
 */
function renderPlans(plans) { 
    const container = document.getElementById('plans-container');
    container.innerHTML = '';
    
    plans.forEach(plan => { 
        const card = createPlanCard(plan);
        container.appendChild(card);
    });
}

/** 
 * Create a study plan card element
 */
function createPlanCard(plan) { 
    const card = document.createElement('div');
    card.className = 'study-plan-card';
    
    // Determine status class and text
    const statusClass = `status-${plan.status}`;
    let statusText = plan.status.charAt(0).toUpperCase() + plan.status.slice(1);
    
    // Format due date
    const dueDate = new Date(plan.due_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    dueDate.setHours(0, 0, 0, 0);
    
    const isOverdue = dueDate < today && plan.status !== 'completed';
    const dueDateFormatted = formatDate(plan.due_date);
    
    // Calculate days remaining
    const daysRemaining = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
    let dueDateText = dueDateFormatted;
    if (isOverdue) { 
        dueDateText += ' <span style="color: #ef4444; font-weight: 600;">(Overdue)</span>';
    } else if (daysRemaining === 0) { 
        dueDateText += ' <span style="color: #f59e0b; font-weight: 600;">(Due Today)</span>';
    } else if (daysRemaining > 0 && daysRemaining <= 3) { 
        dueDateText += ` <span style="color: #f59e0b; font-weight: 600;">(${daysRemaining} days left)</span>`;
    }
    
    card.innerHTML = `
        <div class="plan-header">
            <div style="flex: 1;">
                <h3 class="plan-title">${escapeHtml(plan.name)}</h3>
            </div>
            <span class="plan-status ${statusClass}">${statusText}</span>
        </div>
        
        ${plan.description ? `<p class="plan-description">${escapeHtml(plan.description)}</p>` : ''}
        
        <div class="plan-meta">
            <div class="meta-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Due: ${dueDateText}</span>
            </div>
            <div class="meta-item">
                <i class="fas fa-tasks"></i>
                <span>${plan.completed_tasks} / ${plan.total_tasks} tasks completed</span>
            </div>
        </div>
        
        <div class="progress-section">
            <div class="progress-label">
                <span>Progress</span>
                <span style="font-weight: 600;">${plan.progress}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${plan.progress}%"></div>
            </div>
        </div>
        
        <div class="plan-actions">
            <button class="btn btn-primary" onclick="viewPlan(${plan.id})">
                <i class="fas fa-eye"></i> View Plan
            </button>
            <button class="btn btn-danger" onclick="confirmDeletePlan(${plan.id}, '${escapeHtml(plan.name).replace(/'/g, "\'")}')">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    return card;
}

/** 
 * Format date to readable format
 */
function formatDate(dateString) { 
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

/** 
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) { 
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/** 
 * Confirm plan deletion with double confirmation
 */
function confirmDeletePlan(planId, planName) { 
    if (confirm(`Are you sure you want to delete "${planName}"?\n\nThis action cannot be undone.`)) { 
        if (confirm('Final confirmation: Delete this plan and all its tasks permanently?')) { 
            deletePlan(planId);
        }
    }
}

/** 
 * Delete a study plan
 */
function deletePlan(planId) { 
    fetch('api/study-plans.php?action=delete', { 
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ plan_id: planId })
    })
    .then(response => response.json())
    .then(data => { 
        if (data.success) { 
            showNotification('Plan deleted successfully', 'success');
            loadStudyPlans();
        } else { 
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => { 
        console.error('Error deleting plan:', error);
        showNotification('Error deleting plan. Please try again.', 'error');
    });
}

/** 
 * Show notification message
 */
function showNotification(message, type = 'info') { 
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        color: white;
        font-weight: 600;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    
    if (type === 'success') { 
        notification.style.background = 'linear-gradient(45deg, #10b981, #059669)';
    } else if (type === 'error') { 
        notification.style.background = 'linear-gradient(45deg, #ef4444, #dc2626)';
    } else { 
        notification.style.background = 'linear-gradient(45deg, #667eea, #764ba2)';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => { 
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ============================================ 
// STEP 2: CREATE PLAN MODAL 
// ============================================ 

/** 
 * Open create plan modal
 */
function createNewPlan(resetForm = true) { 
    const modal = document.getElementById('createPlanModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    if (resetForm) {
        document.getElementById('createPlanForm').reset();
        document.getElementById('tasksContainer').innerHTML = '';
        document.getElementById('validationMessage').textContent = '';
        taskCounter = 0;
        
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('planDueDate').min = today;
        
        addTask();
    }
}

/** 
 * Close create plan modal
 */
function closeCreateModal() { 
    const modal = document.getElementById('createPlanModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

/** 
 * Add a new task input row
 */
function addTask() { 
    taskCounter++;
    const container = document.getElementById('tasksContainer');
    const taskCount = container.children.length + 1;
    
    const taskItem = document.createElement('div');
    taskItem.className = 'task-item';
    taskItem.id = `task-${taskCounter}`;
    
    taskItem.innerHTML = `
        <div class="task-number">${taskCount}</div>
        
        <div class="task-input-group">
            <label>Task Name <span style="color: #ef4444;">*</span></label>
            <input 
                type="text" 
                name="taskName[]" 
                placeholder="e.g., Review Chapter 1-5"
                required
            >
        </div>
        
        <div class="task-input-group">
            <label>Description (Optional)</label>
            <textarea 
                name="taskDescription[]"
                placeholder="Describe what needs to be done..."
            ></textarea>
        </div>
        
        <div class="task-input-group">
            <label>Due Date (Optional)</label>
            <input 
                type="date" 
                name="taskDueDate[]"
                min="${new Date().toISOString().split('T')[0]}"
            >
        </div>
        
        <button 
            type="button" 
            class="task-remove-btn" 
            onclick="removeTask('task-${taskCounter}')"
            title="Remove task"
        >
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(taskItem);
    updateTaskNumbers();
}

/** 
 * Remove a task
 */
function removeTask(taskId) { 
    const container = document.getElementById('tasksContainer');
    
    if (container.children.length <= 1) { 
        showNotification('At least one task is required', 'error');
        return;
    }
    
    const taskItem = document.getElementById(taskId);
    taskItem.remove();
    updateTaskNumbers();
}

/** 
 * Update task numbers after adding/removing
 */
function updateTaskNumbers() { 
    const container = document.getElementById('tasksContainer');
    const tasks = container.children;
    
    for (let i = 0; i < tasks.length; i++) { 
        const numberElement = tasks[i].querySelector('.task-number');
        if (numberElement) { 
            numberElement.textContent = i + 1;
        }
    }
}

/** 
 * Validate form before submission
 */
function validateForm() { 
    const validationMsg = document.getElementById('validationMessage');
    validationMsg.textContent = '';
    
    const planName = document.getElementById('planName').value.trim();
    if (!planName) { 
        validationMsg.textContent = 'Plan name is required';
        return false;
    }
    
    const dueDate = document.getElementById('planDueDate').value;
    if (!dueDate) { 
        validationMsg.textContent = 'Due date is required';
        return false;
    }
    
    const selectedDate = new Date(dueDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (selectedDate < today) { 
        validationMsg.textContent = 'Due date cannot be in the past';
        return false;
    }
    
    const taskNames = document.querySelectorAll('input[name="taskName[]"]');
    if (taskNames.length === 0) { 
        validationMsg.textContent = 'At least one task is required';
        return false;
    }
    
    let hasEmptyTask = false;
    taskNames.forEach(input => { 
        if (!input.value.trim()) { 
            hasEmptyTask = true;
        }
    });
    
    if (hasEmptyTask) { 
        validationMsg.textContent = 'All task names must be filled';
        return false;
    }
    
    return true;
}

/** 
 * Handle form submission
 */
function handleCreatePlan(event) { 
    event.preventDefault();
    
    if (!validateForm()) { 
        return;
    }
    
    const saveBtn = document.getElementById('saveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    const planData = { 
        name: document.getElementById('planName').value.trim(),
        description: document.getElementById('planDescription').value.trim(),
        due_date: document.getElementById('planDueDate').value,
        tasks: []
    };
    
    const taskNames = document.querySelectorAll('input[name="taskName[]"]');
    const taskDescriptions = document.querySelectorAll('textarea[name="taskDescription[]"]');
    const taskDueDates = document.querySelectorAll('input[name="taskDueDate[]"]');
    
    for (let i = 0; i < taskNames.length; i++) { 
        if (taskNames[i].value.trim()) { 
            planData.tasks.push({ 
                task_name: taskNames[i].value.trim(),
                task_description: taskDescriptions[i].value.trim(),
                due_date: taskDueDates[i].value || null
            });
        }
    }
    
    fetch('api/study-plans.php?action=create', { 
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(planData)
    })
    .then(response => response.json())
    .then(data => { 
        if (data.success) { 
            showNotification('Study plan created successfully!', 'success');
            closeCreateModal();
            loadStudyPlans();
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Create Plan';
        } else { 
            showNotification('Error: ' + data.message, 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Create Plan';
        }
    })
    .catch(error => { 
        console.error('Error creating plan:', error);
        showNotification('Error creating plan. Please try again.', 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Create Plan';
    });
}

// ============================================ 
// STEP 3: VIEW/EDIT PLAN MODAL 
// ============================================ 

/** 
 * View plan details
 */
function viewPlan(planId) { 
    fetch(`api/study-plans.php?action=get_plan&plan_id=${planId}`)
        .then(response => response.json())
        .then(data => { 
            if (data.success) { 
                currentPlanData = data.plan;
                displayPlanDetails(data.plan);
                openViewModal();
            } else { 
                showNotification('Error loading plan: ' + data.message, 'error');
            }
        })
        .catch(error => { 
            console.error('Error loading plan:', error);
            showNotification('Error loading plan details', 'error');
        });
}

/** 
 * Display plan details in view mode
 */
function displayPlanDetails(plan) { 
    document.getElementById('editPlanId').value = plan.id;
    document.getElementById('viewPlanName').textContent = plan.name;
    
    const statusBadge = document.getElementById('viewStatusBadge');
    statusBadge.className = `plan-status status-${plan.status}`;
    statusBadge.textContent = plan.status.charAt(0).toUpperCase() + plan.status.slice(1);
    
    document.getElementById('viewPlanNameDisplay').textContent = plan.name;
    
    const dueDate = new Date(plan.due_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    dueDate.setHours(0, 0, 0, 0);
    
    let dueDateHTML = formatDate(plan.due_date);
    if (dueDate < today && plan.status !== 'completed') { 
        dueDateHTML += ' <span style="color: #ef4444; font-weight: 600; margin-left: 0.5rem;">(Overdue)</span>';
    }
    document.getElementById('viewDueDateDisplay').innerHTML = dueDateHTML;
    
    const descDisplay = document.getElementById('viewDescriptionDisplay');
    if (plan.description && plan.description.trim()) { 
        descDisplay.textContent = plan.description;
        descDisplay.classList.remove('empty');
    } else { 
        descDisplay.textContent = 'No description provided';
        descDisplay.classList.add('empty');
    }
    
    document.getElementById('viewProgressFill').style.width = plan.progress + '%';
    document.getElementById('viewProgressText').textContent = plan.progress + '%';
    document.getElementById('viewTasksCompletedDisplay').textContent = 
        `${plan.completed_tasks} / ${plan.total_tasks} tasks`;
    
    // Handle Owner vs Viewer permissions
    const actionsContainer = document.getElementById('viewModeActions');
    // Buttons: [0]=Share, [1]=Close, [2]=Edit, [3]=Delete (based on previous reordering)
    // Actually, let's use querySelector to be safe
    const btnSharePlan = document.getElementById('btnSharePlan');
    const editBtn = actionsContainer.querySelector('button[onclick="switchToEditMode()"]');
    const deleteBtn = actionsContainer.querySelector('button[onclick="confirmDeletePlanFromModal()"]');
    const closeBtn = actionsContainer.querySelector('button[onclick="closeViewModal()"]');

    // If plan.is_owner is explicitly false (it might be undefined for old plans cached, but API always sends it now)
    const isOwner = plan.is_owner !== false; 

    if (!isOwner) {
        if(btnSharePlan) btnSharePlan.style.display = 'none';
        if(editBtn) editBtn.style.display = 'none';
        if(deleteBtn) deleteBtn.style.display = 'none';
        // Change modal title for viewer
        document.getElementById('viewModalTitle').innerHTML = '<i class="fas fa-eye"></i> Viewing Shared Plan';
        
        // Make tasks read-only (disable checkboxes)
        // We do this after generating the list
        setTimeout(() => {
             const checkboxes = document.querySelectorAll('#viewTasksList .task-checkbox');
             checkboxes.forEach(cb => cb.disabled = true);
        }, 0);

    } else {
        if(btnSharePlan) btnSharePlan.style.display = 'inline-flex';
        if(editBtn) editBtn.style.display = 'inline-block';
        if(deleteBtn) deleteBtn.style.display = 'inline-block';
        document.getElementById('viewModalTitle').innerHTML = '<i class="fas fa-clipboard-list"></i> <span id="viewPlanName">Plan Details</span>';
        // Ensure name is set back
        document.getElementById('viewPlanName').textContent = plan.name;
    }

    displayTasksList(plan.tasks);
}

/** 
 * Display tasks in view mode
 */
function displayTasksList(tasks) { 
    const container = document.getElementById('viewTasksList');
    container.innerHTML = '';
    
    if (!tasks || tasks.length === 0) { 
        container.innerHTML = '<p style="color: #9ca3af; text-align: center; padding: 2rem;">No tasks added yet</p>';
        return;
    }
    
    tasks.forEach((task, index) => { 
        const taskItem = document.createElement('div');
        taskItem.className = 'task-view-item' + (task.is_completed ? ' completed' : '');
        
        let taskDueDate = '';
        if (task.due_date) { 
            const dueDate = new Date(task.due_date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            dueDate.setHours(0, 0, 0, 0);
            
            taskDueDate = `<i class="fas fa-calendar"></i> ${formatDate(task.due_date)}`;
            if (dueDate < today && !task.is_completed) { 
                taskDueDate += ' <span style="color: #ef4444;">(Overdue)</span>';
            }
        } else { 
            taskDueDate = '<i class="fas fa-calendar"></i> No due date';
        }
        
        taskItem.innerHTML = `
            <input 
                type="checkbox" 
                class="task-checkbox" 
                ${task.is_completed ? 'checked' : ''}
                onchange="toggleTaskStatus(${task.id}, this.checked)"
            >
            
            <div class="task-view-content">
                <div class="task-view-name">${escapeHtml(task.task_name)}</div>
            </div>
            
            <div class="task-view-content">
                <div class="task-view-description">
                    ${task.task_description ? escapeHtml(task.task_description) : '<em style="color: #9ca3af;">No description</em>'}
                </div>
            </div>
            
            <div class="task-view-date">${taskDueDate}</div>
            
            <span class="task-status-badge ${task.is_completed ? 'completed' : 'pending'}">
                ${task.is_completed ? 'Done' : 'Pending'}
            </span>
        `;
        
        container.appendChild(taskItem);
    });
}

/** 
 * Toggle task completion status
 */
function toggleTaskStatus(taskId, isCompleted) { 
    fetch('api/study-plans.php?action=toggle_task', { 
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
            task_id: taskId,
            is_completed: isCompleted ? 1 : 0
        })
    })
    .then(response => response.json())
    .then(data => { 
        if (data.success) { 
            showNotification('Task status updated', 'success');
            viewPlan(currentPlanData.id);
            loadStudyPlans();
        } else { 
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => { 
        console.error('Error updating task:', error);
        showNotification('Error updating task status', 'error');
    });
}

/** 
 * Open view modal
 */
function openViewModal() { 
    const modal = document.getElementById('viewPlanModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    document.getElementById('viewMode').style.display = 'block';
    document.getElementById('editMode').style.display = 'none';
    document.getElementById('viewModeActions').style.display = 'flex';
    document.getElementById('editModeActions').style.display = 'none';
}

/** 
 * Close view modal
 */
function closeViewModal() { 
    const modal = document.getElementById('viewPlanModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    currentPlanData = null;
}

/** 
 * Switch to edit mode
 */
function switchToEditMode() { 
    if (!currentPlanData) return;
    
    document.getElementById('editPlanName').value = currentPlanData.name;
    document.getElementById('editPlanDueDate').value = currentPlanData.due_date;
    document.getElementById('editPlanDescription').value = currentPlanData.description || '';
    
    const container = document.getElementById('editTasksContainer');
    container.innerHTML = '';
    editTaskCounter = 0;
    
    currentPlanData.tasks.forEach(task => { 
        addEditTask(task);
    });
    
    if (currentPlanData.tasks.length === 0) { 
        addEditTask();
    }
    
    const btnSharePlan = document.getElementById('btnSharePlan');
    if (btnSharePlan) btnSharePlan.style.display = 'none';

    document.getElementById('viewMode').style.display = 'none';
    document.getElementById('editMode').style.display = 'block';
    document.getElementById('viewModeActions').style.display = 'none';
    document.getElementById('editModeActions').style.display = 'flex';
    document.getElementById('editValidationMessage').textContent = '';
}

/** 
 * Add task in edit mode
 */
function addEditTask(taskData = null) { 
    editTaskCounter++;
    const container = document.getElementById('editTasksContainer');
    const taskCount = container.children.length + 1;
    
    const taskItem = document.createElement('div');
    taskItem.className = 'edit-task-item';
    taskItem.id = `edit-task-${editTaskCounter}`;
    
    const isCompleted = taskData?.is_completed || 0;
    
    taskItem.innerHTML = `
        <div class="task-number">${taskCount}</div>
        
        <div class="task-input-group">
            <label>Task Name <span style="color: #ef4444;">*</span></label>
            <input 
                type="text" 
                name="editTaskName[]" 
                placeholder="e.g., Review Chapter 1-5"
                value="${taskData ? escapeHtml(taskData.task_name) : ''}"
                required
            >
        </div>
        
        <div class="task-input-group">
            <label>Description (Optional)</label>
            <textarea 
                name="editTaskDescription[]"
                placeholder="Describe what needs to be done..."
            >${taskData ? escapeHtml(taskData.task_description || '') : ''}</textarea>
        </div>
        
        <div class="task-input-group">
            <label>Due Date (Optional)</label>
            <input 
                type="date" 
                name="editTaskDueDate[]"
                value="${taskData?.due_date || ''}"
                min="${new Date().toISOString().split('T')[0]}"
            >
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1.5rem;">
            <div class="edit-task-checkbox-wrapper">
                <input 
                    type="checkbox" 
                    name="editTaskCompleted[]" 
                    class="edit-task-checkbox"
                    ${isCompleted ? 'checked' : ''}
                    value="1"
                >
                <span class="edit-task-checkbox-label">Done</span>
            </div>
            <button 
                type="button" 
                class="task-remove-btn" 
                onclick="removeEditTask('edit-task-${editTaskCounter}')"
                title="Remove task"
            >
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(taskItem);
    updateEditTaskNumbers();
}

/** 
 * Remove task in edit mode
 */
function removeEditTask(taskId) { 
    const container = document.getElementById('editTasksContainer');
    
    if (container.children.length <= 1) { 
        showNotification('At least one task is required', 'error');
        return;
    }
    
    const taskItem = document.getElementById(taskId);
    taskItem.remove();
    updateEditTaskNumbers();
}

/** 
 * Update task numbers in edit mode
 */
function updateEditTaskNumbers() { 
    const container = document.getElementById('editTasksContainer');
    const tasks = container.children;
    
    for (let i = 0; i < tasks.length; i++) { 
        const numberElement = tasks[i].querySelector('.task-number');
        if (numberElement) { 
            numberElement.textContent = i + 1;
        }
    }
}

/** 
 * Cancel edit and return to view mode
 */
function cancelEdit() { 
    if (confirm('Discard all changes?')) { 
        displayPlanDetails(currentPlanData);
        document.getElementById('viewMode').style.display = 'block';
        document.getElementById('editMode').style.display = 'none';
        document.getElementById('viewModeActions').style.display = 'flex';
        document.getElementById('editModeActions').style.display = 'none';
    }
}

/** 
 * Handle plan update submission
 */
function handleUpdatePlan(event) { 
    event.preventDefault();
    
    if (!validateEditForm()) { 
        return;
    }
    
    const updateBtn = document.getElementById('updateBtn');
    updateBtn.disabled = true;
    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    const planData = { 
        plan_id: document.getElementById('editPlanId').value,
        name: document.getElementById('editPlanName').value.trim(),
        description: document.getElementById('editPlanDescription').value.trim(),
        due_date: document.getElementById('editPlanDueDate').value,
        tasks: []
    };
    
    const taskNames = document.querySelectorAll('input[name="editTaskName[]"]');
    const taskDescriptions = document.querySelectorAll('textarea[name="editTaskDescription[]"]');
    const taskDueDates = document.querySelectorAll('input[name="editTaskDueDate[]"]');
    const taskCompleted = document.querySelectorAll('input[name="editTaskCompleted[]"]');
    
    for (let i = 0; i < taskNames.length; i++) { 
        if (taskNames[i].value.trim()) { 
            planData.tasks.push({ 
                task_name: taskNames[i].value.trim(),
                task_description: taskDescriptions[i].value.trim(),
                is_completed: taskCompleted[i].checked ? 1 : 0,
                due_date: taskDueDates[i].value || null
            });
        }
    }
    
    fetch('api/study-plans.php?action=update', { 
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(planData)
    })
    .then(response => response.json())
    .then(data => { 
        if (data.success) { 
            showNotification('Plan updated successfully!', 'success');
            closeViewModal();
            loadStudyPlans();
            updateBtn.disabled = false;
            updateBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        } else { 
            showNotification('Error: ' + data.message, 'error');
            updateBtn.disabled = false;
            updateBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    })
    .catch(error => { 
        console.error('Error updating plan:', error);
        showNotification('Error updating plan. Please try again.', 'error');
        updateBtn.disabled = false;
        updateBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    });
}

/** 
 * Validate edit form
 */
function validateEditForm() { 
    const validationMsg = document.getElementById('editValidationMessage');
    validationMsg.textContent = '';
    
    const planName = document.getElementById('editPlanName').value.trim();
    if (!planName) { 
        validationMsg.textContent = 'Plan name is required';
        return false;
    }
    
    const dueDate = document.getElementById('editPlanDueDate').value;
    if (!dueDate) { 
        validationMsg.textContent = 'Due date is required';
        return false;
    }
    
    const taskNames = document.querySelectorAll('input[name="editTaskName[]"]');
    if (taskNames.length === 0) { 
        validationMsg.textContent = 'At least one task is required';
        return false;
    }
    
    let hasEmptyTask = false;
    taskNames.forEach(input => { 
        if (!input.value.trim()) { 
            hasEmptyTask = true;
        }
    });
    
    if (hasEmptyTask) { 
        validationMsg.textContent = 'All task names must be filled';
        return false;
    }
    
    return true;
}

/** 
 * Confirm delete plan from modal
 */
function confirmDeletePlanFromModal() { 
    if (!currentPlanData) return;
    
    const planName = currentPlanData.name;
    const planId = currentPlanData.id;
    
    if (confirm(`Are you sure you want to delete "${planName}"?\n\nThis action cannot be undone.`)) { 
        if (confirm('Final confirmation: Delete this plan and all its tasks permanently?')) { 
            deletePlanFromModal(planId);
        }
    }
}

/** 
 * Delete plan from modal
 */
function deletePlanFromModal(planId) { 
    fetch('api/study-plans.php?action=delete', { 
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ plan_id: planId })
    })
    .then(response => response.json())
    .then(data => { 
        if (data.success) { 
            showNotification('Plan deleted successfully', 'success');
            closeViewModal();
            loadStudyPlans();
        } else { 
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => { 
        console.error('Error deleting plan:', error);
        showNotification('Error deleting plan. Please try again.', 'error');
    });
}

// ============================================ 
// MODAL EVENT LISTENERS 
// ============================================ 

// Close modals when clicking outside
document.addEventListener('click', function(event) { 
    const createModal = document.getElementById('createPlanModal');
    const viewModal = document.getElementById('viewPlanModal');
    
    if (event.target === createModal) { 
        closeCreateModal();
    }
    if (event.target === viewModal) { 
        closeViewModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(event) { 
    if (event.key === 'Escape') { 
        const createModal = document.getElementById('createPlanModal');
        const viewModal = document.getElementById('viewPlanModal');
        
        if (createModal.classList.contains('active')) { 
            closeCreateModal();
        }
        if (viewModal.classList.contains('active')) { 
            closeViewModal();
        }
    }
});

// ============================================
// AI ASSISTANT MODAL
// ============================================

/**
 * Open AI Assistant Modal
 */
function openAiModal() {
    const modal = document.getElementById('aiAssistantModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    modal.classList.add('active'); // Use 'active' class for consistency
}

/**
 * Close AI Assistant Modal
 */
function closeAiModal() {
    const modal = document.getElementById('aiAssistantModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    modal.classList.remove('active');
}

/**
 * Handle AI-powered plan generation
 */
async function handleAiCreatePlan(event) {
    event.preventDefault();

    const subject = document.getElementById('aiSubject').value.trim();
    const weaknesses = document.getElementById('aiWeaknesses').value.trim();
    const durationValue = document.getElementById('aiDurationValue').value;
    const durationUnit = document.getElementById('aiDurationUnit').value;
    const validationMsg = document.getElementById('aiValidationMessage');

    if (!subject || !weaknesses || !durationValue) {
        validationMsg.textContent = 'Please fill out all fields.';
        return;
    }
    validationMsg.textContent = '';

    const aiSaveBtn = document.getElementById('aiSaveBtn');
    aiSaveBtn.disabled = true;
    aiSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

    try {
        const response = await fetch('api/gemini_study_plan_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subject,
                weaknesses,
                duration: `${durationValue} ${durationUnit}`,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.plan) {
            populateCreateFormWithAiData(data.plan);
            closeAiModal();
            createNewPlan(false); // Open the standard create modal WITHOUT resetting it
            showNotification('AI plan generated! Review and save.', 'success');
        } else {
            throw new Error(data.message || 'Failed to generate plan from AI.');
        }
    } catch (error) {
        console.error('AI Plan Generation Error:', error);
        showNotification(error.message, 'error');
        validationMsg.textContent = error.message;
    } finally {
        aiSaveBtn.disabled = false;
        aiSaveBtn.innerHTML = '<i class="fas fa-magic"></i> Generate Plan';
    }
}

/**
 * Populate the main create form with data from AI
 */
function populateCreateFormWithAiData(plan) {
    // Reset form and tasks
    document.getElementById('createPlanForm').reset();
    const tasksContainer = document.getElementById('tasksContainer');
    tasksContainer.innerHTML = '';
    taskCounter = 0;

    // Set plan name and description
    document.getElementById('planName').value = plan.planName || '';
    document.getElementById('planDescription').value = plan.description || '';

    // Set a the due date from the backend response
    document.getElementById('planDueDate').value = plan.due_date || '';

    // Add tasks from AI response
    if (plan.tasks && plan.tasks.length > 0) {
        plan.tasks.forEach(task => {
            addTaskWithData(task);
        });
    } else {
        // Add one empty task if AI provides none
        addTask();
    }
}

/**
 * Adds a task row to the create-plan modal with pre-filled data.
 */
function addTaskWithData(taskData) {
    taskCounter++;
    const container = document.getElementById('tasksContainer');
    const taskCount = container.children.length + 1;
    
    const taskItem = document.createElement('div');
    taskItem.className = 'task-item';
    taskItem.id = `task-${taskCounter}`;
    
    const taskName = taskData.name || '';
    const taskDescription = taskData.description || '';
    
    taskItem.innerHTML = `
        <div class="task-number">${taskCount}</div>
        <div class="task-input-group">
            <label>Task Name <span style="color: #ef4444;">*</span></label>
            <input type="text" name="taskName[]" placeholder="e.g., Review Chapter 1-5" value="${escapeHtml(taskName)}" required>
        </div>
        <div class="task-input-group">
            <label>Description (Optional)</label>
            <textarea name="taskDescription[]" placeholder="Describe what needs to be done...">${escapeHtml(taskDescription)}</textarea>
        </div>
        <div class="task-input-group">
            <label>Due Date (Optional)</label>
            <input type="date" name="taskDueDate[]" value="${taskData.due_date || ''}" min="${new Date().toISOString().split('T')[0]}">
        </div>
        <button type="button" class="task-remove-btn" onclick="removeTask('task-${taskCounter}')" title="Remove task">
            <i class="fas fa-trash"></i>
        </button>
    `;
    
    container.appendChild(taskItem);
    updateTaskNumbers();
}


// ============================================
// SHARE PLAN MODAL
// ============================================

function openShareModal() {
    const modal = document.getElementById('sharePlanModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    modal.classList.add('active');
    
    // Load recipients for default selection
    toggleShareRecipientList();
}

function closeShareModal() {
    const modal = document.getElementById('sharePlanModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    modal.classList.remove('active');
}

function toggleShareRecipientList() {
    const type = document.getElementById('shareRecipientType').value;
    const select = document.getElementById('shareRecipientId');
    
    select.innerHTML = '<option>Loading...</option>';
    select.disabled = true;

    fetch(`api/share_study_plan.php?action=get_recipients&type=${type}`)
        .then(res => res.json())
        .then(data => {
            select.innerHTML = '';
            select.disabled = false;
            
            if (data.success && data.recipients.length > 0) {
                data.recipients.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name;
                    select.appendChild(opt);
                });
            } else {
                select.innerHTML = '<option value="">No recipients found</option>';
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error loading list</option>';
        });
}

function handleSharePlan() {
    if (!currentPlanData) return;
    
    const type = document.getElementById('shareRecipientType').value;
    const recipientId = document.getElementById('shareRecipientId').value;
    const btn = document.getElementById('confirmShareBtn');

    if (!recipientId) {
        showNotification('Please select a recipient', 'error');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    fetch('api/share_study_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            plan_id: currentPlanData.id,
            recipient_type: type,
            recipient_id: recipientId
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Study plan shared successfully!', 'success');
            closeShareModal();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showNotification('Network error. Please try again.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
    });
}

// Add event listeners for Share modal
document.addEventListener('click', function(event) {
    const shareModal = document.getElementById('sharePlanModal');
    if (event.target === shareModal) {
        closeShareModal();
    }
});

// Add event listeners for the new AI modal
document.addEventListener('click', function(event) {
    const aiModal = document.getElementById('aiAssistantModal');
    if (event.target === aiModal) {
        closeAiModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && document.getElementById('aiAssistantModal').classList.contains('active')) {
        closeAiModal();
    }
});

