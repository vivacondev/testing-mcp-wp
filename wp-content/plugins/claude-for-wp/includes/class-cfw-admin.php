<?php
defined( 'ABSPATH' ) || exit;

class CFW_Admin {

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_footer', [ __CLASS__, 'render_floating_widget' ] );
    }

    public static function register_menus(): void {
        add_menu_page(
            'Claude for WP',
            'Claude for WP',
            'manage_options',
            'cfw-chat',
            [ __CLASS__, 'render_chat' ],
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'),
            30
        );

        add_submenu_page( 'cfw-chat', 'Chat',             'Chat',             'manage_options', 'cfw-chat',     [ __CLASS__, 'render_chat' ] );
        add_submenu_page( 'cfw-chat', 'Contenido',        'Contenido',        'manage_options', 'cfw-content',  [ __CLASS__, 'render_content' ] );
        add_submenu_page( 'cfw-chat', 'Elementor / HTML', 'Elementor / HTML', 'manage_options', 'cfw-elementor',[ __CLASS__, 'render_elementor' ] );
        add_submenu_page( 'cfw-chat', 'Ajustes',          'Ajustes',          'manage_options', 'cfw-settings', [ __CLASS__, 'render_settings' ] );
    }

    public static function enqueue_assets( string $hook ): void {
        // Floating widget assets — loaded on every admin page
        wp_enqueue_style(  'cfw-widget', CFW_URL . 'assets/css/widget.css', [], CFW_VERSION );
        wp_enqueue_script( 'cfw-widget', CFW_URL . 'assets/js/widget.js',  [], CFW_VERSION, true );
        wp_localize_script( 'cfw-widget', 'CFW', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'cfw_nonce' ),
        ]);

        // Full admin page assets — only on plugin pages
        if ( str_contains( $hook, 'cfw-' ) || str_contains( $hook, 'claude-for-wp' ) ) {
            wp_enqueue_style(  'cfw-style',  CFW_URL . 'assets/css/admin.css', [ 'cfw-widget' ], CFW_VERSION );
            wp_enqueue_script( 'cfw-script', CFW_URL . 'assets/js/admin.js',  [ 'cfw-widget' ], CFW_VERSION, true );
        }
    }

    // -------------------------------------------------------------------------
    // Pages
    // -------------------------------------------------------------------------

    public static function render_chat(): void {
        ?>
        <div class="wrap cfw-wrap">
            <div class="cfw-header">
                <h1>💬 Chat con Claude</h1>
                <p>Hazle cualquier pregunta o pídele ayuda con tu sitio.</p>
            </div>

            <div class="cfw-chat-window" id="cfw-chat-window">
                <div class="cfw-message cfw-message--assistant">
                    <span class="cfw-avatar">✦</span>
                    <div class="cfw-bubble">Hola, soy Claude. ¿En qué puedo ayudarte hoy?</div>
                </div>
            </div>

            <div class="cfw-input-row">
                <textarea id="cfw-chat-input" class="cfw-textarea" placeholder="Escribe tu mensaje..." rows="3"></textarea>
                <button id="cfw-chat-send" class="cfw-btn cfw-btn--primary">
                    <span class="cfw-btn-text">Enviar</span>
                    <span class="cfw-btn-loader" hidden>...</span>
                </button>
            </div>
        </div>
        <?php
    }

    public static function render_content(): void {
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        ?>
        <div class="wrap cfw-wrap">
            <div class="cfw-header">
                <h1>📝 Generador de contenido</h1>
                <p>Claude redacta o mejora contenido para tus posts y páginas.</p>
            </div>

            <div class="cfw-card">
                <div class="cfw-field">
                    <label for="cfw-content-mode">Modo</label>
                    <select id="cfw-content-mode" class="cfw-select">
                        <option value="generate">Generar nuevo contenido</option>
                        <option value="improve">Mejorar contenido existente</option>
                        <option value="seo">Título + meta descripción SEO</option>
                        <option value="excerpt">Generar extracto</option>
                    </select>
                </div>

                <div class="cfw-field">
                    <label for="cfw-content-prompt">Instrucción / Contenido a mejorar</label>
                    <textarea id="cfw-content-prompt" class="cfw-textarea" rows="5"
                        placeholder="Ej: Escribe un artículo de 500 palabras sobre los beneficios del aceite de oliva..."></textarea>
                </div>

                <div class="cfw-field">
                    <label for="cfw-content-tone">Tono</label>
                    <select id="cfw-content-tone" class="cfw-select">
                        <option value="profesional">Profesional</option>
                        <option value="cercano">Cercano y natural</option>
                        <option value="técnico">Técnico</option>
                        <option value="persuasivo">Persuasivo</option>
                    </select>
                </div>

                <button id="cfw-content-generate" class="cfw-btn cfw-btn--primary">
                    <span class="cfw-btn-text">Generar</span>
                    <span class="cfw-btn-loader" hidden>Generando...</span>
                </button>
            </div>

            <div class="cfw-result-block" id="cfw-content-result" hidden>
                <div class="cfw-result-toolbar">
                    <span class="cfw-result-label">Resultado</span>
                    <div class="cfw-result-actions">
                        <button class="cfw-btn cfw-btn--sm" id="cfw-content-copy">Copiar</button>
                        <button class="cfw-btn cfw-btn--sm cfw-btn--secondary" id="cfw-content-new-post">Crear post</button>
                    </div>
                </div>
                <textarea id="cfw-content-output" class="cfw-textarea cfw-textarea--output" rows="12"></textarea>
            </div>
        </div>
        <?php
    }

    public static function render_elementor(): void {
        ?>
        <div class="wrap cfw-wrap">
            <div class="cfw-header">
                <h1>🎨 Elementor / HTML</h1>
                <p>Genera bloques HTML+CSS listos para pegar en un widget HTML de Elementor.</p>
            </div>

            <div class="cfw-card">
                <div class="cfw-field">
                    <label for="cfw-el-type">Tipo de componente</label>
                    <select id="cfw-el-type" class="cfw-select">
                        <option value="hero">Hero section</option>
                        <option value="cards">Grid de cards</option>
                        <option value="cta">Bloque CTA</option>
                        <option value="testimonials">Testimonios</option>
                        <option value="faq">FAQ accordion</option>
                        <option value="pricing">Tabla de precios</option>
                        <option value="custom">Personalizado (describir abajo)</option>
                    </select>
                </div>

                <div class="cfw-field">
                    <label for="cfw-el-desc">Descripción / detalles</label>
                    <textarea id="cfw-el-desc" class="cfw-textarea" rows="4"
                        placeholder="Ej: Hero con fondo oscuro, título grande, subtítulo y dos botones. Paleta azul y blanco..."></textarea>
                </div>

                <div class="cfw-field cfw-field--row">
                    <div>
                        <label for="cfw-el-colors">Colores principales</label>
                        <input type="text" id="cfw-el-colors" class="cfw-input" placeholder="#1a1a2e, #ffffff" />
                    </div>
                    <div>
                        <label for="cfw-el-style">Estilo visual</label>
                        <select id="cfw-el-style" class="cfw-select">
                            <option value="moderno">Moderno / minimalista</option>
                            <option value="bold">Bold / impactante</option>
                            <option value="elegante">Elegante</option>
                            <option value="corporativo">Corporativo</option>
                            <option value="amigable">Amigable / colorido</option>
                        </select>
                    </div>
                </div>

                <button id="cfw-el-generate" class="cfw-btn cfw-btn--primary">
                    <span class="cfw-btn-text">Generar bloque</span>
                    <span class="cfw-btn-loader" hidden>Generando...</span>
                </button>
            </div>

            <div class="cfw-result-block" id="cfw-el-result" hidden>
                <div class="cfw-tabs">
                    <button class="cfw-tab cfw-tab--active" data-tab="code">Código</button>
                    <button class="cfw-tab" data-tab="preview">Preview</button>
                </div>
                <div class="cfw-tab-panel" id="cfw-tab-code">
                    <div class="cfw-result-toolbar">
                        <span class="cfw-result-label">HTML + CSS</span>
                        <button class="cfw-btn cfw-btn--sm" id="cfw-el-copy">Copiar código</button>
                    </div>
                    <textarea id="cfw-el-output" class="cfw-textarea cfw-textarea--code" rows="16" spellcheck="false"></textarea>
                </div>
                <div class="cfw-tab-panel" id="cfw-tab-preview" hidden>
                    <iframe id="cfw-el-preview" class="cfw-preview-frame"></iframe>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_settings(): void {
        $saved = get_option( CFW_Settings::OPTION_API_KEY, '' );
        $masked = $saved ? substr( $saved, 0, 8 ) . str_repeat( '•', 20 ) : '';
        ?>
        <div class="wrap cfw-wrap">
            <div class="cfw-header">
                <h1>⚙️ Ajustes</h1>
            </div>

            <div class="cfw-card">
                <form method="post" action="options.php">
                    <?php settings_fields( 'cfw_settings_group' ); ?>

                    <div class="cfw-field">
                        <label for="<?php echo CFW_Settings::OPTION_API_KEY; ?>">API Key de Anthropic</label>
                        <input
                            type="password"
                            id="<?php echo CFW_Settings::OPTION_API_KEY; ?>"
                            name="<?php echo CFW_Settings::OPTION_API_KEY; ?>"
                            class="cfw-input cfw-input--wide"
                            value="<?php echo esc_attr( $saved ); ?>"
                            placeholder="sk-ant-..."
                            autocomplete="off"
                        />
                        <p class="cfw-help">
                            Obtén tu API key en <a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a>.
                        </p>
                    </div>

                    <div class="cfw-field">
                        <label for="<?php echo CFW_Settings::OPTION_MODEL; ?>">Modelo</label>
                        <select id="<?php echo CFW_Settings::OPTION_MODEL; ?>" name="<?php echo CFW_Settings::OPTION_MODEL; ?>" class="cfw-select">
                            <?php foreach ( CFW_Settings::available_models() as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( CFW_Settings::get_model(), $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="cfw-btn cfw-btn--primary">Guardar ajustes</button>
                </form>
            </div>
        </div>
        <?php
    }
    // -------------------------------------------------------------------------
    // Floating widget — rendered on every admin page
    // -------------------------------------------------------------------------

    public static function render_floating_widget(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div id="cfw-widget" class="cfw-widget" aria-label="Claude for WP">

            <!-- Toggle button -->
            <button id="cfw-widget-toggle" class="cfw-widget__toggle" title="Claude for WP">
                <span class="cfw-widget__icon-open">✦</span>
                <span class="cfw-widget__icon-close" hidden>✕</span>
            </button>

            <!-- Panel -->
            <div id="cfw-widget-panel" class="cfw-widget__panel" hidden>
                <div class="cfw-widget__header">
                    <div class="cfw-widget__title">
                        <span class="cfw-widget__title-icon">✦</span>
                        Claude for WP
                    </div>
                    <div class="cfw-widget__header-actions">
                        <button id="cfw-widget-clear" class="cfw-widget__action-btn" title="Limpiar conversación">↺</button>
                        <button id="cfw-widget-close" class="cfw-widget__action-btn" title="Cerrar">✕</button>
                    </div>
                </div>

                <div id="cfw-widget-messages" class="cfw-widget__messages">
                    <div class="cfw-widget__msg cfw-widget__msg--assistant">
                        <span class="cfw-widget__avatar">✦</span>
                        <div class="cfw-widget__bubble">Hola, soy Claude. Puedo ayudarte con cualquier cosa en este sitio WordPress. ¿Qué necesitas?</div>
                    </div>
                </div>

                <div class="cfw-widget__input-area">
                    <textarea
                        id="cfw-widget-input"
                        class="cfw-widget__input"
                        placeholder="Escribe un mensaje... (Ctrl+Enter)"
                        rows="2"
                    ></textarea>
                    <button id="cfw-widget-send" class="cfw-widget__send">
                        <span class="cfw-widget__send-icon">➤</span>
                        <span class="cfw-widget__send-loader" hidden>…</span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

}
