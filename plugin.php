<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
Plugin Name: Gravity Forms Postcodes / Suburbs
Description: Plugin to create a custom GF field for postcodes and suburbs
Version: 1.0
Author: Emilio Venegas
Author URI: https://neegrum.com
License: GPL2
*/

/* Databse creation */
function gfp_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . "gf_suburbs";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id int(11) NOT NULL AUTO_INCREMENT,
      suburb varchar(100) NOT NULL,
      postcode varchar(10) NOT NULL,
      active tinyint(1) DEFAULT 1 NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'gfp_create_table' );

/* Admin page start */
include( plugin_dir_path( __FILE__ ) . 'classes/suburbs_manager.php');

/* Add suburbs page admin menu item */
add_action( 'admin_menu', 'gfp_menu' );
function gfp_menu() {
	$hook = add_menu_page(
        'Gravity Forms Suburbs',
        'GF Suburbs',
        'manage_options',
        'gf-suburbs',
        'gfp_adminpage_options',
        'dashicons-admin-multisite',
        19
    );

    // We put the action in here so the scripts and styles only load in the
    // page where they're used
    add_action( "load-$hook", 'screen_styles_scripts' );
}

function screen_styles_scripts() {
    add_action( 'admin_print_styles', 'gfp_adminpage_styles' );
    add_action( 'admin_enqueue_scripts', 'gfp_adminpage_scripts' );

    $option = 'per_page';
    $args   = [
        'label'   => 'Suburbs per page',
        'default' => 20,
        'option'  => 'suburbs_per_page'
    ];

    // Let the user adjust how many suburbs to display per page
    add_screen_option( $option, $args );

    $suburbs_object = new Suburbs_List();
}

function gfp_adminpage_styles() {
    echo '<link rel="stylesheet" href="' . plugin_dir_url( __FILE__ ) . 'css/styles.css" />';
}
function gfp_adminpage_scripts() {
    wp_enqueue_script(
        'gfp_admin_form',
        plugin_dir_url( __FILE__ ) . 'js/admin_form.js',
        array('jquery')
    );
}

/* Actual wp-admin suburbs page */
function gfp_adminpage_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
    $suburbs_object = new Suburbs_List();
    $suburbs_object->prepare_items();
    $invalid_postcode_link = get_option('invalid_postcode_link');
    ?>
    <div class="wrap">
        <h2>Gravity Forms Suburbs</h2>
        <div id="poststuff" class="metabox-holder has-right-sidebar">
            <div class="inner-sidebar">
                <div id="side-sortables" class="meta-box-sortables ui-sortable">
                    <!-- BOXES -->
                    <div class="postbox" id="boxid">
                        <div title="Click to toggle" class="handlediv"><br></div>
                        <h3 class="hndle"><span>Invalid Postcode Link</span></h3>
                        <div class="inside">
                            <form method="post" action="/wp-admin/admin-post.php">
                                <input type="hidden" name="action" value="edit_invalid_postcode_link" />
                                <input placeholder="https://example.com/" style="width: 100%;" name="link" value="<?php echo $invalid_postcode_link ?>" />
                                <br />
                                <input style="margin-top:10px;" type="submit" value="Save Changes" class="button-primary" name="Submit" />
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div id="post-body">
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <form action="<?php menu_page_url('gf-suburbs') ?>" method="post">
                            <?php
                            $suburbs_object->display(); ?>
                        </form>
                    </div>
                    <p id="add-buttons-wrap">
                        <button id="add-new-suburb" class="button-primary">Add new</button>
                        <button id="bulk-upload-suburb" class="button-primary">Bulk upload</button>
                    </p>
                    <form action="/wp-admin/admin-post.php" class="suburb-form" id="add-new-suburb-form" method="post">
                        <input type="hidden" name="action" value="single_suburb">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <td>Suburb</td>
                                    <td><input required name="suburb" /></td>
                                </tr>
                                <tr>
                                    <td>Postcode</td>
                                    <td><input required name="postcode" /></td>
                                </tr>
                                <tr>
                                    <td>
                                        <input class="button-primary" type="submit" name="single_submit" value="Submit" />
                                        <button class="button-secondary suburb-submit-cancel" type="button">Cancel</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                    <form action="/wp-admin/admin-post.php" class="suburb-form" id="bulk-upload-suburb-form" method="post">
                        <input type="hidden" name="action" value="bulk_suburb">
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <td>
                                        <textarea required placeholder="Suburb, postcode&#10;Suburb, postcode" rows="5" style="width:500px;" name="bulk-suburbs"></textarea>
                                        <p class="description">Enter 1 suburb / postcode combination per row in the following format: <b>suburb name, postcode</b></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input class="button-primary" type="submit" name="bulk_submit" value="Submit" />
                                        <button class="button-secondary suburb-submit-cancel" type="button">Cancel</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
            <br class="clear">
        </div>
	</div>
    <?php
}

add_action('admin_init', 'process_bulk_action');
function process_bulk_action() {

    // Single actions
    if ( isset($_GET['action']) ) {
        if ( 'delete' === $_GET['action'] ) {

            $nonce = esc_attr( $_REQUEST['_wpnonce'] );
            if ( ! wp_verify_nonce( $nonce, 'gfp_delete_suburb' ) ) {
                wp_die('Something went wrong.');
                die('Something went wrong.');
            }
            else {
                delete_suburb( absint( $_GET['suburb'] ) );
                wp_redirect( esc_url_raw(menu_page_url('gf-suburbs', 0)) );
                exit;
            }

        } else if ( 'toggle' === $_GET['action'] ) {

            $nonce = esc_attr( $_REQUEST['_wpnonce'] );
            if ( ! wp_verify_nonce( $nonce, 'gfp_toggle_suburb' ) ) {
                wp_die('Something went wrong.');
                die('Something went wrong.');
            }
            else {
                toggle_suburb( absint( $_GET['suburb'] ) );
                wp_redirect( esc_url_raw(menu_page_url('gf-suburbs', 0)) );
                exit;
            }

        }
    }

    // Bulk actions (delete is the only bulk action)
    if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
         || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
    ) {

        $delete_ids = esc_sql( $_POST['bulk-selected'] );

        // loop over the array of record IDs and delete them
        foreach ( $delete_ids as $id ) {
            delete_suburb( $id );
        }

        wp_redirect( esc_url_raw(menu_page_url('gf-suburbs', 0)) );
        exit;
    }
}

/* Delete a suburb from the db */
function delete_suburb( $id ) {
    global $wpdb;

    $wpdb->delete(
        "{$wpdb->prefix}gf_suburbs",
        [ 'id' => $id ],
        [ '%d' ]
    );
}

/* Activate a suburb */
function activate_suburb( $id ) {
    global $wpdb;

    $wpdb->update(
        "{$wpdb->prefix}gf_suburbs",
        [ 'active' => 1 ],
        [ 'id' => $id ],
        [ '%d', '%d' ]
    );
}

/* Deactivate a suburb */
function deactivate_suburb( $id ) {
    global $wpdb;

    $wpdb->update(
        "{$wpdb->prefix}gf_suburbs",
        [ 'active' => 0 ],
        [ 'id' => $id ],
        [ '%d', '%d' ]
    );
}

/* Toggle a suburb active / unactive */
function toggle_suburb( $id ) {

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
        deactivate_suburb($id);
    } else {
        activate_suburb($id);
    }

}

// Process the single suburb upload
// We don't have a 'admin_post_nopriv' version bc this action is only for admins
add_action( 'admin_post_single_suburb', 'process_form_single' );
function process_form_single() {
    // We don't have a 'admin_post_nopriv', but you can never be too sure
    if ( !current_user_can( 'manage_options' ) )  {
        wp_redirect( get_home_url() );
		    exit;
        wp_die( "How did you even get here" );
        die( "Ok what" );
        return false; // Maybe you CAN sometimes be too sure
	  } else {

        global $wpdb;

        $suburb = trim($_POST['suburb']);
        $postcode = trim($_POST['postcode']);

        if (!$suburb || $suburb == "" || !$postcode || $postcode == "") {
            // Return an error
            wp_redirect( get_admin_url() . "admin.php?page=gf-suburbs&gfp-response=error" );
            exit;
            return false;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . "gf_suburbs",
            array(
                "suburb" => $suburb,
                "postcode" => $postcode
            ),
            array(
                '%s',
                '%s'
            )
        );

        if ( $result > 0 ) { // Suburb was inserted correctly
            wp_redirect( get_admin_url() . "admin.php?page=gf-suburbs&gfp-response=success&gfp-count=1" );
            exit;
        } else { // Something went wrong
            wp_redirect( get_admin_url() . "admin.php?page=gf-suburbs&gfp-response=error&gfp-error=server" );
            exit;
        }

        return false;
    }
}

// Process the bulk suburb upload
// We don't have a 'admin_post_nopriv' version bc this action is only for admins
add_action( 'admin_post_bulk_suburb', 'process_form_bulk' );
function process_form_bulk() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_redirect( get_home_url() );
		    exit;
        wp_die( "How did you even get here" );
        die( "Ok what" );
        return false; // Maybe you CAN sometimes be too sure
	  } else {
        /*
        *  The format for the bulk upload is:
        *  suburb, postcode
        *  suburb, postcode
        *  suburb, postcode
        */
        global $wpdb;

        $raw = $_POST['bulk-suburbs'];
        $lines = preg_split("/\r\n|\n|\r/", $raw); // Lines of "suburb, postcode"

        $table = $wpdb->prefix . "gf_suburbs";
        $query = "INSERT INTO $table (suburb, postcode) VALUES";

        // Array where we'll store the lines with errors to give feedback to the user
        $w_errors = array();

        // At least 1 line had the correct format ?
        $atleast1 = false;

        foreach ( $lines as $l ) {

            $arr = explode( ",", $l );

            $sub = false;
            $pos = false;

            if ( isset($arr[0]) ) $sub = trim($arr[0]); // Suburb
            if ( isset($arr[1]) ) $pos = trim($arr[1]); // Postcode

            if (!$sub || $sub == "" || !$pos || $pos == "" || isset($arr[2])) {

                $w_errors[] = $l; // Add the raw line to the array of errors
                continue;

            } else {

                $query .= " ('$sub', '$pos'),"; // Add values to the query
                $atleast1 = true;

            }
        }

        $query = rtrim($query, ','); // Remove the last comma from the query

        $el = ""; // Error lines
        foreach ( $w_errors as $e ) {
            $el .= "&gfp-error_lines[]=$e";
        }

        if ($atleast1){
            // Add data to db and return how many sub / postcodes were added and any lines with errors
            $result = $wpdb->query( $query );
            wp_redirect( get_admin_url() . "admin.php?page=gf-suburbs&gfp-response=success&gfp-count=$result$el" );
            exit;
        } else {
            // If no lines had the correct format, just return the errors
            wp_redirect( get_admin_url() . "admin.php?page=gf-suburbs&gfp-response=error&gfp-error=bulk$el" );
            exit;
        }

        return false;
    }
}

// The link that displays when the user enters an invalid postcode
add_action( 'admin_post_edit_invalid_postcode_link', 'edit_invalid_postcode_link' );
function edit_invalid_postcode_link() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_redirect( get_home_url() );
		    exit;
        wp_die( "How did you even get here" );
        die( "Ok what" );
        return false; // Maybe you CAN sometimes be too sure
	  } else {
        if ( isset($_POST['link']) ) {
            $link = $_POST['link'];
            if (get_option('invalid_postcode_link'))
                update_option("invalid_postcode_link", $link);
            else
                add_option("invalid_postcode_link", $link);
        }
        wp_redirect( get_admin_url() . "admin.php?page=gf-suburbs" );
        exit;
    }
}

// Print Success / Error Admin Notices
function gfp_admin_notice__error() {

    $error = 'fields'; // Default error
    if ( isset($_GET['gfp-error']) )
      $error = $_GET['gfp-error'];

	  $class = 'notice notice-error is-dismissible';

    if ($error == 'fields')
      $message = __( 'Please enter a valid postcode and suburb.', 'wordpress' );
    else if ($error == 'server')
      $message = __( 'An error ocurred. Not your fault. Please try again and contact your webmaster if the error persists.', 'wordpress' );
    else if ($error == 'bulk')
      $message = __( 'No suburbs were added. Please make sure your rows are in the right format.', 'wordpress' );

	  printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}
function gfp_admin_notice__success() {

    $count = 1; // Default count
    if ( isset($_GET['gfp-count']) ) $count = $_GET['gfp-count'];

	$class = 'notice notice-success is-dismissible';
	$message = __( "Success! $count suburbs added", 'wordpress' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}
function gfp_admin_notice__error_lines() { // Bulk upload lines with error

    $lines = $_GET['gfp-error_lines'];

    $class = 'notice notice-warning is-dismissible';
    $message = __( "The following lines have an invalid format and couldn't be processed:", 'wordpress' );
    $message .= "<ul style='list-style: disc outside none; padding-left: 15px;'>";
    foreach ( $lines as $l ) {
        $message .= "<li>" . esc_html($l) . "</li>";
    }
    $message .= "</ul>";

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
}

if ( isset($_GET['gfp-response']) ) {
    $res = $_GET['gfp-response'];
    if ($res == 'error') {
        add_action( 'admin_notices', 'gfp_admin_notice__error' );
    } else if ($res == 'success') {
        add_action( 'admin_notices', 'gfp_admin_notice__success' );
    }

    if ( isset($_GET['gfp-error_lines']) ) {
        if ( is_array($_GET['gfp-error_lines']) ) {
            add_action( 'admin_notices', 'gfp_admin_notice__error_lines' );
        }
    }

}
/* Admin page end */



/* Gravity Forms custom field and frontend logic */
include( plugin_dir_path( __FILE__ ) . 'classes/suburbs_field.php');
GF_Fields::register( new GF_Suburbs_Field() );

// Get the invalid postcode link with AJAX
add_action( 'wp_ajax_gf_get_invalid_postcode_link', 'gf_get_invalid_postcode_link' );
add_action( 'wp_ajax_nopriv_gf_get_invalid_postcode_link', 'gf_get_invalid_postcode_link' );
function gf_get_invalid_postcode_link() {
	$link = get_option("invalid_postcode_link");
  echo $link;
	wp_die();
}

// Get the available suburbs for the given postcode
add_action( 'wp_ajax_gf_check_postcode', 'gf_check_postcode' );
add_action( 'wp_ajax_nopriv_gf_check_postcode', 'gf_check_postcode' );
function gf_check_postcode() {
	$postcode = $_POST['postcode'];
    $suburbs = get_suburbs_by_postcode($postcode);
    if ($suburbs)
        echo json_encode($suburbs);
    else
        echo 0;
	wp_die();
}
function get_suburbs_by_postcode($pc) {
    if (!$pc) return false;

	global $wpdb;
    $result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT postcode, suburb
            FROM {$wpdb->prefix}gf_suburbs
            WHERE postcode = %s
            AND active = 1",
            $pc
        )
    );

    if (!$result) $result = false;

    return $result;
}
/* Gravity Forms Custom Field and frontend logic end */
