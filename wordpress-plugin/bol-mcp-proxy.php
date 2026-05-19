<?php
/**
 * Plugin Name: BOL MCP Proxy
 * Description: Token-authenticated MCP endpoint that proxies to the mcp-adapter default server. Connect Claude.ai using a single URL — no headers, no config files.
 * Version: 1.4.0
 * Author: the AMerican
 * Requires at least: 6.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BOL_MCP_PROXY_NAMESPACE', 'bol-mcp/v1' );
define( 'BOL_MCP_PROXY_ROUTE',     'mcp' );

/**
 * Register REST routes at priority 16 — AFTER mcp-adapter initializes at 15.
 * This ensures wp_abilities_api_init has fired and all abilities are registered
 * before our proxy handles any requests.
 */
add_action( 'rest_api_init', function () {

    register_rest_route( BOL_MCP_PROXY_NAMESPACE, '/' . BOL_MCP_PROXY_ROUTE, [
        'methods'             => 'GET',
        'callback'            => 'bol_mcp_proxy_get',
        'permission_callback' => 'bol_mcp_proxy_check_token',
    ] );

    register_rest_route( BOL_MCP_PROXY_NAMESPACE, '/' . BOL_MCP_PROXY_ROUTE, [
        'methods'             => 'POST',
        'callback'            => 'bol_mcp_proxy_post',
        'permission_callback' => 'bol_mcp_proxy_check_token',
    ] );

    register_rest_route( BOL_MCP_PROXY_NAMESPACE, '/' . BOL_MCP_PROXY_ROUTE, [
        'methods'             => 'DELETE',
        'callback'            => 'bol_mcp_proxy_post',
        'permission_callback' => 'bol_mcp_proxy_check_token',
    ] );

}, 16 );

/**
 * Token check — URL param, Bearer, or X-API-Key.
 */
function bol_mcp_proxy_check_token( WP_REST_Request $request ): bool|WP_Error {
    $stored = defined( 'BOL_MCP_TOKEN' ) ? BOL_MCP_TOKEN : get_option( 'bol_mcp_token', '' );

    if ( empty( $stored ) ) {
        return new WP_Error( 'no_token', 'BOL_MCP_TOKEN not configured.', [ 'status' => 500 ] );
    }

    $candidates = [
        $request->get_param( 'token' ),
        trim( str_replace( 'Bearer ', '', (string) $request->get_header( 'authorization' ) ) ),
        $request->get_header( 'x-api-key' ),
    ];

    foreach ( $candidates as $candidate ) {
        if ( ! empty( $candidate ) && hash_equals( $stored, $candidate ) ) {
            return true;
        }
    }

    return new WP_Error( 'invalid_token', 'Invalid or missing token.', [ 'status' => 401 ] );
}

/**
 * POST/DELETE — authenticate as admin then proxy to mcp-adapter.
 */
function bol_mcp_proxy_post( WP_REST_Request $request ) {

    $admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );
    if ( empty( $admins ) ) {
        return new WP_REST_Response( [ 'error' => 'No admin user found.' ], 500 );
    }
    $admin_id = (int) $admins[0];

    // Force admin auth for the inner request
    $auth_filter = function() use ( $admin_id ) { return $admin_id; };
    add_filter( 'determine_current_user', $auth_filter, 99 );
    wp_set_current_user( $admin_id );

    // Build inner request forwarding all MCP headers
    $inner = new WP_REST_Request( $request->get_method(), '/mcp/mcp-adapter-default-server' );
    $inner->set_body( $request->get_body() );
    $inner->set_header( 'Content-Type', 'application/json' );

    foreach ( [ 'Mcp-Session-Id', 'Mcp-Protocol-Version', 'Accept', 'Last-Event-Id' ] as $h ) {
        $val = $request->get_header( $h );
        if ( ! empty( $val ) ) {
            $inner->set_header( $h, $val );
        }
    }

    $response = rest_do_request( $inner );
    remove_filter( 'determine_current_user', $auth_filter, 99 );

    $server = rest_get_server();
    $data   = $server->response_to_data( $response, false );
    $outer  = new WP_REST_Response( $data, $response->get_status() );

    // Forward MCP response headers back to client
    foreach ( $response->get_headers() as $key => $value ) {
        if ( stripos( $key, 'mcp-' ) === 0 ) {
            $outer->header( $key, is_array( $value ) ? implode( ', ', $value ) : $value );
        }
    }

    return $outer;
}

/**
 * GET — health check.
 */
function bol_mcp_proxy_get( WP_REST_Request $request ) {
    return new WP_REST_Response( [
        'status'   => 'ok',
        'service'  => 'bol-mcp-proxy',
        'version'  => '1.4.0',
        'endpoint' => rest_url( BOL_MCP_PROXY_NAMESPACE . '/' . BOL_MCP_PROXY_ROUTE ),
    ], 200 );
}

/**
 * Admin settings page.
 */
add_action( 'admin_menu', function () {
    add_options_page( 'BOL MCP Proxy', 'BOL MCP Proxy', 'manage_options', 'bol-mcp-proxy', 'bol_mcp_proxy_settings_page' );
} );

function bol_mcp_proxy_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['bol_mcp_save_token'] ) && check_admin_referer( 'bol_mcp_token_action' ) ) {
        $new_token = sanitize_text_field( $_POST['bol_mcp_token'] ?? '' );
        if ( ! empty( $new_token ) ) {
            update_option( 'bol_mcp_token', $new_token );
            echo '<div class="notice notice-success"><p>Token saved.</p></div>';
        }
    }

    if ( isset( $_POST['bol_mcp_regenerate'] ) && check_admin_referer( 'bol_mcp_token_action' ) ) {
        update_option( 'bol_mcp_token', wp_generate_password( 32, false ) );
        echo '<div class="notice notice-success"><p>Token regenerated.</p></div>';
    }

    $token         = defined( 'BOL_MCP_TOKEN' ) ? BOL_MCP_TOKEN : get_option( 'bol_mcp_token', '' );
    $connector_url = rest_url( BOL_MCP_PROXY_NAMESPACE . '/' . BOL_MCP_PROXY_ROUTE ) . '?token=' . urlencode( $token );
    ?>
    <div class="wrap">
        <h1>BOL MCP Proxy</h1>
        <p>Paste this URL into Claude.ai → Settings → Connectors → + Add</p>
        <table class="form-table">
            <tr>
                <th>Connector URL</th>
                <td>
                    <input type="text" value="<?php echo esc_attr( $connector_url ); ?>" readonly onclick="this.select()" style="width:100%;font-family:monospace" />
                </td>
            </tr>
            <tr>
                <th>Token</th>
                <td>
                    <?php if ( defined( 'BOL_MCP_TOKEN' ) ) : ?>
                        <code><?php echo esc_html( $token ); ?></code>
                    <?php else : ?>
                        <form method="post">
                            <?php wp_nonce_field( 'bol_mcp_token_action' ); ?>
                            <input type="text" name="bol_mcp_token" value="<?php echo esc_attr( $token ); ?>" class="regular-text" style="font-family:monospace" />
                            <input type="submit" name="bol_mcp_save_token" class="button button-primary" value="Save Token" />
                            <input type="submit" name="bol_mcp_regenerate" class="button" value="Regenerate" onclick="return confirm('Old URL will stop working.')" />
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

register_activation_hook( __FILE__, function () {
    if ( ! get_option( 'bol_mcp_token' ) && ! defined( 'BOL_MCP_TOKEN' ) ) {
        update_option( 'bol_mcp_token', wp_generate_password( 32, false ) );
    }
} );
