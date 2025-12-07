<?php
// includes/study_plans_functions.php

require_once 'config.php'; // Ensure config is loaded for DB credentials if needed
require_once 'lib/db.php'; // Ensure database connection is available

function getStudyPlans($user_id) {
    global $pdo; // Assuming $pdo is available globally from db.php

    if (!$pdo) {
        // Handle error if PDO connection is not available
        error_log("PDO connection not available in getStudyPlans.");
        return [];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM study_plans WHERE user_id = :user_id ORDER BY start_date DESC");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching study plans: " . $e->getMessage());
        return [];
    }
}

// You might add other study plan related functions here later
// function createStudyPlan(...) { ... }
// function updateStudyPlan(...) { ... }
// function deleteStudyPlan(...) { ... }
// function getStudyPlanDetails(...) { ... }

?>