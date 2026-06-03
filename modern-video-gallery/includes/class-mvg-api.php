<?php
/**
 * REST API Endpoints for the gallery.
 */
class MVG_API {
	public function register_routes() {
		register_rest_route( 'mvg/v1', '/videos', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_videos' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'mvg/v1', '/videos', array(
			'methods' => 'POST',
			'callback' => array( $this, 'add_video' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'mvg/v1', '/videos/(?P<id>\d+)', array(
			'methods' => 'POST', // Use POST for updates in WP REST API sometimes easier for compatibility
			'callback' => array( $this, 'update_video' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'mvg/v1', '/videos/(?P<id>\d+)', array(
			'methods' => 'DELETE',
			'callback' => array( $this, 'delete_video' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'mvg/v1', '/reorder', array(
			'methods' => 'POST',
			'callback' => array( $this, 'reorder_videos' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( 'mvg/v1', '/settings', array(
			'methods' => 'POST',
			'callback' => array( $this, 'save_settings' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	public function get_videos() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mvg_videos';
		$videos = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY menu_order ASC" );
		return rest_ensure_response( $videos );
	}

	public function add_video( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mvg_videos';
		$params = $request->get_params();

		$wpdb->insert( $table_name, array(
			'title'         => sanitize_text_field( $params['title'] ),
			'url'           => esc_url_raw( $params['url'] ),
			'thumbnail_url' => esc_url_raw( $params['thumbnail_url'] ),
			'type'          => sanitize_text_field( $params['type'] ),
			'is_featured'   => isset( $params['is_featured'] ) ? (int)$params['is_featured'] : 0,
		) );

		return rest_ensure_response( array( 'id' => $wpdb->insert_id ) );
	}

	public function update_video( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mvg_videos';
		$id = $request['id'];
		$params = $request->get_params();

		// If this is set as featured, unset others
		if ( isset( $params['is_featured'] ) && $params['is_featured'] == 1 ) {
			$wpdb->update( $table_name, array( 'is_featured' => 0 ), array( 'is_featured' => 1 ) );
		}

		$data = array();
		if ( isset( $params['title'] ) ) $data['title'] = sanitize_text_field( $params['title'] );
		if ( isset( $params['url'] ) ) $data['url'] = esc_url_raw( $params['url'] );
		if ( isset( $params['thumbnail_url'] ) ) $data['thumbnail_url'] = esc_url_raw( $params['thumbnail_url'] );
		if ( isset( $params['type'] ) ) $data['type'] = sanitize_text_field( $params['type'] );
		if ( isset( $params['is_featured'] ) ) $data['is_featured'] = (int)$params['is_featured'];

		$wpdb->update( $table_name, $data, array( 'id' => $id ) );

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_video( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mvg_videos';
		$id = $request['id'];
		$wpdb->delete( $table_name, array( 'id' => $id ) );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function reorder_videos( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'mvg_videos';
		$order = $request->get_param( 'order' ); // Array of IDs

		foreach ( $order as $index => $id ) {
			$wpdb->update( $table_name, array( 'menu_order' => $index ), array( 'id' => $id ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function save_settings( $request ) {
		$settings = $request->get_params();
		update_option( 'mvg_settings', $settings );
		return rest_ensure_response( array( 'success' => true ) );
	}
}
