<?php
defined( 'ABSPATH' ) || exit;

class CFW_Ajax {

    public static function init(): void {
        $actions = [ 'cfw_chat', 'cfw_content', 'cfw_elementor', 'cfw_create_post' ];
        foreach ( $actions as $action ) {
            add_action( "wp_ajax_$action", [ __CLASS__, $action ] );
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function verify(): void {
        check_ajax_referer( 'cfw_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sin permisos.', 403 );
        }
    }

    private static function respond( string|WP_Error $result ): void {
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success( [ 'text' => $result ] );
    }

    // -------------------------------------------------------------------------
    // Chat — uses Tool Use loop
    // -------------------------------------------------------------------------

    public static function cfw_chat(): void {
        self::verify();

        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        $history = json_decode( stripslashes( $_POST['history'] ?? '[]' ), true );

        if ( empty( $message ) ) {
            wp_send_json_error( 'Mensaje vacío.' );
        }

        if ( ! is_array( $history ) ) $history = [];

        $system = 'Eres un asistente experto en WordPress integrado directamente en el panel de administración. '
            . 'Tienes acceso a herramientas que te permiten leer y modificar este sitio WordPress en tiempo real. '
            . 'Cuando el usuario te pida hacer algo en WordPress (crear posts, listar contenido, cambiar opciones, etc.), '
            . 'usa las herramientas disponibles para ejecutarlo directamente — no expliques cómo hacerlo manualmente. '
            . 'Después de ejecutar una acción, confirma qué hiciste con un resumen claro. '
            . 'Si necesitas información del sitio antes de actuar, consúltala primero con get_site_info o get_posts. '
            . 'Responde siempre en español. Sé conciso y directo.';

        // Build messages array from history + new message
        $messages = [];
        foreach ( $history as $entry ) {
            $role    = sanitize_text_field( $entry['role'] ?? '' );
            $content = sanitize_textarea_field( $entry['content'] ?? '' );
            if ( in_array( $role, [ 'user', 'assistant' ], true ) && ! empty( $content ) ) {
                $messages[] = [ 'role' => $role, 'content' => $content ];
            }
        }
        $messages[] = [ 'role' => 'user', 'content' => $message ];

        $result = CFW_Api::send_with_tools( $messages, $system, 4096 );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success([
            'text'    => $result['text'],
            'actions' => $result['actions'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Content generation
    // -------------------------------------------------------------------------

    public static function cfw_content(): void {
        self::verify();

        $mode   = sanitize_text_field( $_POST['mode']   ?? 'generate' );
        $prompt = sanitize_textarea_field( $_POST['prompt'] ?? '' );
        $tone   = sanitize_text_field( $_POST['tone']   ?? 'profesional' );

        if ( empty( $prompt ) ) {
            wp_send_json_error( 'Introduce una instrucción.' );
        }

        $system_map = [
            'generate' => "Eres un redactor web. Tu única tarea es generar el contenido solicitado en HTML semántico (usa <h2>, <p>, <ul>, etc.). Tono: $tone. IMPORTANTE: devuelve ÚNICAMENTE el contenido HTML, sin introducción, sin disclaimers, sin explicaciones, sin bloques de código markdown. Solo el HTML del contenido.",
            'improve'  => "Eres un editor experto. Mejora el contenido que recibes manteniendo la esencia pero mejorando claridad, fluidez y estructura. Tono: $tone. Devuelve ÚNICAMENTE el contenido mejorado en HTML, sin ningún texto adicional.",
            'seo'      => "Eres un experto SEO. Genera exactamente esto y nada más:\nTÍTULO: [título SEO optimizado, máx 60 caracteres]\nMETA: [meta descripción, máx 155 caracteres]",
            'excerpt'  => "Genera un extracto de 2-3 frases para el contenido recibido. Devuelve ÚNICAMENTE el extracto, sin comillas, sin explicaciones, sin nada más.",
        ];

        $system = $system_map[ $mode ] ?? $system_map['generate'];

        $task_context = "TAREA: generar contenido para un post de WordPress.\n\nINSTRUCCIÓN DEL USUARIO: $prompt";
        self::respond( CFW_Api::send( $task_context, $system, 3000 ) );
    }

    // -------------------------------------------------------------------------
    // Elementor / HTML block generation
    // -------------------------------------------------------------------------

    public static function cfw_elementor(): void {
        self::verify();

        $type   = sanitize_text_field( $_POST['type']   ?? 'hero' );
        $desc   = sanitize_textarea_field( $_POST['desc'] ?? '' );
        $colors = sanitize_text_field( $_POST['colors'] ?? '' );
        $style  = sanitize_text_field( $_POST['style']  ?? 'moderno' );

        $type_labels = [
            'hero'         => 'Hero section con título, subtítulo y botón CTA',
            'cards'        => 'Grid de 3 cards con icono, título y descripción',
            'cta'          => 'Sección Call to Action con título y botón',
            'testimonials' => 'Sección de testimonios con foto, nombre y cita',
            'faq'          => 'FAQ con accordion en JavaScript puro',
            'pricing'      => 'Tabla de precios con 3 planes',
            'custom'       => '',
        ];

        $base       = $type !== 'custom' ? $type_labels[ $type ] : '';
        $full_desc  = trim( "$base. $desc" );
        $color_note = $colors ? "Paleta de colores: $colors." : '';

        $user_message = "Crea un bloque HTML+CSS para: $full_desc. $color_note Estilo visual: $style. "
            . "Requisitos: código autocontenido (CSS en <style> dentro del mismo HTML), "
            . "responsive mobile-first, clases con prefijo 'cfw-block-' para evitar conflictos con Elementor, "
            . "listo para pegar en un widget HTML de Elementor. "
            . "Devuelve SOLO el código HTML, sin explicaciones ni bloques de markdown.";

        $system = 'Eres un desarrollador frontend experto en Elementor y WordPress. '
            . 'Generas bloques HTML+CSS autocontenidos, modernos, responsive y listos para producción. '
            . 'Nunca uses frameworks externos. Solo HTML, CSS y JS vanilla si es necesario. '
            . 'El CSS siempre va dentro de una etiqueta <style> al inicio del bloque. '
            . 'Responde ÚNICAMENTE con el código, sin ningún texto adicional.';

        self::respond( CFW_Api::send( $user_message, $system, 4000 ) );
    }

    // -------------------------------------------------------------------------
    // Create post from generated content
    // -------------------------------------------------------------------------

    public static function cfw_create_post(): void {
        self::verify();

        $content = wp_kses_post( $_POST['content'] ?? '' );
        $title   = sanitize_text_field( $_POST['title'] ?? 'Nuevo post generado por Claude' );

        if ( empty( $content ) ) {
            wp_send_json_error( 'Contenido vacío.' );
        }

        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_type'    => 'post',
        ], true );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( $post_id->get_error_message() );
        }

        wp_send_json_success([
            'post_id'  => $post_id,
            'edit_url' => get_edit_post_link( $post_id, 'raw' ),
        ]);
    }
}
