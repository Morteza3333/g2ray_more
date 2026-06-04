<?php

class FWS_Deactivator {
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
