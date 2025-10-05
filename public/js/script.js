/**
 * Utility Functions for Dashboard QR Management
 * Author: (Your Name)
 * Description: Handles copy, download, toggle, and search functionality.
 */

// ✅ Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Link copied to clipboard!', 'success');
    });
}

// ✅ Download QR Code
function downloadQR(url, filename) {
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
    const link = document.createElement('a');
    link.href = qrUrl;
    link.download = `${filename}_qr_code.png`;
    link.click();

    showNotification('QR Code downloaded successfully!', 'success');
}

// ✅ Toggle QR Code Status (Pause / Resume)
function toggleStatus(shortUrl, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    const actionText = newStatus === 'paused' ? 'pause' : 'resume';

    // Create confirmation dialog
    const confirmBox = document.createElement('div');
    confirmBox.className = 'confirm-box';
    confirmBox.innerHTML = `
    <div class="confirm-content">
      <h3>Confirm Action</h3>
      <p>Are you sure you want to <b>${actionText}</b> this QR Code?</p>
      <div class="confirm-actions">
        <button id="confirm-yes" class="btn-confirm yes">Yes</button>
        <button id="confirm-no" class="btn-confirm no">Cancel</button>
      </div>
    </div>
  `;
    document.body.appendChild(confirmBox);

    // Animate box
    setTimeout(() => confirmBox.classList.add('show'), 10);

    // Confirm button handler
    document.getElementById('confirm-yes').addEventListener('click', () => {
        updateStatus(shortUrl, newStatus);
        confirmBox.remove();
    });

    // Cancel button handler
    document.getElementById('confirm-no').addEventListener('click', () => {
        confirmBox.classList.remove('show');
        setTimeout(() => confirmBox.remove(), 200);
    });
}

// ✅ Update QR Code Status (AJAX)
function updateStatus(shortUrl, newStatus) {
    fetch('updateStatus.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `short_url=${encodeURIComponent(shortUrl)}&status=${newStatus}`
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification(
                    `QR Code ${newStatus === 'active' ? 'resumed' : 'paused'} successfully!`,
                    'success'
                );

                setTimeout(() => {
                    if (newStatus === 'paused') {
                        window.location.href = 'dashboardPause.php';
                    } else {
                        location.reload();
                    }
                }, 1000);
            } else {
                showNotification('Failed to update status', 'error');
            }
        })
        .catch(() => showNotification('An error occurred', 'error'));
}

// ✅ Notification System
function showNotification(msg, type) {
    const el = document.createElement('div');
    el.className = `notification ${type}`;
    el.textContent = msg;

    el.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    z-index: 1000;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
    ${type === 'success' ? 'background:#4CAF50;' : 'background:#f44336;'}
  `;

    document.body.appendChild(el);

    // Fade in
    setTimeout(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    }, 100);

    // Fade out
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-20px)';
        setTimeout(() => el.remove(), 300);
    }, 3000);
}

// ✅ Search Function for Dashboard
function searchQRCodes() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.qr-card:not(.create-card)');
    let visibleCount = 0;

    cards.forEach(card => {
        const titleEl = card.querySelector('.card-title h3');
        const title = titleEl ? titleEl.textContent.toLowerCase() : '';

        if (title.includes(filter)) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show "No Results" message
    const dashboard = document.querySelector('.dashboard');
    let noResultMsg = document.getElementById('no-search-result');

    if (visibleCount === 0 && filter !== '') {
        if (!noResultMsg) {
            noResultMsg = document.createElement('div');
            noResultMsg.id = 'no-search-result';
            noResultMsg.className = 'empty-state';
            noResultMsg.innerHTML = `
        <h3>No QR Codes Found</h3>
        <p>No results match your search "<b>${filter}</b>"</p>
      `;
            dashboard.appendChild(noResultMsg);
        }
    } else if (noResultMsg) {
        noResultMsg.remove();
    }
}

function clearSearch() {
    window.location.href = '<?php echo $current_page_name; ?>';
}
