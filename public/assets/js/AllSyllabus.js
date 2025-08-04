document.getElementById('searchUniversityInput').addEventListener('input', function () {
  const filter = this.value.toLowerCase();
  const cards = document.querySelectorAll('.college-list .file-card');

  cards.forEach(card => {
    const text = card.innerText.toLowerCase();
    card.style.display = text.includes(filter) ? '' : 'none';
  });
});

document.addEventListener('DOMContentLoaded', function () {
  /*
  const card = document.querySelector('.clickable-card');
  if (card) {
    card.addEventListener('click', () => {
      window.location.href = 'Syllabus.php';
    });
  }
*/

  document.querySelectorAll('tr.collegeEntry').forEach(row => {
    row.addEventListener('click', (e) => {
      console.log('Row clicked:', row); // ✅ see if this logs

      // Ignore clicks on buttons, inputs, links (optional if needed)
      if (e.target.tagName.toLowerCase() === 'a') {
        e.preventDefault(); // prevents link behavior
      }

      const collegeId = row.getAttribute('data-college-id');
      if (collegeId) {
        // Redirect to the target page with the ID as a GET parameter
        window.location.href = `Syllabus.php?college_id=${encodeURIComponent(collegeId)}`;
      }
    });
  });


});




