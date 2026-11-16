(function () {
    'use strict';

    // Gentle pointer tilt for the two advisor mode buttons; CSS supplies the rest.
    var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var pointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    var properties = ['--tilt-x', '--tilt-y', '--art-x', '--art-y', '--shine-x', '--shine-y'];

    document.querySelectorAll('.advisor-mode').forEach(function (card) {
        var frame;
        function reset() {
            window.cancelAnimationFrame(frame);
            properties.forEach(function (property) { card.style.removeProperty(property); });
        }
        card.addEventListener('pointermove', function (event) {
            if (motion.matches || !pointer.matches) return;
            var bounds = card.getBoundingClientRect();
            var x = (event.clientX - bounds.left) / bounds.width - 0.5;
            var y = (event.clientY - bounds.top) / bounds.height - 0.5;
            window.cancelAnimationFrame(frame);
            frame = window.requestAnimationFrame(function () {
                card.style.setProperty('--tilt-x', (x * 7).toFixed(2) + 'deg');
                card.style.setProperty('--tilt-y', (-y * 6).toFixed(2) + 'deg');
                card.style.setProperty('--art-x', (-x * 18).toFixed(1) + 'px');
                card.style.setProperty('--art-y', (-y * 12).toFixed(1) + 'px');
                card.style.setProperty('--shine-x', ((x + 0.5) * 100).toFixed(0) + '%');
                card.style.setProperty('--shine-y', ((y + 0.5) * 100).toFixed(0) + '%');
            });
        });
        card.addEventListener('pointerleave', reset);
        motion.addEventListener('change', reset);
    });
})();

(function () {
    'use strict';

    // "Lắc quẻ cây xanh": shake the tube, raise one stick, then reveal a random plant.
    var section = document.querySelector('[data-lac-que]');
    var source = document.getElementById('que-data');
    if (!section || !source) return;
    var data = JSON.parse(source.textContent);
    var keys = Object.keys(data.sticks || {});
    if (!keys.length) return;
    var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var result = section.querySelector('[data-que-result]');
    var field = function (name) { return section.querySelector('[data-que-' + name + ']'); };
    var busy = false;

    function pick(list) { return list[Math.floor(Math.random() * list.length)]; }

    function reveal(key) {
        var stick = data.sticks[key];
        var plant = pick(stick.plants);
        var tag = field('tag');
        tag.textContent = 'Quẻ ' + stick.label + ' · ' + pick(data.levels);
        tag.style.setProperty('--c', stick.color);
        field('image').src = plant.image;
        field('image').alt = plant.name;
        field('name').textContent = plant.name;
        field('verse').textContent = pick(stick.verses);
        field('meaning').textContent = plant.meaning;
        var facts = field('facts');
        facts.textContent = '';
        plant.facts.forEach(function (fact) {
            var row = document.createElement('div');
            var term = document.createElement('dt');
            var value = document.createElement('dd');
            term.textContent = fact[0];
            value.textContent = fact[1];
            row.append(term, value);
            facts.appendChild(row);
        });
        field('water').querySelector('span').textContent = plant.water;
        field('water').hidden = !plant.water;
        field('link').href = plant.url;
        result.hidden = false;
        busy = false;
    }

    function draw() {
        if (busy) return;
        busy = true;
        var key = pick(keys);
        var still = motion.matches;
        result.hidden = true;
        section.classList.remove('has-drawn', 'is-shaking');
        section.querySelectorAll('[data-que-stick]').forEach(function (stick) { stick.classList.remove('is-drawn'); });
        if (!still) {
            void section.offsetWidth;
            section.classList.add('is-shaking');
        }
        window.setTimeout(function () {
            section.classList.remove('is-shaking');
            section.classList.add('has-drawn');
            section.querySelector('[data-que-stick="' + key + '"]').classList.add('is-drawn');
            window.setTimeout(function () { reveal(key); }, still ? 0 : 650);
        }, still ? 0 : 1260);
    }

    section.querySelectorAll('[data-que-shake]').forEach(function (button) {
        button.setAttribute('role', 'button');
        button.addEventListener('click', function (event) {
            event.preventDefault();
            draw();
        });
    });
})();

(function () {
    'use strict';

    // Advisor form wizard: one step at a time with a progress bar. Without JavaScript all steps stay visible.
    var form = document.querySelector('[data-advisor-steps]');
    if (!form) return;
    var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));
    var progress = form.querySelector('[data-step-progress]');
    var progressButtons = Array.prototype.slice.call(progress.querySelectorAll('[data-step-go]'));
    var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var current = 0;
    var reached = 0;

    function controlsOf(field) {
        return field.querySelectorAll('input, select');
    }

    function isFilled(field) {
        var controls = controlsOf(field);
        if (controls[0] && controls[0].type === 'radio') {
            return !!field.querySelector('input:checked');
        }
        return Array.prototype.every.call(controls, function (control) {
            return control.value.trim() !== '' && control.checkValidity();
        });
    }

    function markField(field, invalid) {
        if (field.tagName === 'FIELDSET') {
            field.classList.toggle('is-invalid', invalid);
            return;
        }
        Array.prototype.forEach.call(controlsOf(field), function (control) {
            if (invalid) control.setAttribute('aria-invalid', 'true');
            else control.removeAttribute('aria-invalid');
        });
    }

    // Returns the first unfilled field of a step, marking every unfilled one.
    function checkStep(index, report) {
        var missing = [];
        steps[index].querySelectorAll('[data-field]').forEach(function (field) {
            var ok = isFilled(field);
            if (report) markField(field, !ok);
            if (!ok) missing.push(field);
        });
        if (report) {
            var message = steps[index].querySelector('[data-step-message]');
            if (message) {
                message.textContent = missing.length ? 'Bạn chọn nốt: ' + missing.map(function (field) { return field.dataset.field; }).join(', ') + '.' : '';
            }
        }
        return missing[0] || null;
    }

    function focusField(field) {
        var target = field.querySelector('input:checked') || field.querySelector('input, select');
        if (target) target.focus();
    }

    function render() {
        steps.forEach(function (step, index) {
            step.classList.toggle('is-current', index === current);
        });
        progressButtons.forEach(function (button, index) {
            var item = button.parentElement;
            item.classList.toggle('is-current', index === current);
            item.classList.toggle('is-done', index < current || (index <= reached && index !== current));
            button.disabled = index > reached && index !== current + 1;
            if (index === current) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
        progress.style.setProperty('--progress', String(current / (steps.length - 1)));
    }

    function show(index, userAction) {
        var back = index < current;
        current = index;
        reached = Math.max(reached, index);
        render();
        if (!userAction) return;
        var step = steps[index];
        if (!motion.matches) {
            step.classList.remove('is-entering', 'is-back');
            void step.offsetWidth;
            step.classList.add('is-entering');
            step.classList.toggle('is-back', back);
        }
        var heading = step.querySelector('h2');
        if (heading) heading.focus({ preventScroll: true });
        var top = form.getBoundingClientRect().top;
        if (top < 0 || top > window.innerHeight * 0.6) {
            form.scrollIntoView({ behavior: motion.matches ? 'auto' : 'smooth', block: 'start' });
        }
    }

    // Moving forward checks every step on the way; going back never blocks.
    function go(target) {
        for (var index = current; index < target; index++) {
            var missing = checkStep(index, true);
            if (missing) {
                if (index !== current) show(index, true);
                focusField(missing);
                return false;
            }
        }
        show(target, true);
        return true;
    }

    form.classList.add('is-stepped');
    progress.hidden = false;
    form.querySelectorAll('[data-step-nav], [data-step-back]').forEach(function (element) { element.hidden = false; });
    form.querySelectorAll('[data-step-next]').forEach(function (button) {
        button.addEventListener('click', function () { go(current + 1); });
    });
    form.querySelectorAll('[data-step-back]').forEach(function (button) {
        button.addEventListener('click', function () { show(Math.max(0, current - 1), true); });
    });
    progressButtons.forEach(function (button, index) {
        button.addEventListener('click', function () {
            if (index < current) show(index, true);
            else if (index > current) go(index);
        });
    });

    // Clear a field's error as soon as it gets a value.
    form.addEventListener('change', function (event) {
        var field = event.target.closest('[data-field]');
        if (field && isFilled(field)) markField(field, false);
        var message = steps[current].querySelector('[data-step-message]');
        if (message && message.textContent && !checkStep(current, false)) message.textContent = '';
    });

    // Capture phase runs before the shared busy-state handler in main.js, so a blocked submit
    // never leaves the form marked as submitting. Enter on an earlier step moves to the next one.
    document.addEventListener('submit', function (event) {
        if (event.target !== form) return;
        if (current < steps.length - 1) {
            event.preventDefault();
            go(current + 1);
            return;
        }
        for (var index = 0; index < steps.length; index++) {
            var missing = checkStep(index, true);
            if (missing) {
                event.preventDefault();
                if (index !== current) show(index, true);
                focusField(missing);
                return;
            }
        }
    }, true);

    // After a server-side error, open the first step that has one.
    var start = 0;
    var flagged = form.querySelector('.advisor-choice.is-invalid, [aria-invalid="true"]');
    if (flagged) {
        steps.forEach(function (step, index) {
            if (index > start && step.contains(flagged)) start = index;
        });
        reached = steps.length - 1;
    }
    show(start, false);
})();

(function () {
    'use strict';

    // Quick tags toggle short phrases in the note; the counter tracks the 200-character limit.
    var box = document.querySelector('[data-note-tags]');
    var note = document.getElementById('advisor-note');
    var count = document.querySelector('[data-note-count]');
    if (!box || !note) return;
    var limit = Number(note.getAttribute('maxlength')) || 200;
    var buttons = Array.prototype.slice.call(box.querySelectorAll('[data-note-tag]'));

    function parts() {
        return note.value.split(/[,;\n]/).map(function (part) { return part.trim(); }).filter(Boolean);
    }

    function has(tag) {
        var wanted = tag.toLocaleLowerCase('vi');
        return parts().some(function (part) { return part.toLocaleLowerCase('vi') === wanted; });
    }

    function sync() {
        buttons.forEach(function (button) {
            button.setAttribute('aria-pressed', has(button.dataset.noteTag) ? 'true' : 'false');
        });
        if (count) {
            count.textContent = note.value.length + '/' + limit + ' ký tự';
            count.classList.toggle('is-near', note.value.length > limit - 20);
        }
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var tag = button.dataset.noteTag;
            if (has(tag)) {
                var wanted = tag.toLocaleLowerCase('vi');
                note.value = parts().filter(function (part) { return part.toLocaleLowerCase('vi') !== wanted; }).join(', ');
            } else {
                var text = note.value.replace(/[\s,;]+$/, '');
                var next = text ? text + ', ' + tag : tag;
                if (next.length > limit) {
                    button.classList.remove('is-full');
                    void button.offsetWidth;
                    button.classList.add('is-full');
                    return;
                }
                note.value = next;
            }
            sync();
        });
    });
    note.addEventListener('input', sync);
    box.hidden = false;
    sync();
})();
