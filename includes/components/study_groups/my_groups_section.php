<?php
// Component: My Study Groups Section
// Variables expected: $my_groups, $db
?>
<div id="my-groups-section">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="section-title"><i class="fas fa-layer-group"></i> My Study Groups</h2>
        <button onclick="openCreateModal()" class="cta-button secondary small">
            <i class="fas fa-plus"></i> Add Group
        </button>
    </div>

    <?php if (empty($my_groups)):
    ?>
        <div class="alert info" style="margin-top: 1rem;">
            <i class="fas fa-info-circle"></i> You are not a member of any study groups yet.
        </div>
    <?php else:
    ?>
        <div class="study-groups-list">
            <?php foreach ($my_groups as $group):
                // Get chat room for this group
                $room_stmt = $db->prepare("SELECT id FROM chat_rooms WHERE room_type = 'group' AND group_id = ?");
                $room_stmt->execute([$group['group_id']]);
                $room = $room_stmt->fetch(PDO::FETCH_ASSOC);
            ?>
                <div class="study-group-item">
                    <i class="fas fa-users group-icon"></i>
                    <div class="group-details">
                        <h3><?php echo htmlspecialchars($group['group_name']); ?></h3>
                        <p><?php echo $group['member_count']; ?> members</p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <a href="group-details.php?id=<?php echo $group['group_id']; ?>" class="cta-button secondary small">Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
