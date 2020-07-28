<?php
/**
 * Define a custom DB table.
 *
 * @package Screenfeed/autowpdb-example-plugin
 */

declare( strict_types=1 );

namespace Screenfeed\AutoWPDBExamplePlugin;

use Screenfeed\AutoWPDB\TableDefinition\AbstractTableDefinition;

defined( 'ABSPATH' ) || exit; // @phpstan-ignore-line

/**
 * Class that defines our custom table.
 *
 * @since 0.1
 */
class CustomTable extends AbstractTableDefinition {

	/**
	 * Get the table version.
	 *
	 * @since 0.1
	 *
	 * @return int
	 */
	public function get_table_version(): int {
		return 102;
	}

	/**
	 * Get the table "short name", aka the unprefixed table name.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function get_table_short_name(): string {
		return 'autowpdb_example_custom_table';
	}

	/**
	 * Tell if the table is the same for each site of a Multisite.
	 *
	 * @since 0.1
	 *
	 * @return bool True if the table is common to all sites. False if each site has its own table.
	 */
	public function is_table_global(): bool {
		return true;
	}

	/**
	 * Get the name of the primary column.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function get_primary_key(): string {
		return 'file_id';
	}

	/**
	 * Get the column placeholders.
	 *
	 * @since 0.1
	 *
	 * @return array<string>
	 */
	public function get_column_placeholders(): array {
		return [
			'file_id'   => '%d',
			'file_date' => '%s',
			'path'      => '%s',
			'mime_type' => '%s',
			'modified'  => '%d',
			'width'     => '%d',
			'height'    => '%d',
			'file_size' => '%d',
			'status'    => '%s',
			'error'     => '%s',
			'data'      => '%s',
		];
	}

	/**
	 * Default column values.
	 *
	 * @since 0.1
	 *
	 * @return array<mixed>
	 */
	public function get_column_defaults(): array {
		return [
			'file_id'   => 0,
			'file_date' => '0000-00-00 00:00:00',
			'path'      => '',
			'mime_type' => '',
			'modified'  => 0,
			'width'     => 0,
			'height'    => 0,
			'file_size' => 0,
			'status'    => null,
			'error'     => null,
			'data'      => [],
		];
	}

	/**
	 * Get the query to create the table fields.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function get_table_schema(): string {
		return "
			file_id bigint(20) unsigned NOT NULL auto_increment,
			file_date datetime NOT NULL default '0000-00-00 00:00:00',
			path varchar(191) NOT NULL default '',
			mime_type varchar(100) NOT NULL default '',
			modified tinyint(1) unsigned NOT NULL default 0,
			width smallint(2) unsigned NOT NULL default 0,
			height smallint(2) unsigned NOT NULL default 0,
			file_size int(4) unsigned NOT NULL default 0,
			status varchar(20) default NULL,
			error varchar(255) default NULL,
			data longtext default NULL,
			PRIMARY KEY  (file_id),
			UNIQUE KEY path (path),
			KEY status (status),
			KEY modified (modified)";
	}
}
