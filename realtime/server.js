'use strict';

// Real-time co-editing for blog posts. Run: npm run realtime (default port 8091).
// PHP signs a short-lived ticket (includes/realtime.php) for owners and editors only; this server
// checks the signature, groups clients by post, and relays changes and presence between them.
// It never touches the database: each editor still saves through the normal PHP form.

const crypto = require('node:crypto');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');
const { WebSocketServer } = require('ws');

const PORT = Number(process.env.REALTIME_PORT || 8091);
const MAX_MESSAGE_BYTES = 400 * 1024;
const SECRET_FILE = path.join(__dirname, '..', '.runtime', 'realtime-secret');

// Shared with PHP. REALTIME_SECRET wins; otherwise both sides use .runtime/realtime-secret (created once).
function loadSecret() {
    if (process.env.REALTIME_SECRET) {
        return process.env.REALTIME_SECRET;
    }
    if (!fs.existsSync(SECRET_FILE)) {
        fs.mkdirSync(path.dirname(SECRET_FILE), { recursive: true });
        fs.writeFileSync(SECRET_FILE, crypto.randomBytes(32).toString('hex'), { mode: 0o644, flag: 'wx' });
    }
    return fs.readFileSync(SECRET_FILE, 'utf8').trim();
}
const secret = loadSecret();

/** Returns the ticket payload ({post, user, name, exp}) or null when the signature or expiry is wrong. */
function verifyTicket(ticket) {
    const [body, signature] = String(ticket || '').split('.');
    if (!body || !signature) {
        return null;
    }
    const expected = crypto.createHmac('sha256', secret).update(body).digest('base64url');
    const given = Buffer.from(signature);
    if (given.length !== Buffer.byteLength(expected) || !crypto.timingSafeEqual(given, Buffer.from(expected))) {
        return null;
    }
    try {
        const payload = JSON.parse(Buffer.from(body, 'base64url').toString('utf8'));
        const valid = Number.isInteger(payload.post) && Number.isInteger(payload.user)
            && typeof payload.name === 'string' && payload.exp * 1000 > Date.now();
        return valid ? payload : null;
    } catch {
        return null;
    }
}

/** Light shape check; PHP validates the content again when someone saves. */
function validState(state) {
    return state && typeof state.title === 'string' && state.title.length <= 200
        && (state.tags === undefined || (Array.isArray(state.tags) && state.tags.length <= 10 && state.tags.every(id => typeof id === 'string' && id.length <= 20)))
        && Array.isArray(state.blocks) && state.blocks.length <= 200
        && state.blocks.every(block => block && typeof block.type === 'string');
}

const rooms = new Map(); // post id -> { clients: Set<ws>, state, version }

function room(postId) {
    if (!rooms.has(postId)) {
        rooms.set(postId, { clients: new Set(), state: null, version: 0 });
    }
    return rooms.get(postId);
}

function send(socket, message) {
    if (socket.readyState === socket.OPEN) {
        socket.send(JSON.stringify(message));
    }
}

function peers(current) {
    return [...current.clients].map(client => ({ id: client.session, user: client.user.user, name: client.user.name, block: client.block }));
}

function broadcastPresence(current) {
    const list = peers(current);
    current.clients.forEach(client => send(client, { type: 'presence', peers: list, you: client.session }));
}

const server = http.createServer((request, response) => {
    response.writeHead(request.url === '/health' ? 200 : 404, { 'Content-Type': 'text/plain' });
    response.end(request.url === '/health' ? 'ok' : 'not found');
});
const wss = new WebSocketServer({ server, path: '/collab', maxPayload: MAX_MESSAGE_BYTES });

wss.on('connection', (socket, request) => {
    const ticket = new URL(request.url, 'http://localhost').searchParams.get('ticket');
    const user = verifyTicket(ticket);
    if (!user) {
        socket.close(4401, 'invalid ticket');
        return;
    }
    socket.user = user;
    socket.session = crypto.randomBytes(6).toString('hex');
    socket.block = -1;
    socket.isAlive = true;
    const current = room(user.post);
    current.clients.add(socket);
    // A newcomer gets the room's latest unsaved state, if anyone has changed something yet.
    send(socket, { type: 'welcome', session: socket.session, state: current.state, version: current.version });
    broadcastPresence(current);

    socket.on('pong', () => { socket.isAlive = true; });
    socket.on('message', raw => {
        let message;
        try {
            message = JSON.parse(raw.toString('utf8'));
        } catch {
            return;
        }
        if (message.type === 'update' && validState(message.state)) {
            current.state = message.state;
            current.version += 1;
            current.clients.forEach(client => {
                if (client !== socket) {
                    send(client, { type: 'update', state: message.state, version: current.version, by: { id: socket.session, name: user.name } });
                }
            });
        } else if (message.type === 'focus' && Number.isInteger(message.block)) {
            socket.block = message.block;
            broadcastPresence(current);
        } else if (message.type === 'saved') {
            // Someone saved through PHP: the room now matches the database.
            current.clients.forEach(client => {
                if (client !== socket) {
                    send(client, { type: 'saved', by: { id: socket.session, name: user.name } });
                }
            });
        }
    });
    socket.on('close', () => {
        current.clients.delete(socket);
        if (current.clients.size === 0) {
            rooms.delete(user.post);
        } else {
            broadcastPresence(current);
        }
    });
});

// Drop connections that stopped answering (closed laptops, lost Wi-Fi).
setInterval(() => {
    wss.clients.forEach(socket => {
        if (!socket.isAlive) {
            socket.terminate();
            return;
        }
        socket.isAlive = false;
        socket.ping();
    });
}, 30000).unref();

server.listen(PORT, () => {
    console.log(`Realtime collaboration server on port ${PORT} (ws path /collab)`);
});
