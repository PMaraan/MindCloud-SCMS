document.addEventListener('DOMContentLoaded', () => {
  const addFolderBtn = document.getElementById('addFolderBtn');
  const fileList = document.getElementById('fileList');

  if (!addFolderBtn || !fileList) return;

  /**
   * Creates a new syllabus card element.
   * @param {string} title The syllabus title shown on the card
   * @param {string|number} syllabusId Unique identifier for the syllabus
   * @param {string} status Current status (e.g., 'Pending', 'Approved')
   * @returns {HTMLElement} The constructed card element
   */
  function createSyllabusCard(title = 'New Syllabus', syllabusId = Date.now() % 100000) {
  const card = document.createElement('a');
  card.className = 'syllabus-card position-relative opacity-0 text-decoration-none text-dark d-flex flex-column';
  card.href = `ContentController.php?type=syllabus&id=${syllabusId}`;
  card.dataset.syllabusId = syllabusId;

  card.innerHTML = `
    <span class="badge status-badge bg-warning text-dark">Pending</span>

    <div class="dropdown position-absolute top-0 end-0 m-2">
      <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-three-dots-vertical" aria-label="Syllabus options"></i>
      </button>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#">Edit</a></li>
        <li><a class="dropdown-item" href="#" data-action="delete">Delete</a></li>
      </ul>
    </div>

    <div class="syllabus-footer text-white fw-bold text-center">
      <i class="bi bi-folder me-2"></i>${title}
    </div>
  `;
  return card;
}

function insertNewSyllabusCard(title = 'New Syllabus') {
  const newId = Date.now() % 100000;
  const card = createSyllabusCard(title, newId);
  const list = document.getElementById('syllabus-list');
  const addButton = document.getElementById('add-syllabus-card');

  if (addButton?.nextSibling) {
    list.insertBefore(card, addButton.nextSibling);
  } else {
    list.appendChild(card);
  }

  // Add transition effect
  requestAnimationFrame(() => card.classList.remove('opacity-0'));
}


  // 📌 Event: Add Folder (Syllabus Card)
  addFolderBtn.addEventListener('click', () => {
    // You can replace Date.now() with the ID from the backend (AJAX/fetch)
    const card = createSyllabusCard();
    fileList.appendChild(card);
  });
});
