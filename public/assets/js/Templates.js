document.addEventListener('DOMContentLoaded', function () {
  console.log('Script is working!');

  const templateList = document.getElementById('template-list');
  const testInsertBtn = document.getElementById('testInsertCardBtn');

  // Get the "+" card by its unique href
  const plusCard = templateList.querySelector('a[href="TemplateBuilder.php"]');

  function createTemplateCard(title = 'New Template') {
    const card = document.createElement('div');
    card.className = 'template-card position-relative opacity-0';
    card.style.transition = 'opacity 0.3s ease'; // Ensures fade-in
    card.innerHTML = `
      <span class="badge status-badge bg-secondary">Draft</span>
      <div class="dropdown position-absolute top-0 end-0 m-2">
        <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="#">Edit</a></li>
          <li><a class="dropdown-item" href="#">Delete</a></li>
        </ul>
      </div>
      <div class="template-footer text-white fw-bold text-center">${title}</div>
    `;
    return card;
  }

  function insertNewCard(title = 'Test Template') {
    const newCard = createTemplateCard(title);
    const nextSibling = plusCard.nextElementSibling;

    if (nextSibling) {
      templateList.insertBefore(newCard, nextSibling);
    } else {
      templateList.appendChild(newCard);
    }

    // Delay fade-in to trigger transition
    requestAnimationFrame(() => {
      newCard.classList.remove('opacity-0');
    });
  }

  if (testInsertBtn) {
    testInsertBtn.addEventListener('click', () => {
      insertNewCard('Test Template ' + (Date.now() % 100000));
    });
  } else {
    console.warn('Test Insert Button not found!');
  }
});
