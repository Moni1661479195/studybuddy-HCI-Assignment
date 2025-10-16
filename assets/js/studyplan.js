document.querySelectorAll('.view-plan-btn').forEach(button => {
  button.addEventListener('click', function(e) {
    e.preventDefault();
    const planId = this.getAttribute('data-id');

    // Open modal
    document.getElementById('viewPlanModal').classList.remove('hidden');

    // Load details via AJAX
    fetch('study_plan_view.php?id=' + planId)
      .then(response => response.text())
      .then(data => {
        document.getElementById('planDetails').innerHTML = data;
      });
  });
});

// Close modal
document.getElementById('closeViewModal').addEventListener('click', function() {
  document.getElementById('viewPlanModal').classList.add('hidden');
});
