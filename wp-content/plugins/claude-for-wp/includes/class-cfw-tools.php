<?php
defined( 'ABSPATH' ) || exit;

/**
 * Defines the tools Claude can use and executes them when called.
 * Each tool maps directly to WordPress functions or REST API operations.
 */
class CFW_Tools {

    /**
     * Tool definitions sent to the Anthropic API.
     */
    public static function definitions(): array {
        return [

            // ── Posts ─────────────────────────────────────────────────────────
            [
                'name'        => 'get_posts',
                'description' => 'Lista posts o páginas de WordPress. Útil para buscar contenido existente antes de modificarlo.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'post_type'   => [ 'type' => 'string',  'description' => 'Tipo de post: post, page u otro custom post type. Por defecto: post.' ],
                        'post_status' => [ 'type' => 'string',  'description' => 'Estado: publish, draft, pending, private. Por defecto: any.' ],
                        'search'      => [ 'type' => 'string',  'description' => 'Texto a buscar en título o contenido.' ],
                        'limit'       => [ 'type' => 'integer', 'description' => 'Número máximo de resultados. Por defecto: 10.' ],
                    ],
                ],
            ],

            [
                'name'        => 'create_post',
                'description' => 'Crea un nuevo post o página en WordPress.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'title'       => [ 'type' => 'string', 'description' => 'Título del post.' ],
                        'content'     => [ 'type' => 'string', 'description' => 'Contenido en HTML.' ],
                        'post_type'   => [ 'type' => 'string', 'description' => 'Tipo: post o page. Por defecto: post.' ],
                        'post_status' => [ 'type' => 'string', 'description' => 'Estado: draft, publish, pending, private. Por defecto: draft.' ],
                        'excerpt'     => [ 'type' => 'string', 'description' => 'Extracto opcional.' ],
                        'categories'  => [ 'type' => 'array',  'items' => [ 'type' => 'string' ], 'description' => 'Nombres de categorías a asignar.' ],
                        'tags'        => [ 'type' => 'array',  'items' => [ 'type' => 'string' ], 'description' => 'Nombres de etiquetas a asignar.' ],
                    ],
                    'required' => [ 'title' ],
                ],
            ],

            [
                'name'        => 'update_post',
                'description' => 'Modifica un post o página existente. Usa get_posts primero si no conoces el ID.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id'     => [ 'type' => 'integer', 'description' => 'ID del post a modificar.' ],
                        'title'       => [ 'type' => 'string',  'description' => 'Nuevo título.' ],
                        'content'     => [ 'type' => 'string',  'description' => 'Nuevo contenido en HTML.' ],
                        'post_status' => [ 'type' => 'string',  'description' => 'Nuevo estado: draft, publish, pending, private.' ],
                        'excerpt'     => [ 'type' => 'string',  'description' => 'Nuevo extracto.' ],
                    ],
                    'required' => [ 'post_id' ],
                ],
            ],

            [
                'name'        => 'delete_post',
                'description' => 'Mueve un post a la papelera. No lo elimina permanentemente.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id' => [ 'type' => 'integer', 'description' => 'ID del post a eliminar.' ],
                    ],
                    'required' => [ 'post_id' ],
                ],
            ],

            // ── Taxonomies ────────────────────────────────────────────────────
            [
                'name'        => 'get_terms',
                'description' => 'Lista categorías, etiquetas u otras taxonomías.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'taxonomy' => [ 'type' => 'string', 'description' => 'Taxonomía: category, post_tag u otra. Por defecto: category.' ],
                        'search'   => [ 'type' => 'string', 'description' => 'Texto a buscar en el nombre.' ],
                    ],
                ],
            ],

            [
                'name'        => 'create_term',
                'description' => 'Crea una nueva categoría, etiqueta u otro término de taxonomía.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'name'        => [ 'type' => 'string', 'description' => 'Nombre del término.' ],
                        'taxonomy'    => [ 'type' => 'string', 'description' => 'Taxonomía: category o post_tag. Por defecto: category.' ],
                        'description' => [ 'type' => 'string', 'description' => 'Descripción opcional.' ],
                        'parent'      => [ 'type' => 'integer', 'description' => 'ID del término padre (solo para categorías).' ],
                    ],
                    'required' => [ 'name' ],
                ],
            ],

            // ── Options ───────────────────────────────────────────────────────
            [
                'name'        => 'get_site_info',
                'description' => 'Obtiene información general del sitio: nombre, descripción, URL, tema activo, plugins activos, versión de WordPress.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => (object) [],
                ],
            ],

            [
                'name'        => 'update_site_option',
                'description' => 'Modifica una opción del sitio WordPress. Opciones comunes: blogname (nombre del sitio), blogdescription (descripción/tagline).',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'option' => [ 'type' => 'string', 'description' => 'Nombre de la opción WP (blogname, blogdescription, etc.).' ],
                        'value'  => [ 'type' => 'string', 'description' => 'Nuevo valor.' ],
                    ],
                    'required' => [ 'option', 'value' ],
                ],
            ],

            // ── Users ─────────────────────────────────────────────────────────
            [
                'name'        => 'get_users',
                'description' => 'Lista los usuarios del sitio WordPress.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'role'   => [ 'type' => 'string',  'description' => 'Filtrar por rol: administrator, editor, author, contributor, subscriber.' ],
                        'search' => [ 'type' => 'string',  'description' => 'Buscar por nombre o email.' ],
                        'limit'  => [ 'type' => 'integer', 'description' => 'Número máximo de resultados. Por defecto: 20.' ],
                    ],
                ],
            ],

            // ── Plugins ───────────────────────────────────────────────────────
            [
                'name'        => 'get_plugins',
                'description' => 'Lista los plugins instalados con su estado (activo/inactivo) y versión.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => (object) [],
                ],
            ],

            // ── Media ─────────────────────────────────────────────────────────
            [
                'name'        => 'get_media',
                'description' => 'Lista archivos de la biblioteca de medios.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'search'    => [ 'type' => 'string',  'description' => 'Buscar por nombre de archivo.' ],
                        'mime_type' => [ 'type' => 'string',  'description' => 'Filtrar por tipo: image, video, audio, application.' ],
                        'limit'     => [ 'type' => 'integer', 'description' => 'Número máximo de resultados. Por defecto: 20.' ],
                    ],
                ],
            ],

            // ── Elementor ─────────────────────────────────────────────────────
            [
                'name'        => 'create_elementor_page',
                'description' => 'Crea una página de WordPress con layout de Elementor usando widgets nativos básicos. '
                    . 'Soporta: heading, text, button, image, spacer, divider, icon-box, image-box, video. '
                    . 'Organiza los widgets en secciones con columnas. '
                    . 'Úsalo cuando el usuario pida crear una página con Elementor, una landing page, o una página con estructura visual.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'title'       => [ 'type' => 'string', 'description' => 'Título de la página.' ],
                        'post_status' => [ 'type' => 'string', 'description' => 'Estado: draft o publish. Por defecto: draft.' ],
                        'sections'    => [
                            'type'        => 'array',
                            'description' => 'Array de secciones. Cada sección contiene columnas y cada columna contiene widgets.',
                            'items'       => [
                                'type'       => 'object',
                                'properties' => [
                                    'bg_color' => [ 'type' => 'string', 'description' => 'Color de fondo de la sección en hex, ej: #1a1a2e. Opcional.' ],
                                    'padding'  => [ 'type' => 'string', 'description' => 'Padding vertical en px, ej: 80. Opcional.' ],
                                    'columns'  => [
                                        'type'  => 'array',
                                        'description' => 'Columnas de la sección. 1 columna = full width, 2 columnas = mitad/mitad, etc.',
                                        'items' => [
                                            'type'       => 'object',
                                            'properties' => [
                                                'width'   => [ 'type' => 'number', 'description' => 'Ancho de la columna en porcentaje, ej: 50, 33.33, 100. Por defecto: 100.' ],
                                                'widgets' => [
                                                    'type'  => 'array',
                                                    'description' => 'Widgets de esta columna.',
                                                    'items' => [
                                                        'type'       => 'object',
                                                        'properties' => [
                                                            'type' => [
                                                                'type' => 'string',
                                                                'description' => 'Tipo de widget: heading, text, button, image, spacer, divider, icon-box, image-box, video.',
                                                            ],
                                                            'settings' => [
                                                                'type'        => 'object',
                                                                'description' => 'Configuración del widget. Varía según el tipo. Ver documentación de cada tipo.',
                                                            ],
                                                        ],
                                                        'required' => [ 'type', 'settings' ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'required' => [ 'title', 'sections' ],
                ],
            ],
        ];
    }

    // =========================================================================
    // Tool executor — dispatches to the right method
    // =========================================================================

    public static function execute( string $tool_name, array $input ): array {
        $allowed = array_column( self::definitions(), 'name' );
        if ( ! in_array( $tool_name, $allowed, true ) ) {
            return [ 'error' => "Tool desconocida: $tool_name" ];
        }

        try {
            return match ( $tool_name ) {
                'get_posts'          => self::tool_get_posts( $input ),
                'create_post'        => self::tool_create_post( $input ),
                'update_post'        => self::tool_update_post( $input ),
                'delete_post'        => self::tool_delete_post( $input ),
                'get_terms'          => self::tool_get_terms( $input ),
                'create_term'        => self::tool_create_term( $input ),
                'get_site_info'      => self::tool_get_site_info(),
                'update_site_option' => self::tool_update_site_option( $input ),
                'get_users'          => self::tool_get_users( $input ),
                'get_plugins'        => self::tool_get_plugins(),
                'get_media'          => self::tool_get_media( $input ),
                'create_elementor_page' => self::tool_create_elementor_page( $input ),
            };
        } catch ( \Throwable $e ) {
            return [ 'error' => $e->getMessage() ];
        }
    }

    // =========================================================================
    // Tool implementations
    // =========================================================================

    private static function tool_get_posts( array $in ): array {
        $args = [
            'post_type'      => sanitize_text_field( $in['post_type'] ?? 'post' ),
            'post_status'    => sanitize_text_field( $in['post_status'] ?? 'any' ),
            'posts_per_page' => min( (int) ( $in['limit'] ?? 10 ), 50 ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( ! empty( $in['search'] ) ) {
            $args['s'] = sanitize_text_field( $in['search'] );
        }

        $posts = get_posts( $args );

        return [
            'count' => count( $posts ),
            'posts' => array_map( fn( $p ) => [
                'id'          => $p->ID,
                'title'       => $p->post_title,
                'status'      => $p->post_status,
                'date'        => $p->post_date,
                'edit_url'    => get_edit_post_link( $p->ID, 'raw' ),
                'excerpt'     => wp_trim_words( $p->post_content, 20 ),
            ], $posts ),
        ];
    }

    private static function tool_create_post( array $in ): array {
        $data = [
            'post_title'   => sanitize_text_field( $in['title'] ?? '' ),
            'post_content' => wp_kses_post( $in['content'] ?? '' ),
            'post_type'    => sanitize_text_field( $in['post_type'] ?? 'post' ),
            'post_status'  => sanitize_text_field( $in['post_status'] ?? 'draft' ),
            'post_excerpt' => sanitize_textarea_field( $in['excerpt'] ?? '' ),
        ];

        $post_id = wp_insert_post( $data, true );

        if ( is_wp_error( $post_id ) ) {
            return [ 'error' => $post_id->get_error_message() ];
        }

        // Assign categories
        if ( ! empty( $in['categories'] ) && is_array( $in['categories'] ) ) {
            $cat_ids = [];
            foreach ( $in['categories'] as $cat_name ) {
                $term = get_term_by( 'name', sanitize_text_field( $cat_name ), 'category' );
                if ( $term ) {
                    $cat_ids[] = $term->term_id;
                } else {
                    $new = wp_insert_term( sanitize_text_field( $cat_name ), 'category' );
                    if ( ! is_wp_error( $new ) ) $cat_ids[] = $new['term_id'];
                }
            }
            wp_set_post_categories( $post_id, $cat_ids );
        }

        // Assign tags
        if ( ! empty( $in['tags'] ) && is_array( $in['tags'] ) ) {
            wp_set_post_tags( $post_id, array_map( 'sanitize_text_field', $in['tags'] ) );
        }

        return [
            'success'  => true,
            'post_id'  => $post_id,
            'title'    => $data['post_title'],
            'status'   => $data['post_status'],
            'edit_url' => get_edit_post_link( $post_id, 'raw' ),
        ];
    }

    private static function tool_update_post( array $in ): array {
        $post_id = (int) ( $in['post_id'] ?? 0 );

        if ( ! $post_id || ! get_post( $post_id ) ) {
            return [ 'error' => "Post ID $post_id no encontrado." ];
        }

        $data = [ 'ID' => $post_id ];

        if ( isset( $in['title'] ) )       $data['post_title']   = sanitize_text_field( $in['title'] );
        if ( isset( $in['content'] ) )     $data['post_content'] = wp_kses_post( $in['content'] );
        if ( isset( $in['post_status'] ) ) $data['post_status']  = sanitize_text_field( $in['post_status'] );
        if ( isset( $in['excerpt'] ) )     $data['post_excerpt'] = sanitize_textarea_field( $in['excerpt'] );

        $result = wp_update_post( $data, true );

        if ( is_wp_error( $result ) ) {
            return [ 'error' => $result->get_error_message() ];
        }

        return [
            'success'  => true,
            'post_id'  => $post_id,
            'edit_url' => get_edit_post_link( $post_id, 'raw' ),
        ];
    }

    private static function tool_delete_post( array $in ): array {
        $post_id = (int) ( $in['post_id'] ?? 0 );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return [ 'error' => "Post ID $post_id no encontrado." ];
        }

        $title = $post->post_title;
        wp_trash_post( $post_id );

        return [ 'success' => true, 'message' => "Post \"$title\" (ID: $post_id) movido a la papelera." ];
    }

    private static function tool_get_terms( array $in ): array {
        $args = [
            'taxonomy'   => sanitize_text_field( $in['taxonomy'] ?? 'category' ),
            'hide_empty' => false,
            'number'     => 100,
        ];

        if ( ! empty( $in['search'] ) ) {
            $args['search'] = sanitize_text_field( $in['search'] );
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) ) {
            return [ 'error' => $terms->get_error_message() ];
        }

        return [
            'count' => count( $terms ),
            'terms' => array_map( fn( $t ) => [
                'id'    => $t->term_id,
                'name'  => $t->name,
                'slug'  => $t->slug,
                'count' => $t->count,
            ], $terms ),
        ];
    }

    private static function tool_create_term( array $in ): array {
        $taxonomy = sanitize_text_field( $in['taxonomy'] ?? 'category' );
        $name     = sanitize_text_field( $in['name'] ?? '' );

        $args = [];
        if ( ! empty( $in['description'] ) ) $args['description'] = sanitize_textarea_field( $in['description'] );
        if ( ! empty( $in['parent'] ) )       $args['parent']      = (int) $in['parent'];

        $result = wp_insert_term( $name, $taxonomy, $args );

        if ( is_wp_error( $result ) ) {
            return [ 'error' => $result->get_error_message() ];
        }

        return [ 'success' => true, 'term_id' => $result['term_id'], 'name' => $name, 'taxonomy' => $taxonomy ];
    }

    private static function tool_get_site_info(): array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins    = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );
        $active_list    = [];

        foreach ( $active_plugins as $plugin_file ) {
            if ( isset( $all_plugins[ $plugin_file ] ) ) {
                $active_list[] = $all_plugins[ $plugin_file ]['Name'];
            }
        }

        $theme = wp_get_theme();

        return [
            'site_name'        => get_bloginfo( 'name' ),
            'tagline'          => get_bloginfo( 'description' ),
            'url'              => get_bloginfo( 'url' ),
            'admin_email'      => get_bloginfo( 'admin_email' ),
            'wp_version'       => get_bloginfo( 'version' ),
            'language'         => get_bloginfo( 'language' ),
            'active_theme'     => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
            'active_plugins'   => $active_list,
            'total_posts'      => wp_count_posts()->publish,
            'total_pages'      => wp_count_posts( 'page' )->publish,
            'total_users'      => count_users()['total_users'],
        ];
    }

    private static function tool_update_site_option( array $in ): array {
        $allowed = [ 'blogname', 'blogdescription', 'admin_email', 'date_format', 'time_format', 'timezone_string' ];
        $option  = sanitize_text_field( $in['option'] ?? '' );

        if ( ! in_array( $option, $allowed, true ) ) {
            return [ 'error' => "Opción no permitida: $option. Permitidas: " . implode( ', ', $allowed ) ];
        }

        update_option( $option, sanitize_text_field( $in['value'] ?? '' ) );

        return [ 'success' => true, 'option' => $option, 'value' => $in['value'] ];
    }

    private static function tool_get_users( array $in ): array {
        $args = [
            'number' => min( (int) ( $in['limit'] ?? 20 ), 100 ),
        ];

        if ( ! empty( $in['role'] ) )   $args['role']   = sanitize_text_field( $in['role'] );
        if ( ! empty( $in['search'] ) ) $args['search']  = '*' . sanitize_text_field( $in['search'] ) . '*';

        $users = get_users( $args );

        return [
            'count' => count( $users ),
            'users' => array_map( fn( $u ) => [
                'id'           => $u->ID,
                'display_name' => $u->display_name,
                'email'        => $u->user_email,
                'roles'        => $u->roles,
                'registered'   => $u->user_registered,
            ], $users ),
        ];
    }

    private static function tool_get_plugins(): array {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins    = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );
        $result         = [];

        foreach ( $all_plugins as $file => $data ) {
            $result[] = [
                'name'    => $data['Name'],
                'version' => $data['Version'],
                'active'  => in_array( $file, $active_plugins, true ),
                'author'  => $data['Author'],
            ];
        }

        return [ 'count' => count( $result ), 'plugins' => $result ];
    }

    private static function tool_get_media( array $in ): array {
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => min( (int) ( $in['limit'] ?? 20 ), 50 ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( ! empty( $in['search'] ) )    $args['s']         = sanitize_text_field( $in['search'] );
        if ( ! empty( $in['mime_type'] ) ) $args['post_mime_type'] = sanitize_text_field( $in['mime_type'] );

        $items = get_posts( $args );

        return [
            'count' => count( $items ),
            'media' => array_map( fn( $p ) => [
                'id'       => $p->ID,
                'title'    => $p->post_title,
                'filename' => basename( get_attached_file( $p->ID ) ),
                'url'      => wp_get_attachment_url( $p->ID ),
                'type'     => $p->post_mime_type,
                'date'     => $p->post_date,
            ], $items ),
        ];
    }
    // =========================================================================
    // Elementor page builder
    // =========================================================================

    private static function tool_create_elementor_page( array $in ): array {
        $title   = sanitize_text_field( $in['title'] ?? 'Nueva página' );
        $status  = sanitize_text_field( $in['post_status'] ?? 'draft' );
        $sections_input = $in['sections'] ?? [];

        // Create the WP post first
        $post_id = wp_insert_post([
            'post_title'  => $title,
            'post_type'   => 'page',
            'post_status' => $status,
            'post_content' => '',
        ], true );

        if ( is_wp_error( $post_id ) ) {
            return [ 'error' => $post_id->get_error_message() ];
        }

        // Build Elementor JSON
        $elementor_data = self::build_elementor_data( $sections_input );

        // Save Elementor meta
        update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
        update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
        update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
        update_post_meta( $post_id, '_elementor_version', '3.0.0' );

        return [
            'success'       => true,
            'post_id'       => $post_id,
            'title'         => $title,
            'status'        => $status,
            'sections'      => count( $elementor_data ),
            'edit_url'      => admin_url( "post.php?post={$post_id}&action=elementor" ),
            'preview_url'   => get_permalink( $post_id ),
        ];
    }

    // ── Elementor data builder ────────────────────────────────────────────────

    private static function build_elementor_data( array $sections_input ): array {
        $sections = [];

        foreach ( $sections_input as $section_input ) {
            $columns_input = $section_input['columns'] ?? [];
            if ( empty( $columns_input ) ) continue;

            $section_id = self::el_id();
            $columns    = [];

            foreach ( $columns_input as $col_input ) {
                $col_id  = self::el_id();
                $width   = (float) ( $col_input['width'] ?? ( 100 / count( $columns_input ) ) );
                $widgets = [];

                foreach ( $col_input['widgets'] ?? [] as $w ) {
                    $widget = self::build_widget( $w['type'] ?? 'text', $w['settings'] ?? [] );
                    if ( $widget ) $widgets[] = $widget;
                }

                $columns[] = [
                    'id'       => $col_id,
                    'elType'   => 'column',
                    'settings' => [
                        '_column_size' => (int) round( $width ),
                        '_inline_size' => $width,
                    ],
                    'elements' => $widgets,
                    'isInner'  => false,
                ];
            }

            // Section settings
            $section_settings = [ 'layout' => 'boxed' ];

            if ( ! empty( $section_input['bg_color'] ) ) {
                $section_settings['background_background'] = 'classic';
                $section_settings['background_color']      = sanitize_hex_color( $section_input['bg_color'] );
            }

            if ( ! empty( $section_input['padding'] ) ) {
                $px = (int) $section_input['padding'];
                $section_settings['padding'] = [
                    'top'    => (string) $px, 'right'  => '0',
                    'bottom' => (string) $px, 'left'   => '0',
                    'unit'   => 'px', 'isLinked' => false,
                ];
            }

            $sections[] = [
                'id'       => $section_id,
                'elType'   => 'section',
                'settings' => $section_settings,
                'elements' => $columns,
                'isInner'  => false,
            ];
        }

        return $sections;
    }

    // ── Widget factory ────────────────────────────────────────────────────────

    private static function build_widget( string $type, array $s ): ?array {
        $id = self::el_id();

        $widget = [
            'id'         => $id,
            'elType'     => 'widget',
            'widgetType' => $type,
            'settings'   => [],
            'elements'   => [],
        ];

        switch ( $type ) {

            case 'heading':
                $widget['settings'] = [
                    'title'       => sanitize_text_field( $s['title'] ?? 'Título' ),
                    'header_size' => sanitize_text_field( $s['tag'] ?? 'h2' ),
                    'align'       => sanitize_text_field( $s['align'] ?? 'left' ),
                    'title_color' => sanitize_hex_color( $s['color'] ?? '' ),
                    'typography_typography' => 'custom',
                    'typography_font_size'  => empty( $s['font_size'] ) ? [] : [ 'size' => (int) $s['font_size'], 'unit' => 'px' ],
                ];
                break;

            case 'text':
            case 'text-editor':
                $widget['widgetType'] = 'text-editor';
                $widget['settings']   = [
                    'editor' => wp_kses_post( $s['content'] ?? $s['text'] ?? '' ),
                    'align'  => sanitize_text_field( $s['align'] ?? 'left' ),
                ];
                break;

            case 'button':
                $widget['settings'] = [
                    'text'         => sanitize_text_field( $s['text'] ?? 'Haz clic aquí' ),
                    'link'         => [ 'url' => esc_url_raw( $s['url'] ?? '#' ), 'is_external' => false, 'nofollow' => false ],
                    'align'        => sanitize_text_field( $s['align'] ?? 'center' ),
                    'button_type'  => 'default',
                    'size'         => sanitize_text_field( $s['size'] ?? 'md' ),
                    'background_color' => sanitize_hex_color( $s['bg_color'] ?? '' ),
                    'button_text_color' => sanitize_hex_color( $s['text_color'] ?? '' ),
                ];
                break;

            case 'image':
                $image_id  = (int) ( $s['image_id'] ?? 0 );
                $image_url = $image_id ? wp_get_attachment_url( $image_id ) : esc_url_raw( $s['url'] ?? '' );
                $widget['settings'] = [
                    'image'   => [ 'id' => $image_id, 'url' => $image_url ],
                    'caption' => sanitize_text_field( $s['caption'] ?? '' ),
                    'align'   => sanitize_text_field( $s['align'] ?? 'center' ),
                    'width'   => [ 'size' => (int) ( $s['width'] ?? 100 ), 'unit' => '%' ],
                    'link_to' => 'none',
                ];
                break;

            case 'spacer':
                $widget['settings'] = [
                    'space' => [ 'size' => (int) ( $s['height'] ?? 50 ), 'unit' => 'px' ],
                ];
                break;

            case 'divider':
                $widget['settings'] = [
                    'style'  => sanitize_text_field( $s['style'] ?? 'solid' ),
                    'color'  => sanitize_hex_color( $s['color'] ?? '#e0e0e0' ),
                    'weight' => [ 'size' => (int) ( $s['weight'] ?? 1 ), 'unit' => 'px' ],
                    'width'  => [ 'size' => (int) ( $s['width'] ?? 100 ), 'unit' => '%' ],
                    'align'  => sanitize_text_field( $s['align'] ?? 'center' ),
                    'gap'    => [ 'size' => 15, 'unit' => 'px' ],
                ];
                break;

            case 'icon-box':
                $widget['settings'] = [
                    'icon'         => [ 'value' => sanitize_text_field( $s['icon'] ?? 'fas fa-star' ), 'library' => 'fa-solid' ],
                    'title_text'   => sanitize_text_field( $s['title'] ?? 'Título' ),
                    'description_text' => sanitize_textarea_field( $s['description'] ?? '' ),
                    'position'     => sanitize_text_field( $s['position'] ?? 'top' ),
                    'title_size'   => 'h3',
                ];
                break;

            case 'image-box':
                $image_id  = (int) ( $s['image_id'] ?? 0 );
                $image_url = $image_id ? wp_get_attachment_url( $image_id ) : esc_url_raw( $s['url'] ?? '' );
                $widget['settings'] = [
                    'image'            => [ 'id' => $image_id, 'url' => $image_url ],
                    'title_text'       => sanitize_text_field( $s['title'] ?? 'Título' ),
                    'description_text' => sanitize_textarea_field( $s['description'] ?? '' ),
                    'position'         => sanitize_text_field( $s['position'] ?? 'top' ),
                    'title_size'       => 'h3',
                    'image_size'       => 'medium',
                ];
                break;

            case 'video':
                $widget['settings'] = [
                    'video_type'  => sanitize_text_field( $s['provider'] ?? 'youtube' ),
                    'youtube_url' => esc_url_raw( $s['url'] ?? '' ),
                    'vimeo_url'   => esc_url_raw( $s['vimeo_url'] ?? '' ),
                    'autoplay'    => false,
                    'mute'        => false,
                    'aspect_ratio'=> '169',
                ];
                break;

            default:
                return null;
        }

        return $widget;
    }

    // ── Helper: generate Elementor-style random ID ────────────────────────────

    private static function el_id(): string {
        return substr( md5( uniqid( '', true ) ), 0, 7 );
    }

}
