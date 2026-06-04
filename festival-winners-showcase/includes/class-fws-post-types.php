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
			'name'               => _x( 'برندگان', 'post type general name', 'festival-winners-showcase' ),
			'singular_name'      => _x( 'برنده', 'post type singular name', 'festival-winners-showcase' ),
			'menu_name'          => _x( 'برندگان جشنواره', 'admin menu', 'festival-winners-showcase' ),
			'name_admin_bar'     => _x( 'برنده', 'add new on admin bar', 'festival-winners-showcase' ),
			'add_new'            => _x( 'افزودن جدید', 'winner', 'festival-winners-showcase' ),
			'add_new_item'       => __( 'افزودن برنده جدید', 'festival-winners-showcase' ),
			'new_item'           => __( 'برنده جدید', 'festival-winners-showcase' ),
			'edit_item'          => __( 'ویرایش برنده', 'festival-winners-showcase' ),
			'view_item'          => __( 'مشاهده برنده', 'festival-winners-showcase' ),
			'all_items'          => __( 'همه برندگان', 'festival-winners-showcase' ),
			'search_items'       => __( 'جستجوی برندگان', 'festival-winners-showcase' ),
			'not_found'          => __( 'برنده‌ای یافت نشد.', 'festival-winners-showcase' ),
			'not_found_in_trash' => __( 'برنده‌ای در زباله‌دان یافت نشد.', 'festival-winners-showcase' )
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
			'name'              => _x( 'دسته‌بندی برندگان', 'taxonomy general name', 'festival-winners-showcase' ),
			'singular_name'     => _x( 'دسته‌بندی برنده', 'taxonomy singular name', 'festival-winners-showcase' ),
			'search_items'      => __( 'جستجوی دسته‌بندی‌ها', 'festival-winners-showcase' ),
			'all_items'         => __( 'همه دسته‌بندی‌ها', 'festival-winners-showcase' ),
			'parent_item'       => __( 'دسته‌بندی مادر', 'festival-winners-showcase' ),
			'parent_item_colon' => __( 'دسته‌بندی مادر:', 'festival-winners-showcase' ),
			'edit_item'         => __( 'ویرایش دسته‌بندی', 'festival-winners-showcase' ),
			'update_item'       => __( 'بروزرسانی دسته‌بندی', 'festival-winners-showcase' ),
			'add_new_item'      => __( 'افزودن دسته‌بندی جدید', 'festival-winners-showcase' ),
			'new_item_name'     => __( 'نام دسته‌بندی جدید', 'festival-winners-showcase' ),
			'menu_name'         => __( 'دسته‌بندی‌ها', 'festival-winners-showcase' ),
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
