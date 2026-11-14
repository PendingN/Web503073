(function () {
    'use strict';

    var scene = document.querySelector('.room-scene');
    var spots = Array.from(document.querySelectorAll('.room-spot'));
    var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var pointer = window.matchMedia('(min-width: 701px) and (hover: hover) and (pointer: fine)');
    var frame;

    function restRoom() {
        window.cancelAnimationFrame(frame);
        scene.style.setProperty('--room-x', '0px');
        scene.style.setProperty('--room-y', '0px');
    }

    if (scene) {
        spots.forEach(function (spot) {
            spot.addEventListener('toggle', function () {
                if (!spot.open) return;
                spots.forEach(function (other) { if (other !== spot) other.open = false; });
                restRoom();
            });
        });
        scene.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            var openSpot = spots.find(function (spot) { return spot.open; });
            if (openSpot) {
                openSpot.open = false;
                openSpot.querySelector('summary').focus({ preventScroll: true });
            }
        });
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.room-spot')) spots.forEach(function (spot) { spot.open = false; });
        });
        scene.addEventListener('pointermove', function (event) {
            if (motion.matches || !pointer.matches || spots.some(function (spot) { return spot.open; })) return;
            var bounds = scene.getBoundingClientRect();
            window.cancelAnimationFrame(frame);
            frame = window.requestAnimationFrame(function () {
                scene.style.setProperty('--room-x', ((event.clientX - bounds.left) / bounds.width - .5) * -10 + 'px');
                scene.style.setProperty('--room-y', ((event.clientY - bounds.top) / bounds.height - .5) * -6 + 'px');
            });
        });
        scene.addEventListener('pointerleave', restRoom);
        motion.addEventListener('change', restRoom);
        pointer.addEventListener('change', restRoom);
    }

    var finder = document.querySelector('[data-plant-finder]');
    if (finder) {
        var plants = Array.from(document.querySelectorAll('[data-plant-picks] > article'));
        var status = document.querySelector('[data-finder-status]');
        var empty = document.querySelector('[data-finder-empty]');
        var filterPlants = function () {
            var light = finder.elements.light.value;
            var position = finder.elements.position.value;
            var count = 0;
            plants.forEach(function (plant) {
                var matches = (light === 'all' || plant.dataset.light.split(' ').includes(light))
                    && (position === 'all' || plant.dataset.position.split(' ').includes(position));
                plant.hidden = !matches;
                if (matches) count++;
            });
            empty.hidden = count > 0;
            status.textContent = count + ' gợi ý cho góc nhà của bạn. Mở từng cây để tìm hiểu thêm.';
        };
        // Changing a select previews matches here; submitting opens the full advisor.
        finder.elements.light.addEventListener('change', filterPlants);
        finder.elements.position.addEventListener('change', filterPlants);
    }
})();
