        </main>
    </div>
</div>

<footer class="mt-5">
    <div class="text-center py-3 bg-light">&copy; Silver Happy 2026 - Espace Administration</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/Silver-Happy-main/admin/assets/admin-crud.js"></script>

<script>
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();

        const group = btn.closest('.btn-group');
        if (group) {
            group.querySelectorAll('.btn').forEach(b => {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-primary');
            });
            btn.classList.add('btn-primary', 'active');
            btn.classList.remove('btn-outline-primary');
        }

        const target = btn.getAttribute('data-bs-target');
        if (target) {
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            const targetPane = document.querySelector(target);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        });
    });
});
</script>
</body>
</html>