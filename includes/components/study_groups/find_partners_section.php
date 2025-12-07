<?php
// Component: Find Study Partners Section
?>
<h2 class="section-title"><i class="fas fa-user-search"></i> Find Study Partners</h2>
<div class="find-users-form">
    <div class="quick-match-form" style="text-align: center; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 0.75rem; background: #f8fafc; margin-bottom: 2rem;">
        <h3 style="margin-top: 0; font-size: 1.2rem; color: #1f2937;">Find a Study Partner Instantly</h3>
        <p style="color: #6b7280; margin: 0.5rem 0 1.5rem 0;">We'll match you with a partner based on your profile's skill level and interests.</p>
        
        <div style="margin: 1rem 0;">
            <label for="desired-skill-level-select" style="font-weight: 500; margin-right: 0.5rem;">I'm looking for a partner with skill level:</label>
            <select id="desired-skill-level-select" style="padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #d1d5db;">
                <option value="any">Any Level</option>
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
            </select>
        </div>
        <div style="margin: 1rem 0;">
            <label for="desired-gender-select" style="font-weight: 500; margin-right: 0.5rem;">I'm looking for a partner with gender:</label>
            <select id="desired-gender-select" style="padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #d1d5db;">
                <option value="any">Any</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>
        
        <button type="button" id="start-quick-match-btn" class="cta-button primary large">
            <i class="fas fa-bolt"></i> Start Quick Match
        </button>
    </div>

    <form id="search-form" action="study-groups.php" method="GET" class="search-form">
        <input type="text" 
               name="search_user" 
               placeholder="Search by name or email..." 
               value="<?php echo htmlspecialchars($_GET['search_user'] ?? ''); ?>" 
               required
               autocomplete="off">
        <button type="submit">
            <i class="fas fa-search"></i> Search
        </button>
        <div id="search-suggestions"></div>
    </form>

    <div id="recommended-partners-section">
    </div>
</div>
