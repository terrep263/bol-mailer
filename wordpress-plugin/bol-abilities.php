<?php
/**
 * Plugin Name: BOL Abilities
 * Description: Registers Book of Lies WordPress abilities for the mcp-adapter.
 * Version: 2.0.0
 * Author: the AMerican
 * Requires at least: 6.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Step 1: Register the BOL category FIRST.
 * wp_abilities_api_categories_init fires before wp_abilities_api_init.
 */
add_action( 'wp_abilities_api_categories_init', function () {
    if ( ! function_exists( 'wp_register_ability_category' ) ) return;
    wp_register_ability_category( 'bol', [
        'label'       => 'Book of Lies',
        'description' => 'Abilities for managing The Book of Lies WordPress site.',
    ] );
} );

/**
 * Step 2: Register abilities on wp_abilities_api_init.
 * This is the ONLY hook where wp_register_ability() works.
 * Calling it on init, rest_api_init, or any other hook silently returns null.
 */
add_action( 'wp_abilities_api_init', function () {
    if ( ! function_exists( 'wp_register_ability' ) ) return;

    // ── 1. AUTOBLOG: Read queue ────────────────────────────────────────────
    wp_register_ability( 'bol/autoblog-queue', [
        'label'       => 'Autoblog Queue',
        'description' => 'Read the BOL autoblog queue. Filter by status (queued/processing/published/failed/all) and limit.',
        'category'    => 'bol',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'status' => [ 'type' => 'string', 'enum' => ['queued','processing','published','failed','all'], 'default' => 'all' ],
                'limit'  => [ 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ],
            ],
        ],
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        'execute_callback' => function ( $input ) {
            global $wpdb;
            $table  = $wpdb->prefix . 'bol_autoblog_queue';
            $status = $input['status'] ?? 'all';
            $limit  = intval( $input['limit'] ?? 20 );
            $where  = ( $status !== 'all' ) ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';
            $rows   = $wpdb->get_results( "SELECT id, title, keyword, category_id, status, scheduled_at, post_id, error_message, created_at FROM {$table} {$where} ORDER BY id DESC LIMIT {$limit}" );
            return [ 'total' => count( $rows ), 'items' => $rows ];
        },
        'meta' => [ 'mcp' => [ 'public' => true ] ],
    ] );

    // ── 2. AUTOBLOG: Add to queue ──────────────────────────────────────────
    wp_register_ability( 'bol/autoblog-queue-add', [
        'label'       => 'Add to Autoblog Queue',
        'description' => 'Add articles to the BOL autoblog queue. Faith=4, Love=5, Money=6, Relationships=7, Institutions=9.',
        'category'    => 'bol',
        'input_schema' => [
            'type'       => 'object',
            'required'   => ['items'],
            'properties' => [
                'items' => [ 'type' => 'array', 'items' => [
                    'type' => 'object', 'required' => ['title','category_id'],
                    'properties' => [
                        'title'        => [ 'type' => 'string' ],
                        'keyword'      => [ 'type' => 'string' ],
                        'category_id'  => [ 'type' => 'integer' ],
                        'scheduled_at' => [ 'type' => 'string' ],
                    ],
                ]],
            ],
        ],
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        'execute_callback' => function ( $input ) {
            global $wpdb;
            $table = $wpdb->prefix . 'bol_autoblog_queue';
            $ids   = [];
            foreach ( $input['items'] as $item ) {
                $wpdb->insert( $table, [
                    'title'        => sanitize_text_field( $item['title'] ),
                    'keyword'      => sanitize_text_field( $item['keyword'] ?? '' ),
                    'category_id'  => intval( $item['category_id'] ),
                    'status'       => 'queued',
                    'scheduled_at' => $item['scheduled_at'] ?? null,
                    'created_at'   => current_time( 'mysql' ),
                ] );
                $ids[] = $wpdb->insert_id;
            }
            return [ 'queued' => count( $ids ), 'ids' => $ids ];
        },
        'meta' => [ 'mcp' => [ 'public' => true ] ],
    ] );

    // ── 3. AUTOBLOG: Trigger queue ─────────────────────────────────────────
    wp_register_ability( 'bol/autoblog-trigger', [
        'label'       => 'Trigger Autoblog Queue',
        'description' => 'Manually fire the autoblog queue processor immediately.',
        'category'    => 'bol',
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        'execute_callback' => function ( $input ) {
            if ( class_exists( 'BOL_Scheduler' ) ) {
                BOL_Scheduler::process_queue();
                return [ 'triggered' => true, 'message' => 'Queue processor fired.' ];
            }
            return [ 'triggered' => false, 'message' => 'BOL_Scheduler not found.' ];
        },
        'meta' => [ 'mcp' => [ 'public' => true ] ],
    ] );

    // ── 4. AUTOBLOG: Clear failed ──────────────────────────────────────────
    wp_register_ability( 'bol/autoblog-clear-failed', [
        'label'       => 'Clear Failed Autoblog Items',
        'description' => 'Delete all failed items from the autoblog queue.',
        'category'    => 'bol',
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        'execute_callback' => function ( $input ) {
            global $wpdb;
            $deleted = $wpdb->delete( $wpdb->prefix . 'bol_autoblog_queue', [ 'status' => 'failed' ], [ '%s' ] );
            return [ 'deleted' => intval( $deleted ) ];
        },
        'meta' => [ 'mcp' => [ 'public' => true ] ],
    ] );

    // ── 5. SITE: Active plugins ────────────────────────────────────────────
    wp_register_ability( 'bol/site-plugins', [
        'label'       => 'Active Plugins',
        'description' => 'List all active WordPress plugins on thebookoflies.shop.',
        'category'    => 'bol',
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        'execute_callback' => function ( $input ) {
            $active  = get_option( 'active_plugins', [] );
            $plugins = [];
            foreach ( $active as $f ) {
                $d = get_plugin_data( WP_PLUGIN_DIR . '/' . $f );
                $plugins[] = [ 'file' => $f, 'name' => $d['Name'], 'version' => $d['Version'] ];
            }
            return [ 'total' => count( $plugins ), 'plugins' => $plugins ];
        },
        'meta' => [ 'mcp' => [ 'public' => true ] ],
    ] );

    // ── 6. SITE: Cron schedule ─────────────────────────────────────────────
    wp_register_ability( 'bol/site-cron', [
        'label'       => 'Cron Schedule',
        'description' => 'List all WordPress cron events and their next run times.',
        'category'    => 'bol',
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        'execute_callback' => function ( $input ) {
            $crons = _get_cron_array();
            $events = [];
            foreach ( $crons as $ts => $hooks ) {
                foreach ( $hooks as $hook => $data ) {
                    $events[] = [ 'hook' => $hook, 'next_run' => date( 'Y-m-d H:i:s', $ts ), 'in' => human_time_diff( $ts ) ];
                }
            }
            usort( $events, fn( $a, $b ) => strcmp( $a['next_run'], $b['next_run'] ) );
            return [ 'total' => count( $events ), 'events' => $events ];
        },
        'meta' => [ 'mcp' => [ 'public' => true ] ],
    ] );

    // ── 7. POSTS: Recent posts ─────────────────────────────────────────────
    wp_register_ability( 'bol/posts-recent', [
        'label'       => 'Recent Posts',
        'description' => 'Get recent posts with category, status, and word count.',
        'category'    => 'bol',
        'input_schema' => [
            'type'       => 'object',
            'properties' => [
                'category_id' => [ 'type' => 'integer' ],
                'status'      => [ 'type' => 'string', 'default' => 'publish' ],
                'limit'       => [ 'type' => 'integer', 'default' => 10 ],
            ],
        ],
        'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
        'execute_callback' => function ( $input ) {
            $args = [ 'numberposts' => min( intval( $input['limit'] ?? 10 ), 50 ), 'post_status' => $input['status'] ?? 'publish' ];
            if ( ! empty( $input['category_id'] ) ) $args['category'] = intval( $input['category_id'] );
            $posts  = get_posts( $args );
            $result = [];
            foreach ( $posts as $post ) {
                $cats     = get_the_category( $post->ID );
                $result[] = [
                    'id'         => $post->ID,
                    'title'      => $post->post_title,
                    'status'     => $post->post_status,
                    'date'       => $post->post_date,
                    'categories' => array_map( fn( $c ) => [ 'id' => $c->term_id, 'name' => $c->name ], $cats ),
                    'word_count' => str_word_count( strip_tags( $post->post_content ) ),
                    'url'        => get_permalink( $post->ID ),
                ];
            }
            return [ 'total' => count( $result ), 'posts' => $result ];
        },
        'meta' => [ 'mcp' => [ 'public' => true ] ],
    ] );

    // ── 8. DB: Read-only query ─────────────────────────────────────────────
    wp_register_ability( 'bol/db-query', [
        'label'       => 'Database Read Query',
        'description' => 'Run a SELECT query against the WordPress database.',
        'category'    => 'bol',
        'input_schema' => [
            'type'       => 'object',
            'required'   => ['query'],
            'properties' => [ 'query' => [ 'type' => 'string' ] ],
        ],
        'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        'execute_callback' => function ( $input ) {
            global $wpdb;
            $query = trim( $input['query'] );
            if ( ! preg_match( '/^SELECT\s/i', $query ) ) return [ 'error' => 'Only SELECT queries permitted.' ];
            $rows = $wpdb->get_results( $query, ARRAY_A );
            return [ 'total' => count( $rows ), 'rows' => $rows ];
        },
        'meta' => [ 'mcp' => [ 'public' => true ] ],
    ] );

} );
