<?php

class FWS_Settings {
	public static function get_settings() {
		$defaults = array(
			'primary_color'        => '#0073aa',
			'accent_color'         => '#ffb200',
			'bg_color'             => '#0a0a0b',
			'festival_title'       => '',
			'animation_intensity' => 'medium',
		);
		$settings = get_option( 'fws_settings', array() );
		return array_merge( $defaults, $settings );
	}

	public static function get_dynamic_css() {
		$settings = self::get_settings();
		$css = "
			:root {
				--fws-primary: " . esc_attr( $settings['primary_color'] ) . ";
				--fws-accent: " . esc_attr( $settings['accent_color'] ) . ";
				--fws-bg: " . esc_attr( $settings['bg_color'] ) . ";
			}
		";
		return $css;
	}
}
