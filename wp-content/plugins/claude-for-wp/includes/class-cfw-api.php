<?php
defined( 'ABSPATH' ) || exit;

class CFW_Api {

    const API_URL = 'https://api.anthropic.com/v1/messages';
    const VERSION  = '2023-06-01';

    /**
     * Simple send — no tools, returns text directly.
     */
    public static function send(
        string $user_message,
        string $system_prompt = '',
        int $max_tokens = 2048
    ): string|WP_Error {

        $result = self::call_api( [
            [ 'role' => 'user', 'content' => $user_message ],
        ], $system_prompt, $max_tokens );

        if ( is_wp_error( $result ) ) return $result;

        return self::extract_text( $result );
    }

    /**
     * Agentic send — includes tools, runs the tool-use loop until Claude
     * returns a final text response. Executes tools via CFW_Tools::execute().
     *
     * Returns an array:
     *   [
     *     'text'    => string,           // Claude's final response
     *     'actions' => array,            // list of tool calls made
     *   ]
     */
    public static function send_with_tools(
        array  $messages,
        string $system_prompt = '',
        int    $max_tokens = 4096,
        int    $max_iterations = 10
    ): array|WP_Error {

        $api_key = CFW_Settings::get_api_key();
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'API key no configurada. Ve a Claude for WP → Ajustes.' );
        }

        $tools   = CFW_Tools::definitions();
        $actions = [];
        $iteration = 0;

        while ( $iteration < $max_iterations ) {
            $iteration++;

            $body = [
                'model'      => CFW_Settings::get_model(),
                'max_tokens' => $max_tokens,
                'tools'      => $tools,
                'messages'   => $messages,
            ];

            if ( ! empty( $system_prompt ) ) {
                $body['system'] = $system_prompt;
            }

            $response = wp_remote_post( self::API_URL, [
                'timeout' => 90,
                'headers' => [
                    'Content-Type'      => 'application/json',
                    'x-api-key'         => $api_key,
                    'anthropic-version' => self::VERSION,
                ],
                'body' => wp_json_encode( $body ),
            ]);

            if ( is_wp_error( $response ) ) return $response;

            $code = wp_remote_retrieve_response_code( $response );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( $code !== 200 ) {
                $msg = $data['error']['message'] ?? "Error HTTP $code";
                return new WP_Error( 'api_error', $msg );
            }

            $stop_reason = $data['stop_reason'] ?? '';
            $content     = $data['content']     ?? [];

            // Append assistant message to history
            $messages[] = [ 'role' => 'assistant', 'content' => $content ];

            // If Claude is done, return the final text
            if ( $stop_reason === 'end_turn' ) {
                $text = '';
                foreach ( $content as $block ) {
                    if ( ( $block['type'] ?? '' ) === 'text' ) {
                        $text .= $block['text'];
                    }
                }
                return [ 'text' => $text, 'actions' => $actions ];
            }

            // If Claude wants to use tools
            if ( $stop_reason === 'tool_use' ) {
                $tool_results = [];

                foreach ( $content as $block ) {
                    if ( ( $block['type'] ?? '' ) !== 'tool_use' ) continue;

                    $tool_name  = $block['name'];
                    $tool_input = $block['input'] ?? [];
                    $tool_id    = $block['id'];

                    // Execute the tool
                    $result = CFW_Tools::execute( $tool_name, $tool_input );

                    // Log the action
                    $actions[] = [
                        'tool'   => $tool_name,
                        'input'  => $tool_input,
                        'result' => $result,
                    ];

                    $tool_results[] = [
                        'type'        => 'tool_result',
                        'tool_use_id' => $tool_id,
                        'content'     => wp_json_encode( $result ),
                    ];
                }

                // Send tool results back to Claude
                $messages[] = [ 'role' => 'user', 'content' => $tool_results ];
                continue;
            }

            // Unexpected stop reason
            break;
        }

        return new WP_Error( 'max_iterations', 'Se alcanzó el límite de iteraciones del agente.' );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function call_api( array $messages, string $system, int $max_tokens ): array|WP_Error {
        $api_key = CFW_Settings::get_api_key();

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'API key no configurada. Ve a Claude for WP → Ajustes.' );
        }

        $body = [
            'model'      => CFW_Settings::get_model(),
            'max_tokens' => $max_tokens,
            'messages'   => $messages,
        ];

        if ( ! empty( $system ) ) $body['system'] = $system;

        $response = wp_remote_post( self::API_URL, [
            'timeout' => 60,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => self::VERSION,
            ],
            'body' => wp_json_encode( $body ),
        ]);

        if ( is_wp_error( $response ) ) return $response;

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $msg = $data['error']['message'] ?? "Error HTTP $code";
            return new WP_Error( 'api_error', $msg );
        }

        return $data;
    }

    private static function extract_text( array $data ): string {
        foreach ( $data['content'] ?? [] as $block ) {
            if ( ( $block['type'] ?? '' ) === 'text' ) return $block['text'];
        }
        return '';
    }
}
