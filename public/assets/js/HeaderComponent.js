document.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', () => {
      item.classList.remove('unread');
      item.querySelector('.dot')?.remove();

      const anyUnread = document.querySelectorAll('.dropdown-item.unread').length > 0;
      if (!anyUnread) {
        document.getElementById('notifDot').style.display = 'none';
      }
    });
  });

