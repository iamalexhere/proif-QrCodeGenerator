// Copy ke clipboard
function copyToClipboard(text, event = null) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // --- coba Clipboard API dulu ---
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text)
            .then(() => {
                showNotification('Link copied to clipboard!', 'success');
            })
            .catch(err => {
                console.error('Clipboard API failed:', err);
                fallbackCopyText(text);
            });
    } else {
        // --- fallback otomatis ---
        fallbackCopyText(text);
    }
}

function fallbackCopyText(text) {
    // Buat input temporary
    const tempInput = document.createElement('input');
    tempInput.value = text;
    tempInput.style.position = 'fixed';
    tempInput.style.opacity = 0;
    document.body.appendChild(tempInput);

    // Pilih & copy
    tempInput.select();
    tempInput.setSelectionRange(0, text.length);
    document.execCommand('copy');

    // Hapus elemen sementara
    document.body.removeChild(tempInput);

    showNotification('Link copied to clipboard!', 'success');
}

// Download QR Code
function downloadQR(url, filename) {
    // Buat URL QR Code dari API eksternal
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(url)}`;
    const link = document.createElement('a');
    link.href = qrUrl;
    link.download = `${filename}_qr_code.png`; // Nama file hasil download
    link.click(); // Jalankan proses download

    showNotification('QR Code downloaded successfully!', 'success');
}

// Mengubah status QR Code (pause / resume)
function toggleStatus(shortUrl, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'paused' : 'active';
    const actionText = newStatus === 'paused' ? 'pause' : 'resume';

    // Buat popup konfirmasi
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

    // Tampilkan animasi popup
    setTimeout(() => confirmBox.classList.add('show'), 10);

    // Jika klik "Yes" → ubah status
    document.getElementById('confirm-yes').addEventListener('click', () => {
        updateStatus(shortUrl, newStatus);
        confirmBox.remove();
    });

    // Jika klik "Cancel" → tutup popup
    document.getElementById('confirm-no').addEventListener('click', () => {
        confirmBox.classList.remove('show');
        setTimeout(() => confirmBox.remove(), 200);
    });
}

// Kirim status baru ke server
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

                // Redirect atau reload halaman sesuai status
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

// Tampilkan notifikasi di pojok kanan atas
function showNotification(msg, type) {
    const el = document.createElement('div');
    el.className = `notification ${type}`;
    el.textContent = msg;

    // Styling dasar notifikasi
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

    setTimeout(() => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    }, 100);

    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-20px)';
        setTimeout(() => el.remove(), 300);
    }, 3000);
}

// Fungsi pencarian QR Code di dashboard
function searchQRCodes() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.qr-card:not(.create-card)');
    let visibleCount = 0;

    // Filter card qr berdasarkan judul
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

    // Tampilkan pesan jika tidak ada hasil
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

// reload halaman
function clearSearch() {
    window.location.href = '<?php echo $current_page_name; ?>';
}
