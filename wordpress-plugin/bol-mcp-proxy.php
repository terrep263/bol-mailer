<?php
/**
 * Plugin Name: BOL MCP Proxy
 * Description: Token-authenticated MCP endpoint that proxies to the mcp-adapter default server. Connect Claude.ai using a single URL — no headers, no config files.
 * Version: 1.1.0
 * Author: the AMerican
 * Requires at least: 6.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BOL_MCP_PROXY_NAMESPACE', 'bol-mcp/v1' );
define( 'BOL_MCP_PROXY_ROUTE',     'mcp' );

add_action( 'rest_api_init', function () {

    register_rest_route( BOL_MCP_PROXY_NAMESPACE, '/' . BOL_MCP_PROXY_ROUTE, [
        'methods'             => 'GET',
        'callback'            => 'bol_mcp_proxy_sse',
        'permission_callback' => 'bol_mcp_proxy_check_token',
    ] );

    register_rest_route( BOL_MCP_PROXY_NAMESPACE, '/' . BOL_MCP_PROXY_ROUTE, [
        'methods'             => 'POST',
        'callback'            => 'bol_mcp_proxy_handle',
        'permission_callback' => 'bol_mcp_proxy_check_token',
    ] );

    register_rest_route( BOL_MCP_PROXY_NAMESPACE, '/' . BOL_MCP_PROXY_ROUTE, [
        'methods'             => 'DELETE',
        'callback'            => 'bol_mcp_proxy_handle',
        'permission_callback' => 'bol_mcp_proxy_check_token',
    ] );

} );

function bol_mcp_proxy_check_token( WP_REST_Request $request ): bool|WP_Error {
    $stored_token = defined( 'BOL_MCP_TOKEN' )
        ? BOL_MCP_TOKEN
        : get_option( 'bol_mcp_token', '' );

    if ( empty( $stored_token ) ) {
        return new WP_Error( 'no_token_configured', 'BOL_MCP_TOKEN is not configured.', [ 'status' => 500 ] );
    }

    $url_token = $request->get_param( 'token' );
    if ( ! empty( $url_token ) && hash_equals( $stored_token, $url_token ) ) {
        return true;
    }

    $auth_header = $request->get_header( 'authorization' );
    if ( ! empty( $auth_header ) ) {
        $bearer = trim( str_replace( 'Bearer ', '', $auth_header ) );
        if ( hash_equals( $stored_token, $bearer ) ) {
            return true;
        }
    }

    $api_key = $request->get_header( 'x-api-key' );
    if ( ! empty( $api_key ) && hash_equals( $stored_token, $api_key ) ) {
        return true;
    }

    return new WP_Error( 'invalid_token', 'Invalid or missing token.', [ 'status' => 401 ] );
}

function bol_mcp_proxy_handle( WP_REST_Request $request ) {
    $admin = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
    if ( ! empty( $admin ) ) {
        wp_set_current_user( $admin[0]->ID );
    }

    $body   = $request->get_body();
    $method = $request->get_method();

    $proxy_request = new WP_REST_Request( $method, '/mcp/mcp-adapter-default-server' );
    $proxy_request->set_body( $body );
    $proxy_request->set_header( 'Content-Type', 'application/json' );

    // Forward all MCP protocol headers
    foreach ( [ 'mcp-session-id', 'last-event-id', 'accept' ] as $header ) {
        $value = $request->get_header( $header );
        if ( ! empty( $value ) ) {
            $proxy_request->set_header( $header, $value );
        }
    }

    $response = rest_do_request( $proxy_request );
    $server   = rest_get_server();
    $data     = $server->response_to_data( $response, false );

    $outer = new WP_REST_Response( $data, $response->get_status() );

    // Forward MCP response headers back to client
    foreach ( $response->get_headers() as $key => $value ) {
        if ( stripos( $key, 'mcp-' ) === 0 || strtolower( $key ) === 'location' ) {
            $outer->header( $key, $value );
        }
    }

    return $outer;
}

function bol_mcp_proxy_sse( WP_REST_Request $request ) {
    return new WP_REST_Response( [
        'status'   => 'ok',
        'service'  => 'bol-mcp-proxy',
        'version'  => '1.1.0',
        'endpoint' => rest_url( BOL_MCP_PROXY_NAMESPACE . '/' . BOL_MCP_PROXY_ROUTE ),
    ], 200 );
}

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
                        <p class="description">Defined in wp-config.php as <code>BOL_MCP_TOKEN</code>.</p>
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
