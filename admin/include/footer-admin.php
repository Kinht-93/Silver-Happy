        </main>
    </div>
</div>

<footer class="mt-5">
    <div class="text-center py-3 bg-light">&copy; Silver Happy 2026 - Espace Administration</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base_url ?>/admin/assets/admin-crud.js"></script>

<script>
if (typeof window.openModal === 'undefined') {
    window.openModal = function (modalId) {
        var modalEl = document.getElementById(modalId);
        if (!modalEl || typeof bootstrap === 'undefined') {
            console.error('Modal element not found or bootstrap not loaded:', modalId);
            return;
        }
        try {
            var instance = bootstrap.Modal.getInstance(modalEl);
            if (!instance) {
                instance = new bootstrap.Modal(modalEl);
            }
            instance.show();
        } catch(err) {
            console.error('Error opening modal:', err);
        }
    };
}

function setupModalEvents() {
    document.querySelectorAll('[data-modal]').forEach(function(btn) {
        btn.removeEventListener('click', handleModalOpen);
        btn.addEventListener('click', handleModalOpen);
    });
    
    document.querySelectorAll('[data-modal-close]').forEach(function(btn) {
        btn.removeEventListener('click', handleModalClose);
        btn.addEventListener('click', handleModalClose);
    });
}

function handleModalOpen(e) {
    e.preventDefault();
    e.stopPropagation();
    var modalId = this.getAttribute('data-modal');
    if (modalId) {
        window.openModal(modalId);
    }
}

function handleModalClose(e) {
    e.preventDefault();
    var modal = this.closest('.modal');
    if (modal) {
        try {
            var bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        } catch(err) {
            console.error('Error closing modal:', err);
        }
    }
}

function setupTabEvents() {
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(btn) {
        btn.removeEventListener('click', handleTabClick);
        btn.addEventListener('click', handleTabClick);
    });
}

function handleTabClick(e) {
    e.preventDefault();

    var btn = this;
    var group = btn.closest('.btn-group');
    if (group) {
        group.querySelectorAll('.btn').forEach(function(b) {
            b.classList.remove('btn-primary', 'active');
            b.classList.add('btn-outline-primary');
        });
        btn.classList.add('btn-primary', 'active');
        btn.classList.remove('btn-outline-primary');
    }

    var target = btn.getAttribute('data-bs-target');
    if (target) {
        document.querySelectorAll('.tab-pane').forEach(function(pane) {
            pane.classList.remove('show', 'active');
        });

        var targetPane = document.querySelector(target);
        if (targetPane) {
            targetPane.classList.add('show', 'active');
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setupModalEvents();
        setupTabEvents();
    });
} else {
    setupModalEvents();
    setupTabEvents();
}

document.addEventListener('DOMContentLoaded', function() {
    var observer = new MutationObserver(function(mutations) {
        var hasModalElements = false;
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                hasModalElements = true;
            }
        });
        if (hasModalElements) {
            setupModalEvents();
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>
</body>
</html>