/* Block editor: structured content is validated again on the server. */
(() => {
    'use strict';
    const form = document.getElementById('blog-editor');
    if (!form) return;
    const area = document.getElementById('editor-blocks');
    const title = document.getElementById('editor-title');
    const tag = document.getElementById('editor-tag');
    const cover = document.getElementById('editor-cover');
    const error = document.getElementById('editor-error');
    const status = document.getElementById('draft-status');
    const recovery = document.getElementById('draft-recovery');
    const preview = document.getElementById('editor-preview');
    const key = form.dataset.draftKey;
    const existingSizes = JSON.parse(form.dataset.existingSizes || '{}');
    const files = new Map();
    const urls = new Map();
    let blocks = JSON.parse(document.getElementById('initial-blocks').textContent);
    let history = [], position = -1, pending, range, dirty = false, savedDraft, activeBlockIndex = -1;
    const labels = { paragraph: 'Đoạn văn', h2: 'Tiêu đề H2', h3: 'Tiêu đề H3', ul: 'Danh sách', ol: 'Danh sách đánh số', quote: 'Trích dẫn', image: 'Ảnh', divider: 'Đường phân cách' };
    const make = (name, text, className) => {
        const node = document.createElement(name);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    };
    function sanitize(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const walk = node => {
            if (node.nodeType === Node.TEXT_NODE) return document.createTextNode(node.textContent);
            const fragment = document.createDocumentFragment();
            if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'SVG', 'MATH'].includes(node.nodeName)) return fragment;
            const allowed = ['STRONG', 'B', 'EM', 'I', 'U', 'BR', 'A'].includes(node.nodeName);
            const result = allowed ? document.createElement(node.nodeName.toLowerCase()) : fragment;
            if (node.nodeName === 'A') {
                const href = node.getAttribute('href') || '';
                if (/^https?:\/\//i.test(href)) { result.setAttribute('href', href); result.setAttribute('rel', 'nofollow noopener'); }
            }
            node.childNodes.forEach(child => result.append(walk(child)));
            if (['DIV', 'P'].includes(node.nodeName)) result.append(document.createElement('br'));
            return result;
        };
        const container = document.createElement('div');
        doc.body.childNodes.forEach(node => container.append(walk(node)));
        return container.innerHTML.replace(/(<br>)+$/, '');
    }
    function message(text) { error.textContent = text; error.hidden = !text; }
    function read() {
        area.querySelectorAll('.editor-block').forEach((row, i) => {
            const block = blocks[i];
            if (block.type === 'image') {
                block.alt = row.querySelector('[data-alt]').value;
                block.caption = row.querySelector('[data-caption]').value;
            } else if (block.type === 'ul' || block.type === 'ol') {
                block.items = [...row.querySelectorAll('[contenteditable]')].map(node => sanitize(node.innerHTML));
            } else if (block.type !== 'divider') block.html = sanitize(row.querySelector('[contenteditable]').innerHTML);
        });
    }
    function snapshot() { return JSON.stringify({ title: title.value, tag: tag.value, blocks }); }
    function save() {
        read();
        const state = snapshot();
        if (history[position] !== state) {
            history = history.slice(0, position + 1);
            history.push(state);
            if (history.length > 80) history.shift();
            position = history.length - 1;
        }
        dirty = true;
        if (!savedDraft) {
            try { localStorage.setItem(key, state); status.textContent = 'Đã lưu nháp trên máy'; }
            catch { status.textContent = 'Không lưu được nháp trên máy'; }
        }
        document.getElementById('undo').disabled = position <= 0;
        document.getElementById('redo').disabled = position >= history.length - 1;
        updateCount();
    }
    function flush() { clearTimeout(pending); save(); }
    function updateCount() {
        const text = blocks.map(b => b.html || (b.items || []).join('\n') || b.caption || '').join('\n');
        const doc = new DOMParser().parseFromString(text, 'text/html');
        document.getElementById('word-count').textContent = `${Array.from(doc.body.textContent.trim()).length.toLocaleString('vi-VN')} / 10.000 ký tự · ${blocks.length} khối`;
    }
    function button(text, action, label) {
        const node = make('button', text);
        node.type = 'button';
        if (label) node.setAttribute('aria-label', label);
        node.addEventListener('click', action);
        return node;
    }
    function editable(html, label) {
        const node = make('div', undefined, 'block-text');
        node.contentEditable = 'true';
        node.setAttribute('role', 'textbox');
        node.setAttribute('aria-multiline', 'true');
        node.setAttribute('aria-label', label);
        node.dataset.placeholder = 'Viết câu chuyện của bạn…';
        node.innerHTML = sanitize(html || '');
        node.addEventListener('paste', event => {
            event.preventDefault();
            const selection = window.getSelection();
            if (!selection.rangeCount) return;
            const selected = selection.getRangeAt(0);
            selected.deleteContents();
            const text = document.createTextNode(event.clipboardData.getData('text/plain'));
            selected.insertNode(text); selected.setStartAfter(text); selected.collapse(true);
            selection.removeAllRanges(); selection.addRange(selected);
            flush();
        });
        return node;
    }
    function source(src) {
        if (files.has(src)) return urls.get(src);
        return /^images\/blog\/[a-f0-9]{32}\.(jpg|png|webp)$/.test(src || '') ? form.dataset.base + src : '';
    }
    function render(focusIndex, focusMoveDirection) {
        area.replaceChildren(); range = null;
        blocks.forEach((block, index) => {
            const row = make('section', undefined, 'editor-block');
            const tools = make('div', undefined, 'block-controls');
            tools.append(make('span', `${index + 1}. ${labels[block.type] || 'Khối'}`));
            const up = button('Lên', () => move(index, -1), `Di chuyển khối ${index + 1} lên`);
            up.dataset.move = 'up';
            const down = button('Xuống', () => move(index, 1), `Di chuyển khối ${index + 1} xuống`);
            down.dataset.move = 'down';
            up.disabled = index === 0; down.disabled = index === blocks.length - 1;
            tools.append(up, down, button('Xóa', () => {
                read(); blocks.splice(index, 1);
                const nextFocus = Math.min(index, blocks.length - 1);
                activeBlockIndex = blocks.length ? nextFocus : -1;
                render(nextFocus); flush();
            }, `Xóa khối ${index + 1}`));
            row.append(tools);
            if (block.type === 'image') {
                const src = source(block.src);
                if (src) { const img = make('img'); img.src = src; img.alt = block.alt || ''; row.append(img); }
                else row.append(make('p', 'Ảnh chưa được tải lên. Chọn lại ảnh để tiếp tục.', 'editor-notice'));
                const replacement = make('input'); replacement.type = 'file'; replacement.accept = 'image/jpeg,image/png,image/webp'; replacement.setAttribute('aria-label', 'Thay ảnh');
                replacement.addEventListener('change', () => { if (replacement.files[0]) addFiles([...replacement.files], index); });
                row.append(replacement);
                for (const [field, label] of [['alt', 'Mô tả ảnh'], ['caption', 'Chú thích ảnh']]) {
                    const input = make('input'); input.value = block[field] || ''; input.maxLength = 300; input.setAttribute(`data-${field}`, ''); input.setAttribute('aria-label', label); input.placeholder = label; row.append(input);
                }
            } else if (block.type === 'divider') row.append(make('hr'));
            else if (block.type === 'ul' || block.type === 'ol') {
                const list = make(block.type);
                (block.items || ['']).forEach((item, itemIndex) => {
                    const li = make('li'); li.append(editable(item, `Mục ${itemIndex + 1} của khối ${index + 1}`));
                    li.append(button('Bỏ mục', () => { read(); blocks[index].items.splice(itemIndex, 1); render(index); flush(); })); list.append(li);
                });
                row.append(list, button('Thêm mục', () => { read(); blocks[index].items.push(''); render(index); flush(); }));
            } else { const text = editable(block.html, `${labels[block.type]} ${index + 1}`); text.classList.add(`block-${block.type}`); row.append(text); }
            area.append(row);
        });
        if (focusIndex !== undefined && focusIndex >= 0) {
            const row = area.children[focusIndex];
            if (row) {
                let target = null;
                if (focusMoveDirection === 'up' || focusMoveDirection === 'down') {
                    const preferred = row.querySelector(`[data-move="${focusMoveDirection}"]`);
                    target = preferred && !preferred.disabled
                        ? preferred
                        : row.querySelector('.block-controls button:not(:disabled)');
                } else {
                    target = row.querySelector('[contenteditable], input') || row.querySelector('button:not(:disabled)');
                }
                target?.focus();
            }
        }
        updateCount();
    }
    function move(i, direction) {
        read();
        [blocks[i], blocks[i + direction]] = [blocks[i + direction], blocks[i]];
        const nextIndex = i + direction;
        activeBlockIndex = nextIndex;
        render(nextIndex, direction < 0 ? 'up' : 'down');
        flush();
    }
    function trackActiveBlock(event) {
        const blockEl = event.target.closest('.editor-block');
        if (blockEl && blockEl.parentElement === area) {
            activeBlockIndex = [...area.children].indexOf(blockEl);
        }
    }
    area.addEventListener('focusin', trackActiveBlock);
    area.addEventListener('click', trackActiveBlock);
    function validateFiles(selected, replaceIndex) {
        const count = blocks.filter(b => b.type === 'image').length + (cover.files.length || form.dataset.cover ? 1 : 0) + selected.length - (replaceIndex !== undefined ? 1 : 0);
        if (count > 10) throw new Error('Tối đa 10 ảnh, tính cả ảnh bìa.');
        const active = blocks.filter((b, i) => b.type === 'image' && i !== replaceIndex).map(b => files.get(b.src)).filter(Boolean);
        const all = [...active, ...selected, ...cover.files];
        if (all.some(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024)) throw new Error('Chọn ảnh JPG, PNG, WebP tối đa 2 MB/ảnh.');
        const retained = new Set(blocks.filter((b, i) => b.type === 'image' && i !== replaceIndex && !files.has(b.src)).map(b => b.src));
        if (!cover.files.length && form.dataset.cover) retained.add(form.dataset.cover);
        const retainedBytes = [...retained].reduce((sum, src) => sum + (existingSizes[src] || 0), 0);
        if (retainedBytes + all.reduce((sum, file) => sum + file.size, 0) > 6 * 1024 * 1024) throw new Error('Tổng dung lượng ảnh của bài viết tối đa 6 MB.');
    }
    function addFiles(selected, replaceIndex) {
        read();
        try {
            validateFiles(selected, replaceIndex);
            selected.forEach(file => {
                const id = 'upload_' + crypto.randomUUID().replaceAll('-', '');
                files.set(id, file); urls.set(id, URL.createObjectURL(file));
                if (replaceIndex !== undefined) blocks[replaceIndex].src = id;
                else blocks.push({ type: 'image', src: id, alt: '', caption: '' });
            });
            render(); flush(); message('');
        } catch (exception) { message(exception.message); }
    }
    document.getElementById('add-block').onclick = () => {
        read(); if (blocks.length >= 200) return message('Tối đa 200 khối.');
        const type = document.getElementById('block-type').value;
        const newBlock = type === 'ul' || type === 'ol' ? { type, items: [''] } : { type, html: '' };
        const insertAt = activeBlockIndex >= 0 && activeBlockIndex < blocks.length ? activeBlockIndex + 1 : blocks.length;
        blocks.splice(insertAt, 0, newBlock);
        activeBlockIndex = insertAt;
        render(insertAt); flush();
    };
    document.getElementById('add-images').onclick = () => document.getElementById('block-files').click();
    document.getElementById('block-files').onchange = event => { addFiles([...event.target.files]); event.target.value = ''; };
    cover.onchange = () => {
        try {
            validateFiles([]);
            const img = document.getElementById('cover-preview');
            img.src = cover.files[0] ? URL.createObjectURL(cover.files[0]) : form.dataset.cover;
            img.hidden = !img.getAttribute('src'); flush(); message('');
        } catch (exception) { cover.value = ''; message(exception.message); }
    };
    document.addEventListener('selectionchange', () => {
        const selection = window.getSelection();
        if (selection.rangeCount && area.contains(selection.anchorNode) && area.contains(selection.focusNode)) range = selection.getRangeAt(0).cloneRange();
    });
    document.querySelectorAll('[data-format]').forEach(control => {
        control.onmousedown = event => event.preventDefault();
        control.onclick = () => {
            if (!range || range.collapsed) return message('Chọn đoạn chữ cần định dạng trước.');
            const start = (range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentElement).closest('[contenteditable]');
            const end = (range.endContainer.nodeType === 1 ? range.endContainer : range.endContainer.parentElement).closest('[contenteditable]');
            if (!start || start !== end) return message('Chọn chữ trong cùng một đoạn văn.');
            const format = control.dataset.format;
            let href;
            if (format === 'a') {
                href = window.prompt('Địa chỉ liên kết (https://…)');
                if (href === null) return;
                try { const url = new URL(href); if (!['http:', 'https:'].includes(url.protocol)) throw new Error(); }
                catch { return message('Liên kết cần bắt đầu bằng http:// hoặc https://.'); }
            }
            if (format === 'clear') {
                const selection = window.getSelection(); selection.removeAllRanges(); selection.addRange(range);
                // shortcut: native removal handles split inline nodes; replace if browsers drop execCommand.
                document.execCommand('removeFormat'); document.execCommand('unlink');
                flush(); message(''); return;
            }
            const content = range.extractContents();
            const node = document.createElement(format);
            node.append(content);
            if (href) node.setAttribute('href', href);
            range.insertNode(node);
            range.selectNodeContents(node);
            const selection = window.getSelection(); selection.removeAllRanges(); selection.addRange(range);
            start.focus(); flush(); message('');
        };
    });
    form.addEventListener('input', () => { dirty = true; clearTimeout(pending); pending = setTimeout(save, 450); });
    function travel(direction) {
        clearTimeout(pending); read();
        if (snapshot() !== history[position]) save();
        const next = position + direction;
        if (next < 0 || next >= history.length) return;
        position = next; const state = JSON.parse(history[position]);
        blocks = state.blocks; title.value = state.title; tag.value = state.tag; render(); save();
    }
    document.getElementById('undo').onclick = () => travel(-1);
    document.getElementById('redo').onclick = () => travel(1);
    form.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') { event.preventDefault(); travel(event.shiftKey ? 1 : -1); }
    });
    function showPreview() {
        read(); preview.replaceChildren(make('h1', title.value || 'Bài viết chưa có tiêu đề'));
        const coverSrc = document.getElementById('cover-preview').getAttribute('src');
        if (coverSrc) { const img = make('img'); img.src = coverSrc; img.alt = title.value; preview.append(img); }
        blocks.forEach(block => {
            let node;
            if (block.type === 'image') {
                node = make('figure'); const src = source(block.src);
                if (src) { const img = make('img'); img.src = src; img.alt = block.alt; node.append(img); }
                else node.append(make('p', 'Ảnh cần được chọn lại.'));
                if (block.caption && block.caption.trim() !== '') node.append(make('figcaption', block.caption));
            } else if (block.type === 'divider') node = make('hr');
            else if (['ul', 'ol'].includes(block.type)) { node = make(block.type); block.items.forEach(item => { const li = make('li'); li.innerHTML = sanitize(item); node.append(li); }); }
            else { node = make({ paragraph: 'p', quote: 'blockquote', h2: 'h2', h3: 'h3' }[block.type] || 'p'); node.innerHTML = sanitize(block.html || ''); }
            preview.append(node);
        });
    }
    document.getElementById('preview-toggle').onclick = event => {
        const opening = preview.hidden;
        if (opening) showPreview();
        preview.hidden = !opening; form.querySelector('.editor-layout').hidden = opening;
        event.target.textContent = opening ? 'Tiếp tục viết' : 'Xem trước'; event.target.setAttribute('aria-pressed', String(opening));
    };
    try { savedDraft = localStorage.getItem(key); } catch { status.textContent = 'Không thể truy cập bộ nhớ nháp'; }
    if (savedDraft) recovery.hidden = false;
    document.getElementById('restore-draft').onclick = () => {
        try {
            const state = JSON.parse(savedDraft);
            if (typeof state.title !== 'string' || typeof state.tag !== 'string' || !Array.isArray(state.blocks) || state.blocks.length > 200 || state.blocks.some(b =>
                !b || !labels[b.type] ||
                (['paragraph', 'h2', 'h3', 'quote'].includes(b.type) && typeof b.html !== 'string') ||
                (['ul', 'ol'].includes(b.type) && (!Array.isArray(b.items) || b.items.some(item => typeof item !== 'string'))) ||
                (b.type === 'image' && (typeof b.src !== 'string' || typeof b.alt !== 'string' || typeof b.caption !== 'string'))
            )) throw new Error();
            blocks = state.blocks; title.value = state.title; tag.value = state.tag;
            savedDraft = null; recovery.hidden = true; render(); flush();
            message('Đã khôi phục nội dung. Ảnh mới chưa đăng cần chọn lại.');
        } catch { message('Không đọc được nháp. Bạn có thể bỏ nháp để viết tiếp.'); }
    };
    document.getElementById('discard-draft').onclick = () => { savedDraft = null; try { localStorage.removeItem(key); } catch {} recovery.hidden = true; status.textContent = 'Đã bỏ nháp cũ'; };
    window.addEventListener('beforeunload', event => { if (dirty) { clearTimeout(pending); save(); event.preventDefault(); event.returnValue = ''; } });
    form.onsubmit = async event => {
        event.preventDefault(); flush(); message('');
        try {
            validateFiles([]);
            if (blocks.some(b => b.type === 'image' && !source(b.src))) throw new Error('Vui lòng chọn lại các ảnh chưa được tải lên.');
            document.getElementById('blocks-data').value = JSON.stringify({ version: 1, blocks });
            const data = new FormData(form);
            blocks.filter(b => b.type === 'image' && files.has(b.src)).forEach(b => data.append(`block_images[${b.src}]`, files.get(b.src)));
            const submit = form.querySelector('[type=submit]'); submit.disabled = true; form.querySelector('.editor-layout').inert = true; status.textContent = 'Đang đăng bài…';
            try {
                const response = await fetch(form.action, { method: 'POST', body: data });
                const url = new URL(response.url);
                if (response.ok && /\/blog-post\.php$/.test(url.pathname)) {
                    clearTimeout(pending); dirty = false; try { localStorage.removeItem(key); } catch {}
                    location.assign(response.url); return;
                }
                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                throw new Error(doc.querySelector('.editor-notice, .status-note')?.textContent || 'Không đăng được bài. Kiểm tra phiên đăng nhập và thử lại; nội dung vẫn được giữ tại đây.');
            } finally { submit.disabled = false; form.querySelector('.editor-layout').inert = false; }
        } catch (exception) { message(exception.message); status.textContent = 'Chưa đăng bài'; }
    };
    if (matchMedia('(max-width: 760px)').matches) document.querySelector('.editor-settings details').open = false;
    render(); history = [snapshot()]; position = 0; document.getElementById('undo').disabled = true; document.getElementById('redo').disabled = true;
})();
