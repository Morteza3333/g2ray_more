<?php

class FWS_API {
	public function register_routes() {
		register_rest_route( 'fws/v1', '/winners', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_winners' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_winner' ),
				'permission_callback' => array( $this, 'update_permissions_check' ),
			),
		) );

		register_rest_route( 'fws/v1', '/winners/(?P<id>\d+)', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_winner' ),
				'permission_callback' => array( $this, 'update_permissions_check' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_winner' ),
				'permission_callback' => array( $this, 'update_permissions_check' ),
			),
		) );

		register_rest_route( 'fws/v1', '/settings', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'update_settings' ),
			'permission_callback' => array( $this, 'update_permissions_check' ),
		) );

		register_rest_route( 'fws/v1', '/reorder', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'reorder_winners' ),
			'permission_callback' => array( $this, 'update_permissions_check' ),
		) );
	}

	public function get_permissions_check() {
		return current_user_can( 'edit_posts' );
	}

	public function update_permissions_check() {
		return current_user_can( 'manage_options' );
	}

	public function get_winners( $request ) {
		$winners = get_posts( array(
			'post_type'      => 'winner_entry',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );

		$data = array();
		foreach ( $winners as $winner ) {
			$gallery_ids = get_post_meta( $winner->ID, '_winner_gallery', true );
			$gallery_urls = array();
			if ( ! empty( $gallery_ids ) ) {
				$ids = explode( ',', $gallery_ids );
				foreach ( $ids as $id ) {
					$url = wp_get_attachment_url( $id );
					if ( $url ) {
						$gallery_urls[] = $url;
					}
				}
			}

			$data[] = array(
				'id'               => $winner->ID,
				'title'            => $winner->post_title,
				'rank'             => get_post_meta( $winner->ID, '_winner_rank', true ),
				'photographer'     => get_post_meta( $winner->ID, '_winner_photographer', true ),
				'instagram'        => get_post_meta( $winner->ID, '_winner_instagram', true ),
				'best_of_festival' => get_post_meta( $winner->ID, '_best_of_festival', true ) === 'yes',
				'gallery'          => $gallery_ids,
				'gallery_urls'     => $gallery_urls,
				'thumbnail'        => get_the_post_thumbnail_url( $winner->ID, 'large' ),
				'category'         => wp_get_post_terms( $winner->ID, 'winner_category', array( 'fields' => 'slugs' ) ),
			);
		}

		return rest_ensure_response( $data );
	}

	public function create_winner( $request ) {
		$params = $request->get_json_params();
		$post_id = wp_insert_post( array(
			'post_title'  => sanitize_text_field( $params['title'] ?? 'برنده جدید' ),
			'post_type'   => 'winner_entry',
			'post_status' => 'publish',
		) );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( ! empty( $params['category'] ) ) {
			wp_set_object_terms( $post_id, $params['category'], 'winner_category' );
		}

		return rest_ensure_response( array( 'id' => $post_id ) );
	}

	public function update_winner( $request ) {
		$id = $request['id'];
		$params = $request->get_json_params();

		$data = array( 'ID' => $id );
		if ( isset( $params['title'] ) ) $data['post_title'] = sanitize_text_field( $params['title'] );

		wp_update_post( $data );

		if ( isset( $params['rank'] ) ) update_post_meta( $id, '_winner_rank', sanitize_text_field( $params['rank'] ) );
		if ( isset( $params['photographer'] ) ) update_post_meta( $id, '_winner_photographer', sanitize_text_field( $params['photographer'] ) );
		if ( isset( $params['instagram'] ) ) update_post_meta( $id, '_winner_instagram', sanitize_text_field( $params['instagram'] ) );
		if ( isset( $params['gallery'] ) ) update_post_meta( $id, '_winner_gallery', sanitize_text_field( $params['gallery'] ) );
		if ( isset( $params['thumbnail_id'] ) ) set_post_thumbnail( $id, $params['thumbnail_id'] );
		if ( isset( $params['best_of_festival'] ) ) update_post_meta( $id, '_best_of_festival', $params['best_of_festival'] ? 'yes' : 'no' );

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_winner( $request ) {
		$id = $request['id'];
		wp_delete_post( $id, true );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function update_settings( $request ) {
		$params = $request->get_json_params();
		$settings = get_option( 'fws_settings', array() );

		$new_settings = array_merge( $settings, $params );
		update_option( 'fws_settings', $new_settings );

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function reorder_winners( $request ) {
		$params = $request->get_json_params();
		$order = $params['order'];

		foreach ( $order as $index => $id ) {
			wp_update_post( array(
				'ID'         => $id,
				'menu_order' => $index,
			) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}
}
