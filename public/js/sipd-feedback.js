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

(function (window, document) {
    'use strict';

    function clauseModeOf(value) {
        return value === 'no_presento' || value === 'extemporaneo' ? value : 'omit';
    }

    function ensureOptionalRadioGroup(box) {
        var hidden = box.querySelector('.js-optional-value');
        var group = box.getAttribute('data-radio-group');
        if (!group) {
            group = (hidden && hidden.name)
                ? String(hidden.name).replace(/[^A-Za-z0-9]+/g, '_')
                : ('optional_mode_' + (box.getAttribute('data-clause') || 'descargos'));
            box.setAttribute('data-radio-group', group);
        }
        box.querySelectorAll('.js-optional-mode').forEach(function (radio) {
            radio.setAttribute('type', 'radio');
            radio.setAttribute('name', group);
        });
        return group;
    }

    function applyOptionalClause(box, forced) {
        var include = box.querySelector('.js-optional-include');
        var hidden = box.querySelector('.js-optional-value');
        var modes = box.querySelectorAll('.js-optional-mode');
        var mode = 'omit';

        ensureOptionalRadioGroup(box);

        if (forced === 'omit' || (include && !include.checked && forced == null)) {
            if (include) include.checked = false;
            mode = 'omit';
        } else if (forced === 'no_presento' || forced === 'extemporaneo') {
            if (include) include.checked = true;
            mode = forced;
        } else if (include && include.checked) {
            var selected = null;
            modes.forEach(function (radio) {
                if (radio.checked) selected = radio;
            });
            mode = selected ? clauseModeOf(selected.value) : 'no_presento';
            if (mode === 'omit') mode = 'no_presento';
        }

        modes.forEach(function (radio) {
            radio.checked = mode !== 'omit' && radio.value === mode;
        });
        if (hidden) hidden.value = mode;
        box.setAttribute('data-mode', mode);
    }

    function bindOptionalClauses(root) {
        var scope = root || document;
        scope.querySelectorAll('.doc-optional-clause').forEach(function (box) {
            var hidden = box.querySelector('.js-optional-value');
            var include = box.querySelector('.js-optional-include');
            var mode = clauseModeOf((hidden && hidden.value) || box.getAttribute('data-mode') || 'omit');
            ensureOptionalRadioGroup(box);
            if (include) include.checked = mode !== 'omit';
            box.querySelectorAll('.js-optional-mode').forEach(function (radio) {
                radio.checked = radio.value === mode;
            });
            applyOptionalClause(box, mode);
            if (box.dataset.optionalBound) return;
            box.dataset.optionalBound = '1';

            if (include) {
                include.addEventListener('change', function () {
                    applyOptionalClause(box, include.checked ? null : 'omit');
                });
            }

            box.addEventListener('mousedown', function (e) {
                var t = e.target && e.target.closest ? e.target.closest('.js-optional-mode') : null;
                if (!t && e.target && e.target.closest) {
                    var wrap = e.target.closest('.doc-optional-choice');
                    t = wrap ? wrap.querySelector('.js-optional-mode') : null;
                }
                if (t) t.dataset.wasOn = t.checked ? '1' : '0';
            });
            box.querySelectorAll('.js-optional-mode').forEach(function (radio) {
                radio.addEventListener('click', function (e) {
                    if (radio.dataset.wasOn === '1') {
                        e.preventDefault();
                        radio.checked = false;
                        applyOptionalClause(box, 'omit');
                        return;
                    }
                    applyOptionalClause(box, clauseModeOf(radio.value));
                });
                radio.addEventListener('change', function () {
                    if (radio.checked) applyOptionalClause(box, clauseModeOf(radio.value));
                });
            });
        });
    }

    window.initSipdOptionalClauses = bindOptionalClauses;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bindOptionalClauses(document);
        });
    } else {
        bindOptionalClauses(document);
    }
})(window, document);
