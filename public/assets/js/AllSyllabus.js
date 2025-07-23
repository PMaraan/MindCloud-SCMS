document.getElementById('searchUniversityInput').addEventListener('input', function () {
  const filter = this.value.toLowerCase();
  const cards = document.querySelectorAll('.college-list .file-card');

  cards.forEach(card => {
    const text = card.innerText.toLowerCase();
    card.style.display = text.includes(filter) ? '' : 'none';
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const card = document.querySelector('.clickable-card');
  if (card) {
    card.addEventListener('click', () => {
      window.location.href = 'Syllabus.php';
    });
  }
});




