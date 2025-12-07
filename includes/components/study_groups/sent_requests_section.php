<?php
// Component: Sent Study Requests Section
// Variables expected: $sent_requests
?>
<div id="sent-requests-section">
    <?php if (!empty($sent_requests)):
    ?>
        <h2 class="section-title"><i class="fas fa-paper-plane"></i> Sent Study Requests</h2>
        <div class="user-results-list">
            <?php foreach ($sent_requests as $request): ?>
                <div class="user-result-item">
                    <div class="user-info">
                        <?php if (!empty($request['profile_picture_path'])) : ?>
                            <img src="<?php echo htmlspecialchars($request['profile_picture_path']); ?>" alt="<?php echo htmlspecialchars($request['first_name']); ?>'s avatar" class="user-avatar">
                        <?php else : ?>
                            <div class="user-avatar"><?php echo strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1)); ?></div>
                        <?php endif; ?>
                        <div class="user-details">
                            <h4><a href="user_profile.php?id=<?php echo $request['user_id']; ?>"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></a></h4>
                            <p><?php echo htmlspecialchars($request['email']); ?></p>
                        </div>
                    </div>
                    <div class="request-status">
                        <span class="status-text pending">
                            <i class="fas fa-clock"></i> Request Sent
                        </span>
                        <form action="study-groups.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="cancel_study_request">
                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                            <button type="submit" class="cta-button danger small">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
