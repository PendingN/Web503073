// Live as-you-type suggestions for a search box, used by the plant collection and the blog.
// Markup: <div data-search-suggest='[{"name","category","image","url","extra"?}]' data-empty-text="…">
// wrapping an input[role=combobox] and a ul[role=listbox]. Matching ignores case and Vietnamese accents.
(function () {
    'use strict';

    var MAX_RESULTS = 6;

    // Lowercase and strip Vietnamese diacritics so "cay luoi ho" matches "Cây Lưỡi Hổ".
    function fold(text) {
        return String(text).normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase().trim();
    }

    // Lower score ranks first: name prefix, word prefix, name contains, category match, extra text match.
    function score(item, query) {
        if (item.foldedName.indexOf(query) === 0) return 0;
        if (item.foldedShort.indexOf(query) === 0) return 0;
        if ((' ' + item.foldedName).indexOf(' ' + query) !== -1) return 1;
        if (item.foldedName.indexOf(query) !== -1) return 2;
        if (item.foldedCategory.indexOf(query) !== -1) return 3;
        if (item.foldedExtra.indexOf(query) !== -1) return 4;
        return -1;
    }

    function setup(wrapper) {
        var input = wrapper.querySelector('input[role="combobox"]');
        var list = wrapper.querySelector('[role="listbox"]');
        if (!input || !list) return;

        var items;
        try {
            items = JSON.parse(wrapper.getAttribute('data-search-suggest') || '[]');
        } catch (error) {
            return;
        }
        items.forEach(function (item) {
            item.foldedName = fold(item.name);
            item.foldedShort = item.foldedName.replace(/^cay\s+/, '');
            item.foldedCategory = fold(item.category || '');
            item.foldedExtra = fold(item.extra || '');
        });
        var emptyText = wrapper.getAttribute('data-empty-text') || 'Không có kết quả khớp. Nhấn Enter để tìm đầy đủ.';

        var matches = [];
        var active = -1;

        function close() {
            list.hidden = true;
            list.innerHTML = '';
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            matches = [];
            active = -1;
        }

        function highlight(index) {
            var options = list.querySelectorAll('[role="option"]');
            options.forEach(function (option, i) {
                option.setAttribute('aria-selected', i === index ? 'true' : 'false');
            });
            active = index;
            if (index >= 0 && options[index]) {
                input.setAttribute('aria-activedescendant', options[index].id);
                options[index].scrollIntoView({ block: 'nearest' });
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function render() {
            var query = fold(input.value);
            if (query === '') {
                close();
                return;
            }
            matches = items
                .map(function (item) { return { item: item, rank: score(item, query) }; })
                .filter(function (entry) { return entry.rank >= 0; })
                .sort(function (a, b) { return a.rank - b.rank || a.item.name.localeCompare(b.item.name, 'vi'); })
                .slice(0, MAX_RESULTS)
                .map(function (entry) { return entry.item; });

            list.innerHTML = '';
            if (matches.length === 0) {
                var empty = document.createElement('li');
                empty.className = 'search-suggest-empty';
                empty.textContent = emptyText;
                list.appendChild(empty);
            }
            matches.forEach(function (item, index) {
                var option = document.createElement('li');
                option.id = list.id + '-option-' + index;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');

                var image = document.createElement('img');
                image.src = item.image;
                image.alt = '';
                image.width = 32;
                image.height = 32;
                image.loading = 'lazy';

                var text = document.createElement('span');
                var name = document.createElement('strong');
                name.textContent = item.name;
                var category = document.createElement('small');
                category.textContent = item.category;
                text.appendChild(name);
                text.appendChild(category);

                option.appendChild(image);
                option.appendChild(text);
                // mousedown keeps focus in the input so blur does not close the list first.
                option.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    window.location.href = item.url;
                });
                option.addEventListener('mousemove', function () {
                    if (active !== index) highlight(index);
                });
                list.appendChild(option);
            });
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            active = -1;
        }

        input.addEventListener('input', render);
        input.addEventListener('focus', function () {
            if (input.value.trim() !== '') render();
        });
        input.addEventListener('blur', close);
        input.addEventListener('keydown', function (event) {
            if (list.hidden) {
                if (event.key === 'ArrowDown' && input.value.trim() !== '') {
                    render();
                    event.preventDefault();
                }
                return;
            }
            if (event.key === 'ArrowDown' && matches.length) {
                highlight((active + 1) % matches.length);
                event.preventDefault();
            } else if (event.key === 'ArrowUp' && matches.length) {
                highlight(active <= 0 ? matches.length - 1 : active - 1);
                event.preventDefault();
            } else if (event.key === 'Enter' && active >= 0 && matches[active]) {
                event.preventDefault();
                window.location.href = matches[active].url;
            } else if (event.key === 'Escape') {
                close();
                event.preventDefault();
            }
        });
    }

    function init() {
        document.querySelectorAll('[data-search-suggest]').forEach(setup);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
