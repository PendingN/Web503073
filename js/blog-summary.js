// AI summary on blog-post.php: submit in the background and render the answer in place.
// Without JavaScript the form posts normally and the server redirects back with the summary.
(function () {
    'use strict';

    var box = document.querySelector('[data-ai-summary]');
    var form = box && box.querySelector('[data-ai-summary-form]');
    if (!form || !window.fetch) {
        return;
    }
    var button = form.querySelector('[data-ai-summary-button]');
    var status = box.querySelector('[data-ai-summary-status]');
    var body = box.querySelector('[data-ai-summary-body]');
    var lead = box.querySelector('[data-ai-summary-lead]');
    var list = box.querySelector('[data-ai-summary-points]');
    var time = box.querySelector('[data-ai-summary-time]');
    var empty = box.querySelector('[data-ai-summary-empty]');

    function setBusy(busy) {
        button.disabled = busy;
        box.classList.toggle('is-loading', busy);
        box.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        setBusy(true);
        status.textContent = 'Đang đọc bài và tóm tắt…';
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok || !Array.isArray(data.points)) {
                    throw new Error(data.message || 'Trợ lý AI chưa tóm tắt được bài này. Vui lòng thử lại.');
                }
                return data;
            });
        }).then(function (data) {
            lead.textContent = data.summary;
            list.replaceChildren.apply(list, data.points.map(function (point) {
                var item = document.createElement('li');
                item.textContent = point;
                return item;
            }));
            time.textContent = data.created_at;
            body.hidden = false;
            if (empty) {
                empty.remove();
                empty = null;
            }
            box.classList.add('has-summary');
            button.textContent = 'Tạo lại';
            status.textContent = 'Đã tạo tóm tắt mới.';
        }).catch(function (error) {
            status.textContent = error instanceof TypeError
                ? 'Không kết nối được máy chủ. Vui lòng thử lại.'
                : error.message;
        }).finally(function () {
            setBusy(false);
        });
    });
}());
