<?php
/*
Plugin Name: WPDeCS
Description: The WPDeCS consumes the DeCS service and append into posts or pages in your WP.
Version: 0.1
Author: Moacir Moda - BIREME/OPAS/OMS
Author URI: http://github.com/moacirmoda
*/

define('WPDECS_URL', plugins_url() . "/wpdecs");

include_once 'functions.php';

add_action('admin_menu', 'my_plugin_menu');
function my_plugin_menu() {
	add_options_page(__('WPDeCS Options'), 'WPDeCS', 'manage_options', 'wpdecs-options.php', 'wp_decs_options_call');
}
function wp_decs_options_call() {
	include "wpdecs-options.php";
}

$wpdecs_post_types = get_option('wpdecs_post_types');

// register the meta box
add_action( 'add_meta_boxes', 'decs_metabox' );
function decs_metabox() {
    global $wpdecs_post_types;

    if($wpdecs_post_types) {

        foreach($wpdecs_post_types as $post_type) {
            add_meta_box(
                'decs_id',          // this is HTML id of the box on edit screen
                'DeCS',    // title of the box
                'decs_metabox_content',   // function to be called to display the checkboxes, see the function below
                $post_type,        // on which edit screen the box should appear
                'normal',      // part of page where the box should appear
                'default'      // priority of the box
            );
        }
    }
}

// TAXONOMY
// hook into the init action and call create_wpdecs_taxonomies when it fires
add_action( 'init', 'create_wpdecs_taxonomies', 0 );

// create two taxonomies, genres and writers for the post type "book"
function create_wpdecs_taxonomies() {
    global $wpdecs_post_types;

    // Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name'              => _x( __('DeCS Term'), 'taxonomy general name' ),
        'singular_name'     => _x( __('DeCS Terms'), 'taxonomy singular name' ),
        'search_items'      => __( 'Search Terms' ),
        'all_items'         => __( 'All Terms' ),
        'parent_item'       => __( 'Parent Term' ),
        'parent_item_colon' => __( 'Parent Term:' ),
        'edit_item'         => __( 'Edit Term' ),
        'update_item'       => __( 'Update Term' ),
        'add_new_item'      => __( 'Add New Term' ),
        'new_item_name'     => __( 'New Term Name' ),
        'menu_name'         => __( 'Term' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'decs' ),
    );

    register_taxonomy( 'decs', $wpdecs_post_types, $args );
}

// display the metabox
function decs_metabox_content( $post ) {

    $post_id = $post->ID;
    
    // nonce field for security check, you can have the same
    // nonce field for all your meta boxes of same plugin
    wp_nonce_field( plugin_basename( __FILE__ ), 'myplugin_nonce' );

    include "metabox.php";
}

// save data from checkboxes
add_action( 'save_post', 'decs_metabox_data', 10, 2 );
function decs_metabox_data($post_id, $post) {

    // check if this isn't an auto save
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    // security check
    if ( !wp_verify_nonce( $_POST['myplugin_nonce'], plugin_basename( __FILE__ ) ) )
        return;

    // die(var_dump($_POST));
    $terms = array();
    if(isset($_POST['wpdecs_terms'])) {
        $terms = $_POST['wpdecs_terms'];
    }

    // die(print_r($terms));

    // atualizando arvore de termos
    if(!get_post_meta($post->ID, 'wpdecs_terms', true)) {
        
        $return = add_post_meta($post->ID, 'wpdecs_terms', $terms, true);

        // ATENÇÃO: quando muda a estrutura do array, é preciso dar um add EM SEGUIDA um update.
        if(!$return) {
            update_post_meta($post->ID, 'wpdecs_terms', $terms);
        }
    } else {
        update_post_meta($post->ID, 'wpdecs_terms', $terms);
    }

    $terms_names = array();
    foreach($terms as $term) {
        $terms_names[] = $term['term'];
    }
    $insert_terms = wp_set_object_terms( $post_id, $terms_names, 'decs');
    
    return $post_id;
}