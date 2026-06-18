<?php
defined( 'ABSPATH' ) || exit;

class CFW_Api {

    const API_URL = 'https://api.anthropic.com/v1/messages';
    const VERSION  = '2023-06-01';

    /**
     * Send a message to Claude and return the response text.
     *
     * @param string $user_message
     * @param string $system_prompt
     * @param int    $max_tokens
     * @return string|WP_Error
     */
    public static function send(
        string $user_message,
        string $system_prompt = '',
        int $max_tokens = 2048
    ): string|WP_Error {

        $api_key = CFW_Settings::get_api_key();

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'API key no configurada. Ve a Claude for WP → Ajustes.' );
        }

        $body = [
            'model'      => CFW_Settings::get_model(),
            'max_tokens' => $max_tokens,
            'messages'   => [
                [ 'role' => 'user', 'content' => $user_message ],
            ],
        ];

        if ( ! empty( $system_prompt ) ) {
            $body['system'] = $system_prompt;
        }

        $response = wp_remote_post( self::API_URL, [
            'timeout' => 60,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => self::VERSION,
            ],
            'body' => wp_json_encode( $body ),
        ]);

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $msg = $data['error']['message'] ?? "Error HTTP $code";
            return new WP_Error( 'api_error', $msg );
        }

        return $data['content'][0]['text'] ?? '';
    }
}
