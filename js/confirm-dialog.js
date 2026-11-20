/*
 * Confirmation popup for forms that carry data-confirm="message".
 * Optional attributes on the form:
 *   data-confirm-title   heading of the popup (default "Xác nhận thao tác")
 *   data-confirm-label   text of the confirm button (default "Xác nhận")
 *   data-confirm-tone    "danger" paints the confirm button red and focuses Hủy first
 * Styling lives in css/blog.css (blog pages) and css/admin-tailwind.css (admin pages).
 */
(function () {
    'use strict';

    var dialog = null;
    var titleEl = null;
    var messageEl = null;
    var cancelButton = null;
    var okButton = null;
    var onResult = null;

    function supportsDialog() {
        return typeof window.HTMLDialogElement === 'function' && typeof document.createElement('dialog').showModal === 'function';
    }

    function build() {
        dialog = document.createElement('dialog');
        dialog.className = 'confirm-dialog';
        dialog.setAttribute('aria-labelledby', 'confirm-dialog-title');
        dialog.setAttribute('aria-describedby', 'confirm-dialog-message');
        dialog.innerHTML =
            '<form method="dialog" class="confirm-dialog__panel">' +
            '<h2 class="confirm-dialog__title" id="confirm-dialog-title"></h2>' +
            '<p class="confirm-dialog__message" id="confirm-dialog-message"></p>' +
            '<div class="confirm-dialog__actions">' +
            '<button type="submit" value="cancel" class="confirm-dialog__cancel">Hủy</button>' +
            '<button type="submit" value="ok" class="confirm-dialog__ok"></button>' +
            '</div></form>';
        titleEl = dialog.querySelector('.confirm-dialog__title');
        messageEl = dialog.querySelector('.confirm-dialog__message');
        cancelButton = dialog.querySelector('.confirm-dialog__cancel');
        okButton = dialog.querySelector('.confirm-dialog__ok');

        // Clicking the dimmed area (the <dialog> itself, never its panel) cancels.
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) dialog.close('cancel');
        });
        dialog.addEventListener('close', function () {
            var callback = onResult;
            var confirmed = dialog.returnValue === 'ok';
            onResult = null;
            if (callback) callback(confirmed);
        });
        document.body.appendChild(dialog);
    }

    function ask(options, callback) {
        if (!dialog) build();
        titleEl.textContent = options.title;
        messageEl.textContent = options.message;
        okButton.textContent = options.label;
        dialog.dataset.tone = options.tone;
        dialog.returnValue = '';
        onResult = callback;
        dialog.showModal();
        // Destructive actions start on "Hủy" so Enter never deletes by accident.
        (options.tone === 'danger' ? cancelButton : okButton).focus();
    }

    function submitConfirmed(form, submitter) {
        form.dataset.confirmed = 'true';
        try {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
            } else {
                form.submit();
            }
        } catch (error) {
            form.submit();
        }
    }

    // Capture phase on document: runs before any form-level submit handler, so the
    // double-submit guards see defaultPrevented and stay out of the way until we resubmit.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.hasAttribute || !form.hasAttribute('data-confirm')) return;

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        var message = form.getAttribute('data-confirm') || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
        if (!supportsDialog()) {
            if (!window.confirm(message)) event.preventDefault();
            return;
        }

        event.preventDefault();
        var submitter = event.submitter || null;
        ask({
            title: form.getAttribute('data-confirm-title') || 'Xác nhận thao tác',
            message: message,
            label: form.getAttribute('data-confirm-label') || 'Xác nhận',
            tone: form.getAttribute('data-confirm-tone') === 'danger' ? 'danger' : 'default'
        }, function (confirmed) {
            if (confirmed) submitConfirmed(form, submitter);
        });
    }, true);
}());
