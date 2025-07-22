let templateCardsInitialized = false;

function initializeTemplateCards() {
  if (templateCardsInitialized) return;
  templateCardsInitialized = true;

  const templateList = document.getElementById('template-list');
  const testInsertBtn = document.getElementById('testInsertCardBtn');
  const plusCard = templateList?.querySelector('a[href="TemplateBuilder.php"]');

  if (!templateList || !testInsertBtn || !plusCard) {
    console.warn('❌ Required elements not found');
    return;
  }

  /**
   * Attaches event handlers (dropdown toggle + delete) to a single template card.
   * Called for both dynamically inserted and existing cards.
   */
  function attachCardEvents(card) {
    const toggleBtn = card.querySelector('[data-bs-toggle="dropdown"]');
    if (toggleBtn) {
      // Bootstrap needs explicit instantiation for dropdowns created dynamically
      new bootstrap.Dropdown(toggleBtn);
    }

    const deleteBtn = card.querySelector('[data-action="delete"]');
    if (deleteBtn) {
      deleteBtn.addEventListener('click', e => {
        e.preventDefault();

        // Optional: call backend to delete via templateId
        const id = card.dataset.templateId;
        console.log(`🗑️ Request to delete template id=${id}`);
        // Example: fetch(`/api/delete_template.php?id=${id}`, { method: 'POST' });

        // Remove the card visually
        card.classList.add('opacity-0');
        setTimeout(() => card.remove(), 300);
      });
    }
  }

  /**
   * Creates a new template card element.
   * @param {string} title - Title shown on the card
   * @param {string|number} templateId - Unique backend template ID
   * @returns {HTMLElement}
   */
  function createTemplateCard(title = 'New Template', templateId = Date.now() % 100000) {
    const card = document.createElement('a'); // Use <a> for linking
    card.className = 'template-card position-relative opacity-0 text-decoration-none text-dark d-flex flex-column';
    card.href = `TemplateBuilder.php?id=${templateId}`; // Pass template id in the URL
    card.dataset.templateId = templateId; // For backend reference (e.g. deletion)

    card.innerHTML = `
      <span class="badge status-badge bg-secondary">Draft</span>

      <!-- Dropdown Menu -->
      <div class="dropdown position-absolute top-0 end-0 m-2">
        <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-three-dots-vertical" style="font-size: 1.2rem; color: #555;"></i>
        </button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="#">Edit</a></li>
          <li><a class="dropdown-item" href="#" data-action="delete">Delete</a></li>
        </ul>
      </div>

      <!-- Footer with template title -->
      <div class="template-footer text-white fw-bold text-center">${title}</div>
    `;
    return card;
  }

  /**
   * Inserts a new card visually (for demo or testing).
   * Backend should eventually replace this with real data.
   */
  function insertNewCard(title = 'Draft') {
    const randomId = Date.now() % 100000; // Simulated template ID (mock)
    const card = createTemplateCard(`${title} ${randomId}`, randomId);

    if (plusCard.nextSibling) {
      templateList.insertBefore(card, plusCard.nextSibling);
    } else {
      templateList.appendChild(card);
    }

    attachCardEvents(card);
    requestAnimationFrame(() => card.classList.remove('opacity-0'));
  }

  // Handler for test button (create fake card)
  testInsertBtn.addEventListener('click', () => {
    insertNewCard('Draft');
  });

  // Attach handlers to any pre-rendered cards (e.g. from PHP backend)
  [...templateList.children].forEach(child => {
    if (child !== plusCard) attachCardEvents(child);
  });
}

// Allow dynamic calls after partial loads
window.initializeTemplateCards = initializeTemplateCards;

// Run immediately if DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeTemplateCards);
} else {
  initializeTemplateCards();
}
