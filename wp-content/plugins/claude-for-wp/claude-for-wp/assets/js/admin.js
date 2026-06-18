/* Claude for WP — admin.js */
(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    function post(action, data) {
        return fetch(CFW.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action, nonce: CFW.nonce, ...data }),
        }).then(r => r.json());
    }

    function setLoading(btn, loading) {
        btn.disabled = loading;
        btn.querySelector('.cfw-btn-text').hidden = loading;
        const loader = btn.querySelector('.cfw-btn-loader');
        if (loader) loader.hidden = !loading;
    }

    function showNotice(parent, msg, type = 'success') {
        const el = parent.querySelector('.cfw-notice') || document.createElement('div');
        el.className = `cfw-notice cfw-notice--${type}`;
        el.textContent = msg;
        parent.appendChild(el);
        setTimeout(() => el.remove(), 5000);
    }

    // Basic markdown-ish → HTML for chat bubbles
    function renderMarkdown(text) {
        return text
            .replace(/```(\w+)?\n?([\s\S]*?)```/g, '<pre><code>$2</code></pre>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
    }

    // -------------------------------------------------------------------------
    // Chat
    // -------------------------------------------------------------------------

    const chatWindow = document.getElementById('cfw-chat-window');
    const chatInput  = document.getElementById('cfw-chat-input');
    const chatSend   = document.getElementById('cfw-chat-send');

    if (chatSend) {
        function appendMessage(text, role) {
            const wrap = document.createElement('div');
            wrap.className = `cfw-message cfw-message--${role}`;

            const avatar = document.createElement('span');
            avatar.className = 'cfw-avatar';
            avatar.textContent = role === 'user' ? '👤' : '✦';

            const bubble = document.createElement('div');
            bubble.className = 'cfw-bubble';
            if (role === 'assistant') {
                bubble.innerHTML = renderMarkdown(text);
            } else {
                bubble.textContent = text;
            }

            wrap.appendChild(avatar);
            wrap.appendChild(bubble);
            chatWindow.appendChild(wrap);
            chatWindow.scrollTop = chatWindow.scrollHeight;
            return bubble;
        }

        async function sendChat() {
            const msg = chatInput.value.trim();
            if (!msg) return;

            chatInput.value = '';
            appendMessage(msg, 'user');
            setLoading(chatSend, true);

            // Typing indicator
            const typingWrap = document.createElement('div');
            typingWrap.className = 'cfw-message cfw-message--assistant cfw-typing';
            typingWrap.innerHTML = '<span class="cfw-avatar">✦</span><div class="cfw-bubble">Escribiendo</div>';
            chatWindow.appendChild(typingWrap);
            chatWindow.scrollTop = chatWindow.scrollHeight;

            const res = await post('cfw_chat', { message: msg });
            typingWrap.remove();
            setLoading(chatSend, false);

            if (res.success) {
                appendMessage(res.data.text, 'assistant');
            } else {
                appendMessage('⚠️ Error: ' + res.data, 'assistant');
            }
        }

        chatSend.addEventListener('click', sendChat);
        chatInput.addEventListener('keydown', e => {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) sendChat();
        });
    }

    // -------------------------------------------------------------------------
    // Content generator
    // -------------------------------------------------------------------------

    const contentGenBtn = document.getElementById('cfw-content-generate');
    const contentOutput = document.getElementById('cfw-content-output');
    const contentResult = document.getElementById('cfw-content-result');

    if (contentGenBtn) {
        contentGenBtn.addEventListener('click', async () => {
            const prompt = document.getElementById('cfw-content-prompt').value.trim();
            if (!prompt) return;

            setLoading(contentGenBtn, true);

            const res = await post('cfw_content', {
                mode:   document.getElementById('cfw-content-mode').value,
                prompt,
                tone:   document.getElementById('cfw-content-tone').value,
            });

            setLoading(contentGenBtn, false);

            if (res.success) {
                contentOutput.value = res.data.text;
                contentResult.hidden = false;
                contentResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                showNotice(document.querySelector('.cfw-card'), res.data, 'error');
            }
        });

        // Copy
        document.getElementById('cfw-content-copy')?.addEventListener('click', () => {
            navigator.clipboard.writeText(contentOutput.value);
            showNotice(contentResult, 'Copiado al portapapeles.');
        });

        // Create post
        document.getElementById('cfw-content-new-post')?.addEventListener('click', async () => {
            const content = contentOutput.value.trim();
            if (!content) return;

            const title = prompt('Título del post:', 'Nuevo post generado por Claude') || 'Nuevo post generado por Claude';

            const res = await post('cfw_create_post', { content, title });
            if (res.success) {
                const link = `<a href="${res.data.edit_url}" target="_blank">Ver borrador →</a>`;
                const notice = document.createElement('div');
                notice.className = 'cfw-notice cfw-notice--success';
                notice.innerHTML = `Post creado como borrador. ${link}`;
                contentResult.appendChild(notice);
            } else {
                showNotice(contentResult, res.data, 'error');
            }
        });
    }

    // -------------------------------------------------------------------------
    // Elementor block generator
    // -------------------------------------------------------------------------

    const elGenBtn  = document.getElementById('cfw-el-generate');
    const elOutput  = document.getElementById('cfw-el-output');
    const elResult  = document.getElementById('cfw-el-result');
    const elPreview = document.getElementById('cfw-el-preview');

    if (elGenBtn) {
        elGenBtn.addEventListener('click', async () => {
            const desc = document.getElementById('cfw-el-desc').value.trim();

            setLoading(elGenBtn, true);

            const res = await post('cfw_elementor', {
                type:   document.getElementById('cfw-el-type').value,
                desc,
                colors: document.getElementById('cfw-el-colors').value,
                style:  document.getElementById('cfw-el-style').value,
            });

            setLoading(elGenBtn, false);

            if (res.success) {
                elOutput.value = res.data.text;
                elResult.hidden = false;
                elResult.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                updatePreview(res.data.text);
            } else {
                showNotice(document.querySelector('.cfw-card'), res.data, 'error');
            }
        });

        function updatePreview(html) {
            if (!elPreview) return;
            elPreview.srcdoc = html;
        }

        elOutput.addEventListener('input', () => updatePreview(elOutput.value));

        document.getElementById('cfw-el-copy')?.addEventListener('click', () => {
            navigator.clipboard.writeText(elOutput.value);
            showNotice(elResult, 'Código copiado al portapapeles.');
        });

        // Tabs
        document.querySelectorAll('.cfw-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.cfw-tab').forEach(t => t.classList.remove('cfw-tab--active'));
                tab.classList.add('cfw-tab--active');

                const target = tab.dataset.tab;
                document.getElementById('cfw-tab-code').hidden    = target !== 'code';
                document.getElementById('cfw-tab-preview').hidden = target !== 'preview';

                if (target === 'preview') updatePreview(elOutput.value);
            });
        });
    }

})();
