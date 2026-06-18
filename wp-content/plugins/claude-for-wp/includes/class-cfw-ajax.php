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
    // Chat
    // -------------------------------------------------------------------------

    public static function cfw_chat(): void {
        self::verify();

        $message = sanitize_textarea_field( $_POST['message'] ?? '' );
        if ( empty( $message ) ) {
            wp_send_json_error( 'Mensaje vacío.' );
        }

        $system = 'Eres un asistente experto en WordPress, Elementor y desarrollo web. '
            . 'Responde siempre en español. Sé conciso y práctico. '
            . 'Cuando des código, usa bloques de código con el lenguaje correspondiente.';

        self::respond( CFW_Api::send( $message, $system, 2048 ) );
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
            'generate' => "Eres un redactor web experto. Genera contenido en HTML semántico (usa <h2>, <p>, <ul>, etc.). Tono: $tone. Responde solo con el contenido, sin explicaciones.",
            'improve'  => "Eres un editor experto. Mejora el siguiente contenido manteniendo la esencia pero mejorando claridad, fluidez y estructura. Tono: $tone. Devuelve solo el contenido mejorado en HTML.",
            'seo'      => "Eres un experto SEO. Dado el contenido o tema, genera: 1 título SEO optimizado (máx 60 chars) y 1 meta descripción (máx 155 chars). Formato: TÍTULO: ...\nMETA: ...",
            'excerpt'  => "Eres un redactor experto. Genera un extracto atractivo de 2-3 frases para el siguiente contenido. Solo el extracto, sin comillas ni explicaciones.",
        ];

        $system = $system_map[ $mode ] ?? $system_map['generate'];

        self::respond( CFW_Api::send( $prompt, $system, 3000 ) );
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

        $base = $type !== 'custom' ? $type_labels[ $type ] : '';
        $full_desc = trim( "$base. $desc" );
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
