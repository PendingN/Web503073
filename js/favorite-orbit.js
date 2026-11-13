// Trang Yêu thích:
// 1. Di chuột vào cây nào thì vòng xoay đưa cây đó ra giữa (thay cho thanh kéo).
// 2. Giữ vòng xoay khớp với lưới cây: bỏ tim một cây thì cây đó rời khỏi vòng.
// Phải chạy trước js/shoe-orbit.js để lưu bản gốc của khung chi tiết.
(function () {
    'use strict';
    var section = document.querySelector('[data-favorite-orbit]');
    if (!section) return;

    // Chỉ phản ứng khi chuột thật sự di chuyển, nên cây trượt qua dưới con trỏ đứng yên không làm vòng quay tiếp.
    var hoverLockUntil = 0;
    section.addEventListener('pointermove', function (event) {
        if (event.pointerType !== 'mouse' || event.buttons !== 0) return;
        var shoe = event.target.closest('[data-so-shoe]');
        if (!shoe || shoe.classList.contains('so-active') || Date.now() < hoverLockUntil) return;
        hoverLockUntil = Date.now() + 450;
        // shoe-orbit.js lắng nghe sự kiện này và xoay tới cây có data-block-id tương ứng.
        shoe.dispatchEvent(new CustomEvent('orbit:select', { bubbles: true, detail: { blockId: shoe.getAttribute('data-block-id') } }));
    });

    var loopOrbit = section.getAttribute('data-loop') !== 'false';
    var handledKeyEvents = new WeakSet();
    var boundKeyTargets = new WeakSet();

    function handleOrbitKeydown(event) {
        if (handledKeyEvents.has(event) || event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) return;
        if (document.querySelector('.favorite-orbit-modal.so-open')) return;
        var tagName = event.target && event.target.tagName ? event.target.tagName : '';
        if (tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT') return;

        var key = event.key;
        if (key !== 'ArrowLeft' && key !== 'ArrowRight' && key !== 'Home' && key !== 'End') return;

        var shoes = [].slice.call(section.querySelectorAll('[data-so-shoe]'));
        if (!shoes.length) return;
        handledKeyEvents.add(event);

        var focusedShoe = event.target && event.target.closest ? event.target.closest('[data-so-shoe]') : null;
        var currentIndex = focusedShoe ? shoes.indexOf(focusedShoe) : -1;
        if (currentIndex < 0) {
            currentIndex = shoes.findIndex(function (shoe) { return shoe.classList.contains('so-active'); });
        }
        if (currentIndex < 0) currentIndex = 0;

        var nextIndex = currentIndex;
        var canLoop = loopOrbit && shoes.length > 2;
        if (key === 'ArrowRight') {
            nextIndex = canLoop ? (currentIndex + 1) % shoes.length : Math.min(shoes.length - 1, currentIndex + 1);
        } else if (key === 'ArrowLeft') {
            nextIndex = canLoop ? (currentIndex - 1 + shoes.length) % shoes.length : Math.max(0, currentIndex - 1);
        } else if (key === 'Home') {
            nextIndex = 0;
        } else if (key === 'End') {
            nextIndex = shoes.length - 1;
        }

        event.preventDefault();
        var nextShoe = shoes[nextIndex];
        if (!nextShoe) return;
        shoes.forEach(function (shoe) {
            shoe.setAttribute('tabindex', shoe === nextShoe ? '0' : '-1');
        });
        nextShoe.dispatchEvent(new CustomEvent('orbit:select', {
            bubbles: true,
            detail: { blockId: nextShoe.getAttribute('data-block-id') }
        }));
        try {
            nextShoe.focus({ preventScroll: true });
        } catch (err) {
            nextShoe.focus();
        }
    }

    function bindKeyNav() {
        var targets = [section, section.querySelector('.so-stage')];
        targets.forEach(function (target) {
            if (!target || boundKeyTargets.has(target)) return;
            boundKeyTargets.add(target);
            target.addEventListener('keydown', handleOrbitKeydown);
        });
    }

    bindKeyNav();

    var grid = document.querySelector('[data-favorites-grid]');
    if (!grid || !window.MutationObserver) return;

    var modal = section.querySelector('[data-so-modal]');
    var modalTemplate = modal ? modal.cloneNode(true) : null;

    function gridIds() {
        return [].map.call(grid.querySelectorAll('input[name="plant_id"]'), function (input) { return input.value; });
    }

    var heading = document.querySelector('[data-favorites-heading]');

    function sync() {
        var ids = gridIds();
        if (heading) heading.hidden = ids.length === 0;
        var gone = [].filter.call(section.querySelectorAll('[data-so-shoe]'), function (shoe) {
            return ids.indexOf(shoe.getAttribute('data-plant-id')) < 0;
        });
        if (!gone.length || !window.ShoeOrbit) return;
        window.ShoeOrbit.destroy(section);
        gone.forEach(function (shoe) { shoe.remove(); });
        if (modalTemplate && !section.querySelector('[data-so-modal]')) section.appendChild(modalTemplate.cloneNode(true));
        var ridges = section.querySelector('[data-so-ridges]');
        if (ridges) ridges.innerHTML = '';
        section.classList.remove('so-ready');
        if (section.querySelector('[data-so-shoe]')) {
            window.ShoeOrbit.init(section, { noDrop: true });
        } else {
            section.hidden = true;
        }
    }

    new MutationObserver(sync).observe(grid, { childList: true });
})();
