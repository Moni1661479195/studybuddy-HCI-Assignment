<?php
require_once 'session.php';
require_once 'lib/matching.php';

// Prevent browser caching so Back button forces a fresh request (and triggers session check)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Buddy - My Study Plans</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #333;
        }

        #navbar {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: background 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .dashboard-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 3rem;
            width: 100%;
            max-width: 1200px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
            text-align: center;
        }

        .welcome-message {
            font-size: 1.2rem;
            color: #4b5563;
            margin-bottom: 2rem;
            text-align: center;
        }

        .nav-links .cta-button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .nav-links .cta-button.primary {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            border: none;
        }

        .nav-links .cta-button.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .nav-links .active {
            background: linear-gradient(45deg, #ef4444, #dc2626) !important;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        .footer {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            color: white;
            text-align: center;
            padding: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer p {
            margin-bottom: 0.5rem;
            opacity: 0.8;
            color: white;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            margin: 0 0.75rem;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .footer-links a:hover {
            opacity: 1;
        }

        /* Study Plans Section */
        .dashboard-section {
            margin-bottom: 3rem;
        }

        .dashboard-section h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }

        .study-plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .study-plan-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
        }

        .study-plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .plan-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            word-wrap: break-word;
            flex: 1;
            padding-right: 0.5rem;
        }

        .plan-status {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-active {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .plan-description {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .plan-meta {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #4b5563;
        }

        .meta-item i {
            color: #667eea;
            width: 18px;
        }

        .progress-section {
            margin-bottom: 1rem;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #4b5563;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 1rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 1rem;
            transition: width 0.3s ease;
        }

        .plan-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 0.6rem 0.9rem;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .create-plan-section {
            text-align: center;
            margin-top: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }

        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Notification styles */
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        /* ============================================ */
        /* MODAL STYLES */
        /* ============================================ */

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        /* Modal Container */
        .modal-container {
            background: white;
            border-radius: 1.5rem;
            width: 100%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        /* Modal Header */
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 1.5rem 1.5rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        /* Modal Body */
        .modal-body {
            padding: 2rem;
        }

        /* Form Layout - Horizontal */
        .form-section {
            margin-bottom: 2rem;
        }

        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        /* Plan Basic Info - Horizontal Layout */
        .plan-basic-info {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Tasks Section - Horizontal Layout */
        .tasks-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .task-item {
            display: grid;
            grid-template-columns: 2fr 3fr 1.5fr auto;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.75rem;
            border: 2px solid #e5e7eb;
            align-items: start;
            transition: all 0.3s ease;
            position: relative;
        }

        .task-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .task-number {
            position: absolute;
            top: -10px;
            left: 10px;
            background: #667eea;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .task-input-group {
            display: flex;
            flex-direction: column;
        }

        .task-input-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 0.35rem;
        }

        .task-input-group input,
        .task-input-group textarea {
            padding: 0.6rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
        }

        .task-input-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .task-input-group input:focus,
        .task-input-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .task-remove-btn {
            background: #ef4444;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
        }

        .task-remove-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .add-task-btn {
            background: linear-gradient(45deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .add-task-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .add-task-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Modal Footer */
        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .validation-message {
            color: #ef4444;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-cancel {
            background: #6b7280;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #4b5563;
        }

        .btn-save {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-save:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* View Mode Styles */
        .view-section {
            margin-bottom: 2rem;
        }

        .view-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .view-section h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .view-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-item label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-item label i {
            color: #667eea;
            width: 16px;
        }

        .info-value {
            font-size: 1rem;
            color: #1f2937;
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            min-height: 45px;
            display: flex;
            align-items: center;
        }

        .info-value.empty {
            color: #9ca3af;
            font-style: italic;
        }

        .progress-display {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .progress-display .progress-bar {
            flex: 1;
        }

        .progress-text {
            font-weight: 600;
            color: #667eea;
            font-size: 1.1rem;
            min-width: 50px;
        }

        /* Tasks View List */
        .tasks-view-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .task-view-item {
            display: grid;
            grid-template-columns: auto 2fr 3fr 1.5fr auto;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.75rem;
            border: 2px solid #e5e7eb;
            align-items: center;
            transition: all 0.3s ease;
        }

        .task-view-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .task-view-item.completed {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .task-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .task-view-content {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .task-view-name {
            font-weight: 600;
            color: #1f2937;
        }

        .task-view-item.completed .task-view-name {
            text-decoration: line-through;
            color: #6b7280;
        }

        .task-view-description {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .task-view-item.completed .task-view-description {
            color: #9ca3af;
        }

        .task-view-date {
            font-size: 0.85rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .task-view-date i {
            color: #667eea;
        }

        .task-status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .task-status-badge.completed {
            background: #d1fae5;
            color: #065f46;
        }

        .task-status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        /* Edit Mode Button Styles */
        .btn-edit {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Edit Mode Task with Checkbox */
        .edit-task-item {
            display: grid;
            grid-template-columns: auto 2fr 3fr 1.5fr auto;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.75rem;
            border: 2px solid #e5e7eb;
            align-items: start;
            transition: all 0.3s ease;
            position: relative;
        }

        .edit-task-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .edit-task-checkbox-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            margin-top: 1.8rem;
        }

        .edit-task-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .edit-task-checkbox-label {
            font-size: 0.7rem;
            color: #6b7280;
            font-weight: 600;
        }

        /* Modal Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-card {
                padding: 2rem;
            }
            .welcome-title {
                font-size: 2rem;
            }
            .welcome-message {
                font-size: 1rem;
            }
            .study-plans-grid {
                grid-template-columns: 1fr;
            }
            .nav-links {
                flex-direction: column;
                gap: 0.5rem;
            }
            .plan-basic-info {
                grid-template-columns: 1fr;
            }
            .task-item, .edit-task-item {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .task-remove-btn {
                width: 100%;
                margin-top: 0;
            }
            .modal-footer {
                flex-direction: column;
                align-items: stretch;
            }
            .modal-actions {
                width: 100%;
                flex-direction: column;
            }
            .modal-actions button {
                width: 100%;
            }
            .view-info-grid {
                grid-template-columns: 1fr;
            }
            .task-view-item {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            .task-checkbox {
                margin-bottom: 0.5rem;
            }
            .edit-task-checkbox-wrapper {
                margin-top: 0;
                flex-direction: row;
                justify-content: flex-start;
            }
        }

        @media (max-width: 480px) {
            #navbar {
                padding: 1rem;
            }
            .logo {
                font-size: 1.5rem;
            }
            .dashboard-card {
                padding: 1.5rem;
            }
            .welcome-title {
                font-size: 1.8rem;
            }
            .plan-actions {
                flex-direction: column;
            }
            .btn-danger {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav id="navbar">
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <a href="index.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            Study Buddy
        </a>

        <!-- mobile hamburger -->
        <button id="nav-toggle" class="nav-toggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="cta-button primary <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
                <a href="logout.php" class="cta-button primary">Logout</a>
            <?php else: ?>
                <a href="index.php" class="cta-button primary <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a>
                <a href="login.php" class="cta-button primary <?php echo ($currentPage == 'login.php') ? 'active' : ''; ?>">Sign In</a>
                <a href="signup.php" class="cta-button primary <?php echo ($currentPage == 'signup.php') ? 'active' : ''; ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-card">
            <h1 class="welcome-title">My Study Plans</h1>
            <p class="welcome-message">Organize your learning and track your progress.</p>

            <div class="dashboard-section">
                <h2>Your Active Plans</h2>
                
                <!-- Loading state -->
                <div id="loading" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading your study plans...</p>
                </div>

                <!-- Plans container -->
                <div id="plans-container" class="study-plans-grid" style="display: none;"></div>

                <!-- Empty state -->
                <div id="empty-state" class="empty-state" style="display: none;">
                    <i class="fas fa-clipboard-list"></i>
                    <p>You haven't created any study plans yet.</p>
                    <p style="font-size: 0.95rem; color: #9ca3af;">Start planning your success today!</p>
                </div>

                <!-- Create new plan button -->
                <div class="create-plan-section">
                    <button class="btn btn-primary btn-large" onclick="createNewPlan()">
                        <i class="fas fa-plus"></i> Create New Plan
                    </button>
                </div>
            </div>

            <p style="margin-top: 1.5rem; text-align: center;">
                <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 500;">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </p>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Study Buddy. All rights reserved.</p>
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="terms.php">Terms of Service</a>
            <a href="privacy.php">Privacy Policy</a>
        </div>
    </footer>

    <!-- ============================================ -->
    <!-- CREATE PLAN MODAL - Step 2 -->
    <!-- ============================================ -->
    <div id="createPlanModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Create New Study Plan</h2>
                <button class="modal-close" onclick="closeCreateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="createPlanForm" onsubmit="handleCreatePlan(event)">
                <div class="modal-body">
                    <!-- Plan Basic Information -->
                    <div class="form-section">
                        <h3 class="form-section-title">Plan Information</h3>
                        <div class="plan-basic-info">
                            <div class="form-group">
                                <label for="planName">
                                    Plan Name <span style="color: #ef4444;">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="planName" 
                                    name="planName" 
                                    placeholder="e.g., Math Final Exam Preparation"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="planDueDate">
                                    Due Date <span style="color: #ef4444;">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    id="planDueDate" 
                                    name="planDueDate"
                                    required
                                >
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="planDescription">
                                    Description (Optional)
                                </label>
                                <textarea 
                                    id="planDescription" 
                                    name="planDescription"
                                    placeholder="Describe your study plan goals and objectives..."
                                ></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tasks Section -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            Tasks <span style="color: #ef4444;">* (At least 1 required)</span>
                        </h3>
                        
                        <div id="tasksContainer" class="tasks-container">
                            <!-- Task items will be added here dynamically -->
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <button type="button" class="add-task-btn" onclick="addTask()">
                                <i class="fas fa-plus"></i> Add Task
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <div class="validation-message" id="validationMessage"></div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeCreateModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn-save" id="saveBtn">
                            <i class="fas fa-save"></i> Create Plan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- VIEW/EDIT PLAN MODAL - Step 3 -->
    <!-- ============================================ -->
    <div id="viewPlanModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="viewModalTitle">
                    <i class="fas fa-clipboard-list"></i> <span id="viewPlanName">Plan Details</span>
                </h2>
                <button class="modal-close" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editPlanForm" onsubmit="handleUpdatePlan(event)">
                <input type="hidden" id="editPlanId" name="editPlanId">
                
                <div class="modal-body">
                    <!-- View Mode -->
                    <div id="viewMode">
                        <!-- Plan Information Display -->
                        <div class="view-section">
                            <div class="view-header">
                                <h3>Plan Information</h3>
                                <span id="viewStatusBadge" class="plan-status"></span>
                            </div>
                            
                            <div class="view-info-grid">
                                <div class="info-item">
                                    <label><i class="fas fa-heading"></i> Plan Name</label>
                                    <div id="viewPlanNameDisplay" class="info-value"></div>
                                </div>
                                
                                <div class="info-item">
                                    <label><i class="fas fa-calendar-alt"></i> Due Date</label>
                                    <div id="viewDueDateDisplay" class="info-value"></div>
                                </div>
                                
                                <div class="info-item full-width">
                                    <label><i class="fas fa-align-left"></i> Description</label>
                                    <div id="viewDescriptionDisplay" class="info-value"></div>
                                </div>
                                
                                <div class="info-item">
                                    <label><i class="fas fa-chart-line"></i> Progress</label>
                                    <div class="progress-display">
                                        <div class="progress-bar">
                                            <div id="viewProgressFill" class="progress-fill"></div>
                                        </div>
                                        <span id="viewProgressText" class="progress-text"></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <label><i class="fas fa-tasks"></i> Tasks Completed</label>
                                    <div id="viewTasksCompletedDisplay" class="info-value"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tasks List Display -->
                        <div class="view-section">
                            <h3>Tasks</h3>
                            <div id="viewTasksList" class="tasks-view-list">
                                <!-- Tasks will be inserted here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Edit Mode -->
                    <div id="editMode" style="display: none;">
                        <!-- Plan Basic Information -->
                        <div class="form-section">
                            <h3 class="form-section-title">Plan Information</h3>
                            <div class="plan-basic-info">
                                <div class="form-group">
                                    <label for="editPlanName">
                                        Plan Name <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="editPlanName" 
                                        name="editPlanName" 
                                        placeholder="e.g., Math Final Exam Preparation"
                                        required
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="editPlanDueDate">
                                        Due Date <span style="color: #ef4444;">*</span>
                                    </label>
                                    <input 
                                        type="date" 
                                        id="editPlanDueDate" 
                                        name="editPlanDueDate"
                                        required
                                    >
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="editPlanDescription">
                                        Description (Optional)
                                    </label>
                                    <textarea 
                                        id="editPlanDescription" 
                                        name="editPlanDescription"
                                        placeholder="Describe your study plan goals and objectives..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tasks Section -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                Tasks <span style="color: #ef4444;">* (At least 1 required)</span>
                            </h3>
                            
                            <div id="editTasksContainer" class="tasks-container">
                                <!-- Task items will be added here dynamically -->
                            </div>
                            
                            <div style="margin-top: 1rem;">
                                <button type="button" class="add-task-btn" onclick="addEditTask()">
                                    <i class="fas fa-plus"></i> Add Task
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <div class="validation-message" id="editValidationMessage"></div>
                    <div class="modal-actions">
                        <!-- View Mode Actions -->
                        <div id="viewModeActions">
                            <button type="button" class="btn-cancel" onclick="closeViewModal()">
                                Close
                            </button>
                            <button type="button" class="btn-edit" onclick="switchToEditMode()">
                                <i class="fas fa-edit"></i> Edit Plan
                            </button>
                            <button type="button" class="btn-danger" onclick="confirmDeletePlanFromModal()">
                                <i class="fas fa-trash"></i> Delete Plan
                            </button>
                        </div>
                        
                        <!-- Edit Mode Actions -->
                        <div id="editModeActions" style="display: none;">
                            <button type="button" class="btn-cancel" onclick="cancelEdit()">
                                Cancel
                            </button>
                            <button type="submit" class="btn-save" id="updateBtn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
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
        });

        /**
         * Load all study plans from API
         */
        function loadStudyPlans() {
            fetch('api/study-plans.php?action=get_all')
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
                    <button class="btn btn-danger" onclick="confirmDeletePlan(${plan.id}, '${escapeHtml(plan.name).replace(/'/g, "\\'")}')">
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
        function createNewPlan() {
            const modal = document.getElementById('createPlanModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            document.getElementById('createPlanForm').reset();
            document.getElementById('tasksContainer').innerHTML = '';
            document.getElementById('validationMessage').textContent = '';
            taskCounter = 0;
            
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('planDueDate').min = today;
            
            addTask();
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
    </script>
    
    <script src="assets/js/responsive.js" defer></script>
    <script src="/assets/js/pomodoro.js" defer></script>
</body>
</html>