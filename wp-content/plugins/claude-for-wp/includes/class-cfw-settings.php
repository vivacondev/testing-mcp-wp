<?php
defined( 'ABSPATH' ) || exit;

class CFW_Settings {

    const OPTION_API_KEY = 'cfw_api_key';
    const OPTION_MODEL   = 'cfw_model';

    public static function init(): void {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings(): void {
        register_setting( 'cfw_settings_group', self::OPTION_API_KEY, [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting( 'cfw_settings_group', self::OPTION_MODEL, [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'claude-sonnet-4-6',
        ]);
    }

    public static function get_api_key(): string {
        return (string) get_option( self::OPTION_API_KEY, '' );
    }

    public static function get_model(): string {
        return (string) get_option( self::OPTION_MODEL, 'claude-sonnet-4-6' );
    }

    public static function available_models(): array {
        return [
            'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 (rápido)',
            'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (recomendado)',
            'claude-opus-4-6'           => 'Claude Opus 4.6 (más potente)',
        ];
    }
}
