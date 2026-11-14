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
