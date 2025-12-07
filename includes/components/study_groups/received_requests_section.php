<?php
// Component: Received Study Requests Section
// Variables expected: $received_requests
?>
<div id="requests-section">
    <?php if (!empty($received_requests)):
    ?>
        <h2 class="section-title"><i class="fas fa-inbox"></i> Received Study Requests</h2>
        <div class="requests-list">
            <?php foreach ($received_requests as $request):
                $sender_initials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
            ?>
                <div class="request-item">
                    <div class="user-info">
                                                <?php if (!empty($request['profile_picture_path'])) : ?>
                            <img src="<?php echo htmlspecialchars($request['profile_picture_path']); ?>" alt="<?php echo htmlspecialchars($request['first_name']); ?>'s avatar" class="user-avatar">
                        <?php else : ?>
                            <div class="user-avatar"><?php echo $sender_initials; ?></div>
                        <?php endif; ?>
                        <div class="user-details">
                            <h4><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></h4>
                            <p><?php echo htmlspecialchars($request['email']); ?></p>
                            <p style="font-size: 0.8rem; color: #9ca3af;">
                                Sent: <?php echo date('M d, Y H:i', strtotime($request['requested_at'])); ?>
                            </p>
                        </div>
                    </div>
                    <div class="request-actions">
<form action="study-groups.php" method="POST" style="display:inline;" class="no-ajax">
                            <input type="hidden" name="action" value="accept_request">
                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                            <button type="submit" id="accept-request-<?php echo $request['request_id']; ?>" class="cta-button primary small">
                                <i class="fas fa-check"></i> Accept
                            </button>
                        </form>
                        <form action="study-groups.php" method="POST" style="display:inline;" class="no-ajax">
                            <input type="hidden" name="action" value="decline_request">
                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                            <button type="submit" id="decline-request-<?php echo $request['request_id']; ?>" class="cta-button danger small">
                                <i class="fas fa-times"></i> Decline
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <h2 class="section-title"><i class="fas fa-inbox"></i> Received Study Requests</h2>
        <div class="alert info">
            <i class="fas fa-inbox"></i> No pending study requests.
        </div>
    <?php endif; ?>
</div>
