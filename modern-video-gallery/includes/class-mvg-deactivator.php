<?php
/**
 * Fired during plugin deactivation.
 */
class MVG_Deactivator {
	public static function deactivate() {
		// We might not want to delete the table automatically to prevent data loss.
	}
}
