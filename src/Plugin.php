<?php
/**
 * Main plugin class.
 *
 * @package Screenfeed/autowpdb-example-plugin
 */

declare( strict_types=1 );

namespace Screenfeed\AutoWPDBExamplePlugin;

use Screenfeed\AutoWPDB\Table;
use Screenfeed\AutoWPDB\TableUpgrader;
use stdClass;

defined( 'ABSPATH' ) || exit; // @phpstan-ignore-line

/**
 * Class that defines our custom table.
 *
 * @since 0.1
 */
class Plugin {

	/**
	 * ID used in add_settings_error().
	 *
	 * @var   string
	 * @since 0.1
	 */
	const POST_ACTION_ID = 'autowpdbexample_action';

	/**
	 * A Table object.
	 *
	 * @var   Table
	 * @since 0.1
	 */
	protected $table;

	/**
	 * A TableUpgrader object.
	 *
	 * @var   TableUpgrader
	 * @since 0.1
	 */
	protected $upgrader;

	/**
	 * The result of requesting the whole table.
	 *
	 * @var   array<stdClass>|null
	 * @since 0.1
	 */
	protected $table_contents;

	/**
	 * Plugin init.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function init() {
		$this->table    = new Table( new CustomTable() );
		$this->upgrader = new TableUpgrader( $this->table );

		$this->upgrader->init();

		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_post_' . self::POST_ACTION_ID, [ $this, 'post_action' ] );
	}

	/** ----------------------------------------------------------------------------------------- */
	/** PAGE CONTENTS =========================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add a menu item in the admin menu if the DB table is ready.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page( 'Custom Table', 'Custom Table', 'manage_options', 'custom-table', [ $this, 'display_page_contents' ] );
	}

	/**
	 * Display the page contents.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function display_page_contents() {
		if ( ! $this->upgrader->table_is_ready() ) {
			// Page header.
			$this->display_error_page_header();
			return;
		}

		// Get everything that is in the table.
		$this->table_contents = ( new CRUD( $this->table->get_table_definition() ) )->get( [ '*' ], [], OBJECT_K );

		// Page header.
		$this->display_page_header();

		// Display buttons to add/delete entries.
		$this->display_page_actions();

		// Display what is in the table.
		$this->display_table_contents();
	}

	/**
	 * Display the page header.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function display_error_page_header() {
		global $title;

		printf( '<h1>%s</h1>', esc_html( $title ) );
		esc_html_e( 'Your custom table does not seem to be ready, something went wrong.', 'autowpdb-example-plugin' );

		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: 1 is the name of the DB table, 2 is an option name. */
					__( 'Your custom table "%1$s" does not seem to be ready, something went wrong. Its current version number is maybe stored in the (network?) option "%2$s".', 'autowpdb-example-plugin' ),
					$this->table->get_table_definition()->get_table_name(),
					$this->upgrader->get_db_version_option_name()
				)
			)
		);
	}

	/**
	 * Display the page header.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function display_page_header() {
		global $title;

		printf( '<h1>%s</h1>', esc_html( $title ) );

		settings_errors( self::POST_ACTION_ID );

		printf(
			'<p>%s</p>',
			esc_html(
				sprintf(
					/* translators: 1 is the name of the DB table, 2 is a version number, 3 is an option name. */
					__( 'Your custom table "%1$s" is ready and its current version is %2$d. This version number is stored in the (network?) option "%3$s".', 'autowpdb-example-plugin' ),
					$this->table->get_table_definition()->get_table_name(),
					$this->upgrader->get_db_version(),
					$this->upgrader->get_db_version_option_name()
				)
			)
		);
	}

	/**
	 * Display the page buttons.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function display_page_actions() {
		printf(
			'<form action="%s" method="post">',
			esc_url( admin_url( 'admin-post.php?action=' . self::POST_ACTION_ID ) )
		);

		echo '<input type="hidden" name="page" value="custom-table"/>';
		wp_nonce_field( self::POST_ACTION_ID );

		echo '<p class="submit">';
		submit_button(
			esc_html__( 'Add Entry', 'autowpdb-example-plugin' ),
			'primary',
			'add_entry',
			false
		);
		if ( ! empty( $this->table_contents ) ) {
			echo ' ';
			submit_button(
				esc_html__( 'Delete Oldest Entry', 'autowpdb-example-plugin' ),
				'',
				'delete_entry',
				false
			);
		}
		echo '</p>';
		echo '</form>';
	}

	/**
	 * Display the DB table contents.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function display_table_contents() {
		// Display what is in the table.
		printf(
			'<p>%s</p>',
			esc_html( __( 'Currently in the database:', 'autowpdb-example-plugin' ) )
		);

		if ( empty( $this->table_contents ) ) {
			esc_html_e( 'Nothing yet.', 'autowpdb-example-plugin' );
			return;
		}

		$table_contents = $this->dump_data( $this->table_contents );

		if ( strpos( $table_contents, '<pre' ) !== 0 ) {
			// Classic output.
			printf( '<pre>%s</pre>', esc_html( $table_contents ) );
			return;
		}

		// Xdebug.
		echo wp_kses(
			$table_contents,
			[
				'b'     => [],
				'font'  => [
					'color' => true,
				],
				'i'     => [],
				'pre'   => [
					'class' => true,
					'dir'   => true,
				],
				'small' => [],
			]
		);
	}

	/**
	 * Var_dump() without the line number.
	 *
	 * @since 0.1
	 *
	 * @param  mixed $data Any data.
	 * @return string
	 */
	public function dump_data( $data ): string { // phpcs:ignore NeutronStandard.Functions.TypeHint.NoArgumentType
		ob_start();
		call_user_func( 'var_dump', $data );
		$data = ob_get_contents();
		ob_end_clean();

		if ( empty( $data ) ) {
			return '';
		}

		$data = (string) preg_replace( '@<small>/.+\.php:\d+:</small>@U', '', $data );

		return trim( $data );
	}

	/** ----------------------------------------------------------------------------------------- */
	/** POST ACTION CALLBACK ==================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add or delete an entry.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function post_action() {
		check_ajax_referer( self::POST_ACTION_ID );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'autowpdb-example-plugin' ), '', 403 );
		}

		$add_entry    = filter_input( INPUT_POST, 'add_entry', FILTER_SANITIZE_STRING );
		$delete_entry = filter_input( INPUT_POST, 'delete_entry', FILTER_SANITIZE_STRING );

		if ( null !== $add_entry ) {
			// Insert a new entry into the table.
			$result = $this->action_insert_entry();

			$this->action_insert_entry_message( $result );
		} elseif ( null !== $delete_entry ) {
			// Delete the oldest entry.
			$result = $this->action_delete_entry();

			$this->action_delete_entry_message( $result );
		} else {
			$this->invalid_action_message();
		}

		set_transient( 'settings_errors', get_settings_errors( self::POST_ACTION_ID ), 30 );

		$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
		wp_safe_redirect( esc_url_raw( $goback ) );
		exit;
	}

	/**
	 * Add an entry.
	 *
	 * @since 0.1
	 *
	 * @return int The new entry ID.
	 */
	public function action_insert_entry(): int {
		return ( new CRUD( $this->table->get_table_definition() ) )->insert(
			[
				'file_date' => date_i18n( 'Y-m-d H:i:s' ),
				'path'      => '/foo/bar/' . wp_rand(),
				'mime_type' => 'image/png',
				'modified'  => 1,
				'width'     => 600,
				'height'    => 200,
				'file_size' => 4563,
				'data'      => [ 'foo', 'bar' ],
			]
		);
	}

	/**
	 * Delete the oldest entry.
	 *
	 * @since 0.1
	 *
	 * @return int The number of deleted entries.
	 */
	public function action_delete_entry(): int {
		return (int) ( new CRUD( $this->table->get_table_definition() ) )->delete_oldest_item();
	}

	/** ----------------------------------------------------------------------------------------- */
	/** POST ACTION MESSAGES ==================================================================== */
	/** ----------------------------------------------------------------------------------------- */

	/**
	 * Add entry insertion message.
	 *
	 * @since 0.1
	 *
	 * @param  int $row_id The new entry ID.
	 * @return void
	 */
	public function action_insert_entry_message( int $row_id ) {
		if ( $row_id <= 0 ) {
			add_settings_error( self::POST_ACTION_ID, 'insertion_failed', __( 'Entry insertion failed.', 'autowpdb-example-plugin' ) );
			return;
		}

		add_settings_error(
			self::POST_ACTION_ID,
			'insertion_success',
			sprintf(
				/* translators: %s is a formatted number, dont use %d. */
				__( 'Entry %s successfully added.', 'autowpdb-example-plugin' ),
				number_format_i18n( $row_id )
			),
			'success'
		);
	}

	/**
	 * Add entry deletion message.
	 *
	 * @since 0.1
	 *
	 * @param  int $nbr_deleted The number of deleted entries.
	 * @return void
	 */
	public function action_delete_entry_message( int $nbr_deleted ) {
		if ( $nbr_deleted <= 0 ) {
			add_settings_error( self::POST_ACTION_ID, 'deletion_failed', __( 'Entry deletion failed.', 'autowpdb-example-plugin' ) );
			return;
		}

		add_settings_error(
			self::POST_ACTION_ID,
			'deletion_success',
			__( 'Oldest entry successfully deleted.', 'autowpdb-example-plugin' ),
			'success'
		);
	}

	/**
	 * Add an "invalid action" message.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function invalid_action_message() {
		add_settings_error( self::POST_ACTION_ID, 'invalid_action', __( 'Invalid action.', 'autowpdb-example-plugin' ) );
	}
}
