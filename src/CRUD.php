<?php
/**
 * Class that contains custom CRUD methods.
 *
 * @package Screenfeed/autowpdb-example-plugin
 */

declare( strict_types=1 );

namespace Screenfeed\AutoWPDBExamplePlugin;

use Screenfeed\AutoWPDB\CRUD\Basic;
use Screenfeed\AutoWPDB\DBUtilities;
use stdClass;

defined( 'ABSPATH' ) || exit; // @phpstan-ignore-line

/**
 * Class to interact with the DB table.
 *
 * @since 0.1
 * @uses  $GLOBALS['wpdb']
 */
class CRUD extends Basic {

	/**
	 * Tell if the table is empty or not.
	 *
	 * @since 0.1
	 *
	 * @return bool True if the table contains at least one row.
	 */
	public function has_items(): bool {
		global $wpdb;

		$column     = esc_sql( $this->table->get_primary_key() );
		$table_name = $this->table->get_table_name();

		return (bool) $wpdb->get_var( "SELECT $column FROM $table_name LIMIT 1;" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Retrieve a row by the primary key value.
	 *
	 * @since 0.1
	 *
	 * @param  int $prim_key_value A primary key value.
	 * @return stdClass|null       A stdClass object. Null if no results are found.
	 */
	public function get_item( int $prim_key_value ) { // phpcs:ignore NeutronStandard.Functions.TypeHint.NoReturnType
		$results = $this->get(
			[ '*' ],
			[ $this->table->get_primary_key() => $prim_key_value ]
		);

		if ( empty( $results ) ) {
			return null;
		}

		return reset( $results );
	}

	/**
	 * Retrieve rows by the specified primary key values.
	 *
	 * @since 0.1
	 *
	 * @param  array<int> $prim_key_values An array of primary key values.
	 * @return array<stdClass>|null
	 */
	public function get_items( array $prim_key_values ) { // phpcs:ignore NeutronStandard.Functions.TypeHint.NoReturnType
		global $wpdb;

		$table_name      = $this->table->get_table_name();
		$where           = esc_sql( $this->table->get_primary_key() );
		$prim_key_values = DBUtilities::prepare_values_list( $prim_key_values );

		$results = $wpdb->get_results(
			"SELECT * FROM $table_name WHERE $where IN ( $prim_key_values );", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			OBJECT
		);

		if ( empty( $results ) ) {
			return $results;
		}

		return array_map( [ $this, 'cast_row' ], $results ); // @phpstan-ignore-line
	}

	/**
	 * Delete the oldest row from the table.
	 *
	 * @since 0.1
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete_oldest_item() { // phpcs:ignore NeutronStandard.Functions.TypeHint.NoReturnType
		global $wpdb;

		$table_name = $this->table->get_table_name();
		$where      = esc_sql( $this->table->get_primary_key() );

		$wpdb->check_current_query = false;

		return $wpdb->query( "DELETE FROM $table_name ORDER BY $where ASC LIMIT 1;" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
