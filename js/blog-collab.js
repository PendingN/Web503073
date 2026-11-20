// Real-time co-editing on blog-editor.php for the author and people the post was shared with for editing.
// Talks to realtime/server.js over WebSocket; blog-editor.js reports local changes and applies remote ones.
(function () {
    'use strict';

    var form = document.getElementById('blog-editor');
    var bar = document.querySelector('[data-collab]');
    if (!form || !bar || !form.dataset.collabTicket || !window.WebSocket) {
        return;
    }
    var statusText = bar.querySelector('[data-collab-status]');
    var peopleList = bar.querySelector('[data-collab-people]');
    var area = document.getElementById('editor-blocks');
    var socket = null;
    var me = null;
    var peers = [];
    var retries = 0;
    var sendTimer = null;
    var pendingState = null;
    var stopped = false;

    function endpoint() {
        var base = form.dataset.collabUrl;
        if (!base) {
            // Same host as the page, port from the server config: nothing is hard-coded here.
            base = (location.protocol === 'https:' ? 'wss://' : 'ws://') + location.hostname + ':' + form.dataset.collabPort + '/collab';
        }
        return base + '?ticket=' + encodeURIComponent(form.dataset.collabTicket);
    }

    function setStatus(text, state) {
        statusText.textContent = text;
        bar.dataset.state = state;
    }

    function send(message) {
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify(message));
        }
    }

    function initials(name) {
        return name.trim().split(/\s+/).pop().charAt(0).toUpperCase() || '?';
    }

    function colorFor(id) {
        var hash = 0;
        for (var i = 0; i < id.length; i++) {
            hash = (hash * 31 + id.charCodeAt(i)) % 360;
        }
        return 'hsl(' + hash + ' 55% 38%)';
    }

    // Outline the blocks other people are in, with their name, so two people avoid typing in the same block.
    function markBlocks() {
        if (!area) {
            return;
        }
        area.querySelectorAll('.editor-block').forEach(function (row) {
            row.classList.remove('is-peer-editing');
            row.style.removeProperty('--peer-color');
            row.removeAttribute('data-peer-name');
        });
        peers.forEach(function (peer) {
            if (peer.id === me || peer.block < 0) {
                return;
            }
            var row = area.children[peer.block];
            if (row) {
                row.classList.add('is-peer-editing');
                row.style.setProperty('--peer-color', colorFor(peer.id));
                row.dataset.peerName = peer.name + ' đang sửa';
            }
        });
    }

    function renderPeople() {
        var others = peers.filter(function (peer) { return peer.id !== me; });
        peopleList.replaceChildren.apply(peopleList, others.map(function (peer) {
            var item = document.createElement('li');
            item.title = peer.name;
            item.style.setProperty('--peer-color', colorFor(peer.id));
            item.textContent = initials(peer.name);
            var label = document.createElement('span');
            label.className = 'sr-only';
            label.textContent = peer.name;
            item.appendChild(label);
            return item;
        }));
        if (socket && socket.readyState === WebSocket.OPEN) {
            setStatus(others.length ? 'Đang sửa cùng ' + others.map(function (peer) { return peer.name; }).join(', ') : 'Đã kết nối · chưa có ai khác', 'online');
        }
        markBlocks();
    }

    function connect() {
        if (stopped) {
            return;
        }
        setStatus(retries ? 'Đang kết nối lại…' : 'Đang kết nối sửa cùng…', 'connecting');
        socket = new WebSocket(endpoint());
        socket.addEventListener('open', function () {
            retries = 0;
            renderPeople();
        });
        socket.addEventListener('message', function (event) {
            var message;
            try {
                message = JSON.parse(event.data);
            } catch (error) {
                return;
            }
            if (message.type === 'welcome') {
                me = message.session;
                if (message.state) {
                    // Someone in the room has unsaved changes newer than the saved post.
                    form.dispatchEvent(new CustomEvent('blog-editor:apply', { detail: message.state }));
                }
                if (pendingState) {
                    send({ type: 'update', state: pendingState });
                    pendingState = null;
                }
            } else if (message.type === 'presence') {
                me = message.you;
                peers = message.peers;
                renderPeople();
            } else if (message.type === 'update') {
                form.dispatchEvent(new CustomEvent('blog-editor:apply', { detail: message.state }));
                setStatus(message.by.name + ' vừa sửa', 'online');
                markBlocks();
            } else if (message.type === 'saved') {
                setStatus(message.by.name + ' đã lưu bài. Nội dung trên trang đã được cập nhật.', 'online');
            }
        });
        socket.addEventListener('close', function (event) {
            if (event.code === 4401) {
                stopped = true;
                setStatus('Phiên sửa cùng đã hết hạn. Tải lại trang để tiếp tục.', 'offline');
                return;
            }
            // Back off up to 15 s; edits made meanwhile are sent when the connection returns.
            retries += 1;
            setStatus('Mất kết nối sửa cùng. Thay đổi của bạn vẫn được giữ.', 'offline');
            setTimeout(connect, Math.min(15000, 1000 * Math.pow(2, Math.min(retries, 4))));
        });
    }

    form.addEventListener('blog-editor:change', function (event) {
        pendingState = event.detail;
        clearTimeout(sendTimer);
        sendTimer = setTimeout(function () {
            if (socket && socket.readyState === WebSocket.OPEN && pendingState) {
                send({ type: 'update', state: pendingState });
                pendingState = null;
            }
        }, 120);
    });
    form.addEventListener('blog-editor:focus', function (event) {
        send({ type: 'focus', block: event.detail });
    });
    form.addEventListener('blog-editor:rendered', markBlocks);
    form.addEventListener('blog-editor:saved', function () {
        send({ type: 'saved' });
    });

    connect();
}());
