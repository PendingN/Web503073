// AI questions about the user's notes. Used by ask.php and by the AI caterpillar chat on every page
// (includes/ai-worm.php). Each [data-ask-root] works on its own; without JavaScript the form posts normally.
(function () {
    'use strict';

    if (!window.fetch) {
        return;
    }

    function element(tag, className, text) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }
        return node;
    }

    function buildTurn(question, data) {
        var turn = element('article', 'ask-turn');
        turn.appendChild(element('p', 'ask-question', question));
        var answer = element('div', 'ask-answer');
        data.answer.split('\n').forEach(function (line, index) {
            if (index > 0) {
                answer.appendChild(document.createElement('br'));
            }
            answer.appendChild(document.createTextNode(line));
        });
        turn.appendChild(answer);
        // Links to the notes, posts, plants or site pages the answer used, shown as one small line.
        if (data.sources.length) {
            var notes = element('p', 'ask-notes');
            data.sources.forEach(function (source) {
                var icons = { note: '📝', blog: '📰', plant: '🌿', guide: '🧭' };
                var link = element('a', '', (icons[source.kind] || '📝') + ' ' + source.title);
                link.href = source.url;
                notes.appendChild(link);
            });
            turn.appendChild(notes);
        }
        return turn;
    }

    function initAsk(root) {
        var form = root.querySelector('[data-ask-form]');
        var field = form && form.querySelector('textarea');
        var button = form && form.querySelector('[data-ask-button]');
        var status = root.querySelector('[data-ask-status]');
        var thread = root.querySelector('[data-ask-thread]');
        // The page shows the newest answer first; the chat panel reads top to bottom like a conversation.
        var chatOrder = root.dataset.askOrder === 'chat';
        if (!form || !field || !thread) {
            return;
        }

        root.querySelectorAll('[data-ask-example]').forEach(function (example) {
            example.addEventListener('click', function () {
                field.value = example.textContent;
                field.focus();
            });
        });

        field.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var question = field.value.trim();
            if (question.length < 2) {
                status.textContent = 'Câu hỏi cần từ 2 ký tự.';
                return;
            }
            button.disabled = true;
            root.classList.add('is-thinking');
            form.setAttribute('aria-busy', 'true');
            status.textContent = 'Sâu đang đọc sổ tay và soạn câu trả lời…';
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (data) {
                    if (!response.ok || typeof data.answer !== 'string') {
                        throw new Error(data.message || 'Trợ lý AI chưa trả lời được. Vui lòng thử lại.');
                    }
                    return data;
                });
            }).then(function (data) {
                var turn = buildTurn(question, data);
                if (chatOrder) {
                    thread.appendChild(turn);
                    thread.scrollTop = thread.scrollHeight;
                } else {
                    thread.insertBefore(turn, thread.firstChild);
                }
                root.classList.add('has-turns');
                field.value = '';
                status.textContent = '';
            }).catch(function (error) {
                status.textContent = error instanceof TypeError ? 'Không kết nối được máy chủ. Vui lòng thử lại.' : error.message;
            }).finally(function () {
                button.disabled = false;
                root.classList.remove('is-thinking');
                form.removeAttribute('aria-busy');
            });
        });
    }

    document.querySelectorAll('[data-ask-root]').forEach(initAsk);
}());
