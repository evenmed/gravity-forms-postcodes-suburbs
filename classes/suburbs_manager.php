<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Suburbs_List extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Suburb', 'wordpress' ), //singular name of the listed records
			'plural'   => __( 'Suburbs', 'wordpress' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}


	/**
	 * Retrieve suburbs data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_suburbs( $per_page = 20, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}gf_suburbs";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		} else {
            $sql .= ' ORDER BY suburb';
        }

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


	/**
	 * Delete a suburb.
	 *
	 * @param int $id suburb ID
	 */
	public static function delete_suburb( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}gf_suburbs",
			[ 'id' => $id ],
			[ '%d' ]
		);
	}


	/**
	 * Activate a suburb.
	 *
	 * @param int $id suburb ID
	 */
	public static function activate_suburb( $id ) {
		global $wpdb;
		$wpdb->update(
			"{$wpdb->prefix}gf_suburbs",
			[ 'active' => 1 ],
			[ 'id' => $id ],
			[ '%d' ],
			[ '%d' ]
		);
	}


	/**
	 * Deactivate a suburb.
	 *
	 * @param int $id suburb ID
	 */
	public static function deactivate_suburb( $id ) {
		global $wpdb;

		$wpdb->update(
			"{$wpdb->prefix}gf_suburbs",
			[ 'active' => 0 ],
			[ 'id' => $id ],
			[ '%d', '%d' ]
		);
	}


	/**
	 * Toggle (activate/deactivate) a suburb.
	 *
	 * @param int $id suburb ID
	 */
	public static function toggle_suburb( $id ) {
		global $wpdb;

        $active = $wpdb->get_var( 
            $wpdb->prepare( 
                "SELECT active 
                FROM {$wpdb->prefix}gf_suburbs 
                WHERE id = %d", 
                $id
            )
        );
        
		if ($active) {
            self::deactivate_suburb($id);
        } else {
            self::activate_suburb($id);
        }
        
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}gf_suburbs";

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no suburb data is available */
	public function no_items() {
		_e( 'No suburbs avaliable.', 'wordpress' );
	}


	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'active':
			case 'postcode':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-selected[]" value="%s" />', $item['id']
		);
	}


	/**
	 * Method for suburb column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_suburb( $item ) {

		$delete_nonce = wp_create_nonce( 'gfp_delete_suburb' );
		$toggle_nonce = wp_create_nonce( 'gfp_toggle_suburb' );

		$title = '<strong>' . $item['suburb'] . '</strong>';
        
        $activate_toggle = "";
        
        if ( !$item['active'] ) {
            $activate_toggle = __('Activate', 'wordpress');
        } else {
            $activate_toggle = __('Deactivate', 'wordpress');
        }

		$actions = [
			'delete' => sprintf( '<a href="?page=%s&action=%s&suburb=%s&_wpnonce=%s">%s</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce, __('Delete', 'wordpress') ),
			'toggle' => sprintf( '<a href="?page=%s&action=%s&suburb=%s&_wpnonce=%s">%s</a>', esc_attr( $_REQUEST['page'] ), 'toggle', absint( $item['id'] ), $toggle_nonce, $activate_toggle ),
		];

		return $title . $this->row_actions( $actions );
	}


	/**
	 * Method for 'active' column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_active( $item ) {
		if ( $item['active'] ) {
            return "Yes";
        } else {
            return "No";
        }
	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'suburb'    => __( 'Suburb', 'wordpress' ),
			'postcode' => __( 'Postcode', 'wordpress' ),
			'active'    => __( 'Active', 'wordpress' )
		];

		return $columns;
	}


	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'suburb' => array( 'suburb', true ),
			'postcode' => array( 'postcode', false ),
			'active' => array( 'active', false )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-deactivate' => 'Deactivate',
			'bulk-activate'   => 'Activate',
			'bulk-delete'     => 'Delete'
		];

		return $actions;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();
        
        $this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'suburbs_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_suburbs( $per_page, $current_page );
	}

	public function process_bulk_action() {

		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$ids = esc_sql( $_POST['bulk-selected'] );

			// loop over the array of record IDs and delete them
			foreach ( $ids as $id ) {
				self::delete_suburb( $id );
			}

		} else if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-activate' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-activate' )
		) {

			$ids = esc_sql( $_POST['bulk-selected'] );

			// loop over the array of record IDs and delete them
			foreach ( $ids as $id ) {
				self::activate_suburb( $id );
			}
            
		} else if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-deactivate' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-deactivate' )
		) {

			$ids = esc_sql( $_POST['bulk-selected'] );

			// loop over the array of record IDs and delete them
			foreach ( $ids as $id ) {
				self::deactivate_suburb( $id );
			}
            
		}
        
	}

}