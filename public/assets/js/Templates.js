/**
 * Initializes the template cards UI:
 * - Attaches Bootstrap dropdowns properly
 * - Handles delete button events with placeholders for backend API calls
 * - Supports adding new cards (for testing/demo)
 */
function initializeTemplateCards() {
  const templateList = document.getElementById('template-list');
  const testInsertBtn = document.getElementById('testInsertCardBtn');
  const plusCard = templateList?.querySelector('a[href="TemplateBuilder.php"]');

  if (!templateList || !testInsertBtn || !plusCard) {
    console.warn('❌ Required elements not found. Initialization aborted.');
    return;
  }

  // Store references to avoid duplicate event bindings or dropdown instances
  const dropdownInstances = new WeakMap();
  const deleteButtonListeners = new WeakSet();

  /**
   * Attach dropdown and delete event handlers to a card element.
   * @param {HTMLElement} card The template card element
   */
  function attachCardEvents(card) {
    // Initialize Bootstrap dropdown for dropdown toggle button
    const dropdownToggle = card.querySelector('[data-bs-toggle="dropdown"]');
    if (dropdownToggle) {
      if (!bootstrap.Dropdown.getInstance(dropdownToggle)) {
        const instance = new bootstrap.Dropdown(dropdownToggle);
        dropdownInstances.set(dropdownToggle, instance);
      }
    }

    // Attach delete button event handler (only once)
    const deleteBtn = card.querySelector('[data-action="delete"]');
    if (deleteBtn && !deleteButtonListeners.has(deleteBtn)) {
      deleteBtn.addEventListener('click', event => {
        event.preventDefault();
        const templateId = card.dataset.templateId;

        console.log(`🗑️ Delete requested for template id=${templateId}`);

        // TODO: Implement backend delete API call here, for example:
        // fetch(`/api/delete_template.php?id=${templateId}`, { method: 'POST' })
        //   .then(response => {
        //     if (response.ok) {
        //       removeCardVisual();
        //     } else {
        //       alert('Delete failed');
        //     }
        //   });

        // Remove card visually with fade-out animation
        card.classList.add('opacity-0');
        setTimeout(() => card.remove(), 300);
      });
      deleteButtonListeners.add(deleteBtn);
    }
  }

  /**
   * Creates a new template card element.
   * @param {string} title The template title shown on the card
   * @param {string|number} templateId Unique identifier for the template
   * @returns {HTMLElement} The constructed card element
   */
  function createTemplateCard(title = 'New Template', templateId = Date.now() % 100000) {
    const card = document.createElement('a');
    card.className = 'template-card position-relative opacity-0 text-decoration-none text-dark d-flex flex-column';
    card.href = `TemplateBuilder.php?id=${templateId}`;
    card.dataset.templateId = templateId;

    card.innerHTML = `
      <span class="badge status-badge bg-secondary">Draft</span>

      <div class="dropdown position-absolute top-0 end-0 m-2">
        <button class="btn btn-sm p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-three-dots-vertical" aria-label="Template options"></i>
        </button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="#">Edit</a></li>
          <li><a class="dropdown-item" href="#" data-action="delete">Delete</a></li>
        </ul>
      </div>

      <div class="template-footer text-white fw-bold text-center">${title}</div>
    `;
    return card;
  }

  /**
   * Inserts a new template card into the UI next to the '+' card.
   * Useful for testing or adding new templates client-side.
   * @param {string} title The title for the new card
   */
  function insertNewCard(title = 'Draft') {
    const newId = Date.now() % 100000;
    const newCard = createTemplateCard(`${title} ${newId}`, newId);

    if (plusCard.nextSibling) {
      templateList.insertBefore(newCard, plusCard.nextSibling);
    } else {
      templateList.appendChild(newCard);
    }

    attachCardEvents(newCard);
    requestAnimationFrame(() => newCard.classList.remove('opacity-0'));
  }

  // Bind test insert button for demo usage
  testInsertBtn.addEventListener('click', () => {
    insertNewCard('Draft');
  });

  // Initialize existing cards on page load (skip the plus card)
  Array.from(templateList.children).forEach(card => {
    if (card !== plusCard) attachCardEvents(card);
  });
}

// Expose globally so backend devs can call if content is dynamically loaded
window.initializeTemplateCards = initializeTemplateCards;

// Auto-run on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeTemplateCards);
} else {
  initializeTemplateCards();
}
