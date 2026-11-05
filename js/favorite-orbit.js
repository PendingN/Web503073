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
        shoe.dispatchEvent(new CustomEvent('shopify:block:select', { bubbles: true, detail: { blockId: shoe.getAttribute('data-block-id') } }));
    });

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
