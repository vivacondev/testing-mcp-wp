/* Claude for WP — widget.js */
(function () {
    'use strict';

    const ACTION_LABELS = {
        get_posts:          '📄 Consultando posts',
        create_post:        '✏️ Creando post',
        update_post:        '💾 Actualizando post',
        delete_post:        '🗑️ Eliminando post',
        get_terms:          '🏷️ Consultando términos',
        create_term:        '🏷️ Creando término',
        get_site_info:      '🌐 Leyendo info del sitio',
        update_site_option: '⚙️ Actualizando opción',
        get_users:          '👥 Consultando usuarios',
        get_plugins:        '🔌 Consultando plugins',
        get_media:          '🖼️ Consultando medios',
        create_elementor_page: '🎨 Creando página Elementor',
    };

    const STORAGE_KEY = 'cfw_widget_history';

    // ── Elements ──────────────────────────────────────────────
    const widget   = document.getElementById('cfw-widget');
    const toggle   = document.getElementById('cfw-widget-toggle');
    const panel    = document.getElementById('cfw-widget-panel');
    const messages = document.getElementById('cfw-widget-messages');
    const input    = document.getElementById('cfw-widget-input');
    const send     = document.getElementById('cfw-widget-send');
    const clear    = document.getElementById('cfw-widget-clear');
    const closeBtn = document.getElementById('cfw-widget-close');
    const iconOpen = widget?.querySelector('.cfw-widget__icon-open');
    const iconClose= widget?.querySelector('.cfw-widget__icon-close');

    if (!widget) return;

    // ── State ─────────────────────────────────────────────────
    // History persists across page navigations via sessionStorage
    let history = loadHistory();
    let isOpen  = false;

    // Restore messages from sessionStorage on page load
    restoreMessages();

    // ── Toggle ────────────────────────────────────────────────
    function openPanel() {
        isOpen = true;
        panel.hidden = false;
        widget.classList.add('cfw-widget--open');
        iconOpen.hidden = true;
        iconClose.hidden = false;
        scrollToBottom();
        input.focus();
    }

    function closePanel() {
        isOpen = false;
        panel.hidden = true;
        widget.classList.remove('cfw-widget--open');
        iconOpen.hidden = false;
        iconClose.hidden = true;
    }

    toggle.addEventListener('click', () => isOpen ? closePanel() : openPanel());
    closeBtn.addEventListener('click', closePanel);

    // ── Clear ─────────────────────────────────────────────────
    clear.addEventListener('click', () => {
        history = [];
        saveHistory();
        messages.innerHTML = `
            <div class="cfw-widget__msg cfw-widget__msg--assistant">
                <span class="cfw-widget__avatar">✦</span>
                <div class="cfw-widget__bubble">Conversación reiniciada. ¿En qué puedo ayudarte?</div>
            </div>`;
    });

    // ── Send ──────────────────────────────────────────────────
    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        autoResize();

        appendMessage(text, 'user');
        saveToHistory('user', text);

        send.disabled = true;
        send.querySelector('.cfw-widget__send-icon').hidden = true;
        send.querySelector('.cfw-widget__send-loader').hidden = false;

        // Typing indicator
        const typing = appendTyping();

        try {
            const res = await fetch(CFW.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action:  'cfw_chat',
                    nonce:   CFW.nonce,
                    message: text,
                    history: JSON.stringify(history.slice(-20)),
                }),
            }).then(r => r.json());

            typing.remove();

            if (res.success) {
                if (res.data.actions?.length) appendActions(res.data.actions);
                appendMessage(res.data.text, 'assistant');
                saveToHistory('assistant', res.data.text);
            } else {
                appendMessage('⚠️ ' + res.data, 'assistant');
            }
        } catch (e) {
            typing.remove();
            appendMessage('⚠️ Error de conexión.', 'assistant');
        }

        send.disabled = false;
        send.querySelector('.cfw-widget__send-icon').hidden = false;
        send.querySelector('.cfw-widget__send-loader').hidden = true;
        input.focus();
    }

    send.addEventListener('click', sendMessage);
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize textarea
    input.addEventListener('input', autoResize);
    function autoResize() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
    }

    // ── DOM helpers ───────────────────────────────────────────
    function appendMessage(text, role) {
        const wrap = document.createElement('div');
        wrap.className = `cfw-widget__msg cfw-widget__msg--${role}`;

        const avatar = document.createElement('span');
        avatar.className = 'cfw-widget__avatar';
        avatar.textContent = role === 'user' ? '👤' : '✦';

        const bubble = document.createElement('div');
        bubble.className = 'cfw-widget__bubble';
        bubble.innerHTML = role === 'assistant' ? renderMarkdown(text) : escapeHtml(text);

        wrap.appendChild(avatar);
        wrap.appendChild(bubble);
        messages.appendChild(wrap);
        scrollToBottom();
        return wrap;
    }

    function appendTyping() {
        const wrap = document.createElement('div');
        wrap.className = 'cfw-widget__msg cfw-widget__msg--assistant cfw-widget__typing';
        wrap.innerHTML = `
            <span class="cfw-widget__avatar">✦</span>
            <div class="cfw-widget__bubble">
                <span class="cfw-widget__dot"></span>
                <span class="cfw-widget__dot"></span>
                <span class="cfw-widget__dot"></span>
            </div>`;
        messages.appendChild(wrap);
        scrollToBottom();
        return wrap;
    }

    function appendActions(actions) {
        const wrap = document.createElement('div');
        wrap.className = 'cfw-widget__actions';
        actions.forEach(a => {
            const pill = document.createElement('span');
            const ok   = !a.result?.error;
            pill.className = 'cfw-widget__action-pill' + (ok ? '' : ' cfw-widget__action-pill--error');
            pill.textContent = (ok ? '✓ ' : '✗ ') + (ACTION_LABELS[a.tool] || a.tool).replace(/^\S+\s/, '');
            wrap.appendChild(pill);
        });
        messages.appendChild(wrap);
        scrollToBottom();
    }

    function scrollToBottom() {
        requestAnimationFrame(() => {
            messages.scrollTop = messages.scrollHeight;
        });
    }

    // ── Session history ───────────────────────────────────────
    function saveToHistory(role, content) {
        history.push({ role, content });
        if (history.length > 40) history = history.slice(-40);
        saveHistory();
    }

    function saveHistory() {
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history)); } catch(e) {}
    }

    function loadHistory() {
        try { return JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]'); } catch(e) { return []; }
    }

    function restoreMessages() {
        if (!history.length) return;
        // Only restore last 10 visible messages to avoid clutter
        const visible = history.slice(-10);
        visible.forEach(entry => {
            if (entry.role === 'user' || entry.role === 'assistant') {
                appendMessage(entry.content, entry.role);
            }
        });
    }

    // ── Utils ─────────────────────────────────────────────────
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function renderMarkdown(text) {
        return escapeHtml(text)
            .replace(/```[\w]*\n?([\s\S]*?)```/g, '<pre><code>$1</code></pre>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

})();
