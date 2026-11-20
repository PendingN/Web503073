// Opens and closes the AI caterpillar chat (includes/ai-worm.php). The questions themselves are handled by ask.js.
(function () {
    'use strict';

    var root = document.querySelector('[data-ai-worm]');
    if (!root) {
        return;
    }
    var toggle = root.querySelector('[data-ai-worm-toggle]');
    var panel = root.querySelector('#ai-worm-panel');
    var close = root.querySelector('[data-ai-worm-close]');
    var field = panel.querySelector('textarea');

    function setOpen(open) {
        panel.hidden = !open;
        root.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        if (open) {
            field.focus();
        } else {
            toggle.focus();
        }
    }

    // With JavaScript the caterpillar opens the chat in place instead of following its link to ask.php.
    toggle.addEventListener('click', function (event) {
        event.preventDefault();
        setOpen(panel.hidden);
    });
    close.addEventListener('click', function () {
        setOpen(false);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
        }
    });

    // The textarea grows with the question, up to a few lines.
    field.addEventListener('input', function () {
        field.style.height = 'auto';
        field.style.height = Math.min(field.scrollHeight, 120) + 'px';
    });

    // Mirror the "thinking" state onto the caterpillar so it wiggles faster while the answer is being written.
    new MutationObserver(function () {
        root.classList.toggle('is-thinking', panel.classList.contains('is-thinking'));
    }).observe(panel, { attributes: true, attributeFilter: ['class'] });
}());
