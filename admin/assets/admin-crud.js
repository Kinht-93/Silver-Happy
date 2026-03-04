window.openModal = function (modalId) {
    var modalEl = document.getElementById(modalId);
    if (!modalEl || typeof bootstrap === 'undefined') {
        return;
    }
    var instance = bootstrap.Modal.getInstance(modalEl);
    if (!instance) {
        instance = new bootstrap.Modal(modalEl);
    }
    instance.show();
};

window.showToast = function (message, type) {
    alert(message);
};

window.ajaxCall = function (route, method, data, onSuccess, onError) {
    console.warn('ajaxCall non implémenté côté API pour la route:', route);
    var response = { success: false, message: 'API non implémentée pour cette action.' };
    if (typeof onError === 'function') {
        onError(response);
    } else if (typeof onSuccess === 'function') {
        onSuccess(response);
    }
};

window.handleUpdateItem = function (route, id, modalId, formId) {
    var message = 'Mise à jour simulée pour ' + route + ' (' + id + '). À connecter à une API réelle.';
    window.showToast(message, 'info');
    if (modalId) {
        var modalEl = document.getElementById(modalId);
        if (modalEl && typeof bootstrap !== 'undefined') {
            var instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
            }
        }
    }
};

window.handleCreateItem = function (route, modalId, formId) {
    var message = 'Création simulée pour ' + route + '. À connecter à une API réelle.';
    window.showToast(message, 'info');
    if (modalId) {
        var modalEl = document.getElementById(modalId);
        if (modalEl && typeof bootstrap !== 'undefined') {
            var instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
            }
        }
    }
};

window.handleDeleteItem = function (route, id, label) {
    var ok = confirm('Supprimer ' + (label || 'cet élément') + ' ?');
    if (!ok) {
        return;
    }
    var message = 'Suppression simulée pour ' + route + ' (' + id + '). À connecter à une API réelle.';
    window.showToast(message, 'info');
};