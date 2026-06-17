// Custom Alert System
(function() {
    // Create overlay element
    const overlay = document.createElement('div');
    overlay.className = 'custom-alert-overlay';
    overlay.style.display = 'none';
    document.body.appendChild(overlay);

    // Custom alert function
    window.customAlert = function(message, title = 'Alert') {
        return new Promise((resolve) => {
            const alertBox = document.createElement('div');
            alertBox.className = 'custom-alert';
            alertBox.innerHTML = `
                <div class="custom-alert-title">${title}</div>
                <div class="custom-alert-message">${message}</div>
                <div class="custom-alert-actions">
                    <button class="custom-alert-btn custom-alert-btn-primary">OK</button>
                </div>
            `;

            overlay.appendChild(alertBox);
            overlay.style.display = 'flex';

            const okBtn = alertBox.querySelector('.custom-alert-btn-primary');
            okBtn.addEventListener('click', () => {
                overlay.style.display = 'none';
                overlay.removeChild(alertBox);
                resolve(true);
            });
        });
    };

    // Custom confirm function
    window.customConfirm = function(message, title = 'Confirm') {
        return new Promise((resolve) => {
            const confirmBox = document.createElement('div');
            confirmBox.className = 'custom-alert';
            confirmBox.innerHTML = `
                <div class="custom-alert-title">${title}</div>
                <div class="custom-alert-message">${message}</div>
                <div class="custom-alert-actions">
                    <button class="custom-alert-btn custom-alert-btn-secondary">Cancel</button>
                    <button class="custom-alert-btn custom-alert-btn-primary">Confirm</button>
                </div>
            `;

            overlay.appendChild(confirmBox);
            overlay.style.display = 'flex';

            const cancelBtn = confirmBox.querySelector('.custom-alert-btn-secondary');
            const confirmBtn = confirmBox.querySelector('.custom-alert-btn-primary');

            cancelBtn.addEventListener('click', () => {
                overlay.style.display = 'none';
                overlay.removeChild(confirmBox);
                resolve(false);
            });

            confirmBtn.addEventListener('click', () => {
                overlay.style.display = 'none';
                overlay.removeChild(confirmBox);
                resolve(true);
            });
        });
    };

    // Custom confirm with danger button
    window.customConfirmDanger = function(message, title = 'Confirm') {
        return new Promise((resolve) => {
            const confirmBox = document.createElement('div');
            confirmBox.className = 'custom-alert';
            confirmBox.innerHTML = `
                <div class="custom-alert-title">${title}</div>
                <div class="custom-alert-message">${message}</div>
                <div class="custom-alert-actions">
                    <button class="custom-alert-btn custom-alert-btn-secondary">Cancel</button>
                    <button class="custom-alert-btn custom-alert-btn-danger">Confirm</button>
                </div>
            `;

            overlay.appendChild(confirmBox);
            overlay.style.display = 'flex';

            const cancelBtn = confirmBox.querySelector('.custom-alert-btn-secondary');
            const confirmBtn = confirmBox.querySelector('.custom-alert-btn-danger');

            cancelBtn.addEventListener('click', () => {
                overlay.style.display = 'none';
                overlay.removeChild(confirmBox);
                resolve(false);
            });

            confirmBtn.addEventListener('click', () => {
                overlay.style.display = 'none';
                overlay.removeChild(confirmBox);
                resolve(true);
            });
        });
    };
})();
