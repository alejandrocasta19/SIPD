(function (window, document) {
    'use strict';

    if (typeof Swal === 'undefined') {
        return;
    }

    var GREEN = '#006837';
    var RED = '#C8102E';

    function titles(icon) {
        if (icon === 'success') return 'Listo';
        if (icon === 'error') return 'No se pudo completar';
        if (icon === 'warning') return 'Atención';
        return 'Aviso';
    }

    window.SIPD = {
        toast: function (opts) {
            opts = opts || {};
            var icon = opts.icon || 'info';
            return Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4800,
                timerProgressBar: true,
                icon: icon,
                title: opts.title || titles(icon),
                text: opts.text || ''
            });
        },
        confirm: function (opts) {
            opts = opts || {};
            return Swal.fire({
                icon: opts.icon || (opts.danger ? 'warning' : 'question'),
                title: opts.title || 'Confirmar acción',
                text: opts.text || '',
                showCancelButton: true,
                focusCancel: true,
                reverseButtons: true,
                confirmButtonText: opts.confirmText || 'Continuar',
                cancelButtonText: opts.cancelText || 'Cancelar',
                buttonsStyling: false,
                customClass: {
                    popup: 'sipd-modal',
                    title: 'sipd-modal-title',
                    htmlContainer: 'sipd-modal-text',
                    confirmButton: opts.danger ? 'sipd-swal-danger' : 'sipd-swal-confirm',
                    cancelButton: 'sipd-swal-cancel',
                    actions: 'sipd-modal-actions',
                    icon: 'sipd-modal-icon'
                }
            }).then(function (result) {
                return result.isConfirmed;
            });
        },
        alert: function (opts) {
            opts = opts || {};
            return Swal.fire({
                icon: opts.icon || 'info',
                title: opts.title || titles(opts.icon || 'info'),
                text: opts.text || '',
                confirmButtonText: opts.confirmText || 'Entendido',
                buttonsStyling: false,
                customClass: {
                    popup: 'sipd-modal',
                    title: 'sipd-modal-title',
                    htmlContainer: 'sipd-modal-text',
                    confirmButton: 'sipd-swal-confirm',
                    icon: 'sipd-modal-icon'
                }
            });
        }
    };

    function flashFromPage() {
        var flashes = window.SIPD_FLASH || [];
        flashes.forEach(function (item, index) {
            window.setTimeout(function () {
                window.SIPD.toast(item);
            }, 80 + index * 280);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', flashFromPage);
    } else {
        window.setTimeout(flashFromPage, 80);
    }

    function markConfirmed(el) {
        el.dataset.sipdOk = '1';
    }

    function isConfirmed(el) {
        return el.dataset.sipdOk === '1';
    }

    function confirmOptions(el) {
        return {
            title: el.getAttribute('data-confirm-title') || 'Confirmar acción',
            text: el.getAttribute('data-confirm') || '',
            confirmText: el.getAttribute('data-confirm-ok') || 'Continuar',
            cancelText: el.getAttribute('data-confirm-cancel') || 'Cancelar',
            icon: el.getAttribute('data-confirm-icon') || undefined,
            danger: el.getAttribute('data-confirm-danger') === '1'
        };
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.hasAttribute('data-confirm')) return;
        if (isConfirmed(form)) return;
        e.preventDefault();
        e.stopPropagation();
        window.SIPD.confirm(confirmOptions(form)).then(function (ok) {
            if (!ok) return;
            markConfirmed(form);
            HTMLFormElement.prototype.submit.call(form);
        });
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-confirm]');
        if (!btn) return;
        if (btn.tagName === 'FORM') return;
        if (isConfirmed(btn)) return;
        e.preventDefault();
        e.stopPropagation();
        window.SIPD.confirm(confirmOptions(btn)).then(function (ok) {
            if (!ok) return;
            markConfirmed(btn);
            if (btn.tagName === 'A') {
                window.location.href = btn.href;
                return;
            }
            if (btn.type === 'submit' && btn.form) {
                if (typeof btn.form.requestSubmit === 'function') {
                    btn.form.requestSubmit(btn);
                } else {
                    btn.click();
                }
                return;
            }
            btn.click();
        });
    }, true);
})(window, document);
