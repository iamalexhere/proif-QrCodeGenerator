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

// Download QR Code in specific format
function downloadQR(shortCode, filename, format = 'png') {
    // Use our multi-format download endpoint
    const downloadUrl = `download_qr_multi.php?code=${encodeURIComponent(shortCode)}&format=${format}`;

    // Create a temporary link and trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `${filename}.${format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showNotification(`QR Code downloaded as ${format.toUpperCase()} successfully!`, 'success');
}

// Show download format dropdown with mobile optimization
function showDownloadOptions(shortCode, filename) {
    // Create dropdown menu
    const dropdown = document.createElement('div');
    dropdown.className = 'download-dropdown';
    dropdown.innerHTML = `
        <div class="download-dropdown-content">
            <div class="download-dropdown-header">Choose Format</div>
            <button class="download-option" onclick="downloadQR('${shortCode}', '${filename}', 'png'); hideDownloadOptions();">
                📱 Download PNG
            </button>
            <button class="download-option" onclick="downloadQR('${shortCode}', '${filename}', 'svg'); hideDownloadOptions();">
                🎨 Download SVG
            </button>
            <button class="download-option" onclick="downloadQR('${shortCode}', '${filename}', 'pdf'); hideDownloadOptions();">
                📄 Download PDF
            </button>
            <button class="download-option cancel" onclick="hideDownloadOptions();">
                ❌ Cancel
            </button>
        </div>
    `;

    // Detect if mobile device
    const isMobile = window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    // Add styles with mobile optimization
    dropdown.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        align-items: ${isMobile ? 'flex-end' : 'center'};
        justify-content: center;
        padding: ${isMobile ? '0' : '20px'};
    `;

    const content = dropdown.querySelector('.download-dropdown-content');
    content.style.cssText = `
        background: white;
        border-radius: ${isMobile ? '12px 12px 0 0' : '12px'};
        padding: ${isMobile ? '24px 20px 32px' : '20px'};
        box-shadow: 0 ${isMobile ? '-4px' : '10px'} 30px rgba(0, 0, 0, 0.3);
        min-width: ${isMobile ? '100%' : '280px'};
        max-width: ${isMobile ? '100%' : '350px'};
        text-align: center;
        ${isMobile ? 'margin: 0; border-radius: 12px 12px 0 0;' : ''}
    `;

    const header = dropdown.querySelector('.download-dropdown-header');
    header.style.cssText = `
        font-size: ${isMobile ? '20px' : '18px'};
        font-weight: bold;
        margin-bottom: ${isMobile ? '20px' : '15px'};
        color: #333;
    `;

    const options = dropdown.querySelectorAll('.download-option');
    options.forEach(option => {
        option.style.cssText = `
            display: block;
            width: 100%;
            padding: ${isMobile ? '16px 20px' : '12px 15px'};
            margin: ${isMobile ? '12px 0' : '8px 0'};
            border: none;
            border-radius: 8px;
            background: #f8f9fa;
            color: #333;
            cursor: pointer;
            font-size: ${isMobile ? '16px' : '14px'};
            transition: background 0.2s;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        `;

        // Enhanced touch events for mobile
        if (isMobile) {
            option.addEventListener('touchstart', () => {
                if (!option.classList.contains('cancel')) {
                    option.style.background = '#007bff';
                    option.style.color = 'white';
                } else {
                    option.style.background = '#dc3545';
                    option.style.color = 'white';
                }
            });

            option.addEventListener('touchend', () => {
                setTimeout(() => {
                    option.style.background = '#f8f9fa';
                    option.style.color = '#333';
                }, 150);
            });
        } else {
            option.addEventListener('mouseenter', () => {
                if (!option.classList.contains('cancel')) {
                    option.style.background = '#007bff';
                    option.style.color = 'white';
                } else {
                    option.style.background = '#dc3545';
                    option.style.color = 'white';
                }
            });

            option.addEventListener('mouseleave', () => {
                option.style.background = '#f8f9fa';
                option.style.color = '#333';
            });
        }
    });

    document.body.appendChild(dropdown);

    // Close on outside click/touch
    dropdown.addEventListener('click', (e) => {
        if (e.target === dropdown) {
            hideDownloadOptions();
        }
    });

    // Prevent body scroll on mobile when dropdown is open
    if (isMobile) {
        document.body.style.overflow = 'hidden';
    }
}

// Hide download options dropdown
function hideDownloadOptions() {
    const dropdown = document.querySelector('.download-dropdown');
    if (dropdown) {
        dropdown.remove();
    }
    // Restore body scroll on mobile
    document.body.style.overflow = '';
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
        body: `short_url=${encodeURIComponent(shortUrl)}&action=update_status&status=${newStatus}`
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification(
                    `QR Code ${newStatus === 'active' ? 'resumed' : 'paused'} successfully! Redirecting...`,
                    'success'
                );

                // Redirect sesuai status baru dengan preserve search parameter
                setTimeout(() => {
                    const urlParams = new URLSearchParams(window.location.search);
                    const searchParam = urlParams.get('search');
                    const searchQuery = searchParam ? '?search=' + encodeURIComponent(searchParam) : '';

                    if (newStatus === 'paused') {
                        window.location.href = 'dashboardPause.php' + searchQuery;
                    } else {
                        window.location.href = 'dashboardActive.php' + searchQuery;
                    }
                }, 2000);
            } else {
                showNotification(data.message || 'Failed to update status', 'error');
            }
        })
        .catch(() => showNotification('An error occurred', 'error'));
}

// Delete QR Code function
function deleteQRCode(shortUrl) {
    // Create confirmation popup
    const confirmBox = document.createElement('div');
    confirmBox.className = 'confirm-box';
    confirmBox.innerHTML = `
        <div class="confirm-content">
            <h3 style="color: #dc3545;">⚠️ Delete QR Code</h3>
            <p>Are you sure you want to <b>permanently delete</b> this QR Code?</p>
            <p style="color: #666; font-size: 14px;">This action cannot be undone. The short link will no longer work.</p>
            <div class="confirm-actions">
                <button id="confirm-delete" class="btn-confirm delete" style="background: #dc3545;">Yes, Delete</button>
                <button id="confirm-cancel" class="btn-confirm no">Cancel</button>
            </div>
        </div>
    `;
    document.body.appendChild(confirmBox);

    // Show animation
    setTimeout(() => confirmBox.classList.add('show'), 10);

    // Handle delete confirmation
    document.getElementById('confirm-delete').addEventListener('click', () => {
        confirmBox.remove();

        // Show loading notification
        showNotification('Deleting QR Code...', 'info');

        // Send delete request
        fetch('updateStatus.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `short_url=${encodeURIComponent(shortUrl)}&action=delete`
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification('QR Code deleted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Failed to delete QR Code', 'error');
                }
            })
            .catch(() => showNotification('An error occurred while deleting', 'error'));
    });

    // Handle cancel
    document.getElementById('confirm-cancel').addEventListener('click', () => {
        confirmBox.classList.remove('show');
        setTimeout(() => confirmBox.remove(), 200);
    });
}

// Tampilkan notifikasi di pojok kanan atas
function showNotification(msg, type) {
    const el = document.createElement('div');
    el.className = `notification ${type}`;
    el.textContent = msg;

    // Styling dasar notifikasi
    let backgroundColor = '#f44336'; // default error
    if (type === 'success') backgroundColor = '#4CAF50';
    else if (type === 'info') backgroundColor = '#2196F3';

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
    background: ${backgroundColor};
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

// Clear search - reload page without search parameter
function clearSearch() {
    window.location.href = window.location.pathname;
}