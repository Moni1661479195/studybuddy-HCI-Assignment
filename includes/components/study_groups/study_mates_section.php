<?php
// Component: My Study Mates Section
// Variables expected: $study_mates
?>
<div id="study-mates-section">
    <?php if (!empty($study_mates)):
    ?>
        <h2 class="section-title"><i class="fas fa-user-friends"></i> My Study Mates</h2>
        <div class="study-groups-list">
            <?php foreach ($study_mates as $mate) {
                $initials = strtoupper(substr($mate['first_name'], 0, 1) . substr($mate['last_name'], 0, 1));
            ?>
                <div class="study-group-item">
                    <?php if (!empty($mate['profile_picture_path'])) : ?>
                        <img src="<?php echo htmlspecialchars($mate['profile_picture_path']); ?>" alt="<?php echo htmlspecialchars($mate['first_name']); ?>'s avatar" class="user-avatar">
                    <?php else : ?>
                        <div class="user-avatar"><?php echo $initials; ?></div>
                    <?php endif; ?>
                    <div class="group-details">
                        <h3><?php echo htmlspecialchars($mate['first_name'] . ' ' . $mate['last_name']); ?></h3>
                        <p><?php echo htmlspecialchars($mate['email']); ?></p>
                    </div>
                    <a href="user_profile.php?id=<?php echo $mate['id']; ?>" class="cta-button secondary small">View Profile</a>
                </div>
            <?php } ?>
        </div>
    <?php endif; ?>
</div>
