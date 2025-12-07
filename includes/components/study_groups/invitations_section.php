<?php
// Component: Group Invitations Section
// Variables expected: $invitations
?>
<div id="invitations-section">
    <?php if (!empty($invitations)):
    ?>
    <div style="margin-top: 2rem;">
        <h2 class="section-title"><i class="fas fa-envelope"></i> Group Invitations</h2>
        <div class="study-groups-list">
            <?php foreach ($invitations as $inv):
            ?>
                <div class="study-group-item">
                    <?php if (!empty($inv['profile_picture_path'])) : ?>
                        <img src="<?php echo htmlspecialchars($inv['profile_picture_path']); ?>" alt="<?php echo htmlspecialchars($inv['sender_name']); ?>'s avatar" class="user-avatar">
                    <?php else : ?>
                        <div class="user-avatar"><?php echo strtoupper(substr($inv['sender_name'], 0, 1)); ?></div>
                    <?php endif; ?>
                    <div class="group-details">
                        <h3><?php echo htmlspecialchars($inv['group_name']); ?></h3>
                        <p>Invited by: <?php echo htmlspecialchars($inv['sender_name']); ?></p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <form method="POST" action="study-groups.php" style="margin: 0;" class="no-ajax">
                            <input type="hidden" name="invitation_action" value="accept">
                            <input type="hidden" name="invitation_id" value="<?php echo $inv['id']; ?>">
                            <button type="submit" class="cta-button primary small">Accept</button>
                        </form>
                        <form method="POST" action="study-groups.php" style="margin: 0;" class="no-ajax">
                            <input type="hidden" name="invitation_action" value="decline">
                            <input type="hidden" name="invitation_id" value="<?php echo $inv['id']; ?>">
                            <button type="submit" class="cta-button danger small">Decline</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
