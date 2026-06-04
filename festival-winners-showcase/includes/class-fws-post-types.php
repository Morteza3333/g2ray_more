<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FWS_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
	}

	public static function register_post_types() {
		$labels = array(
			'name'               => _x( 'Winners', 'post type general name', 'festival-winners-showcase' ),
			'singular_name'      => _x( 'Winner', 'post type singular name', 'festival-winners-showcase' ),
			'menu_name'          => _x( 'Festival Winners', 'admin menu', 'festival-winners-showcase' ),
			'name_admin_bar'     => _x( 'Winner', 'add new on admin bar', 'festival-winners-showcase' ),
			'add_new'            => _x( 'Add New', 'winner', 'festival-winners-showcase' ),
			'add_new_item'       => __( 'Add New Winner', 'festival-winners-showcase' ),
			'new_item'           => __( 'New Winner', 'festival-winners-showcase' ),
			'edit_item'          => __( 'Edit Winner', 'festival-winners-showcase' ),
			'view_item'          => __( 'View Winner', 'festival-winners-showcase' ),
			'all_items'          => __( 'All Winners', 'festival-winners-showcase' ),
			'search_items'       => __( 'Search Winners', 'festival-winners-showcase' ),
			'not_found'          => __( 'No winners found.', 'festival-winners-showcase' ),
			'not_found_in_trash' => __( 'No winners found in Trash.', 'festival-winners-showcase' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'winner' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'       => true,
		);

		register_post_type( 'winner_entry', $args );
	}

	public static function register_taxonomies() {
		$labels = array(
			'name'              => _x( 'Winner Categories', 'taxonomy general name', 'festival-winners-showcase' ),
			'singular_name'     => _x( 'Winner Category', 'taxonomy singular name', 'festival-winners-showcase' ),
			'search_items'      => __( 'Search Categories', 'festival-winners-showcase' ),
			'all_items'         => __( 'All Categories', 'festival-winners-showcase' ),
			'parent_item'       => __( 'Parent Category', 'festival-winners-showcase' ),
			'parent_item_colon' => __( 'Parent Category:', 'festival-winners-showcase' ),
			'edit_item'         => __( 'Edit Category', 'festival-winners-showcase' ),
			'update_item'       => __( 'Update Category', 'festival-winners-showcase' ),
			'add_new_item'      => __( 'Add New Category', 'festival-winners-showcase' ),
			'new_item_name'     => __( 'New Category Name', 'festival-winners-showcase' ),
			'menu_name'         => __( 'Winner Categories', 'festival-winners-showcase' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'winner-category' ),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'winner_category', array( 'winner_entry' ), $args );
	}
}
FWS_Post_Types::init();
