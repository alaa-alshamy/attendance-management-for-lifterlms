<?php
/**
 * Generates The User Grade Listing for Admin
 */
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class LLMS_Attendance_List_Table_Class extends WP_List_Table {
	//define dataset for WP_List_Table => data

	/** Class constructor */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'LLMS Student', LLMS_At_TEXT_DOMAIN ), //singular name of the listed records
			'plural'   => __( 'LLMS Students', LLMS_At_TEXT_DOMAIN ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );
	}


	/**
	 * Function to filter data based on order , order_by & searched items
	 *
	 * @param string $orderBy
	 * @param string $order
	 * @param string $search_term
	 * @return array $users_array()
	 */
	public function list_table_data_fun( $orderBy='', $order='' , $search_term='' ) {

		$users_array = array();
	  $course_id = get_the_ID();
	  if (
	  	$course_id
	  	&& llmsat_is_enabled($course_id)
		){
			$queryArgs = [
				'post_id'  => $course_id,
				'statuses' => 'enrolled',
				'page'     => 1,
				'per_page' => 50,
			];
			$orderBy = $orderBy ?: 'id';
			$order = ($order == 'desc' ? 'DESC' : 'ASC');
			if( $search_term ) {
				$queryArgs['search'] = sanitize_text_field( $search_term );
			}

			if ($orderBy == 'id') {
				$queryArgs['sort'] = [
					'id' => $order,
				];
			}
			else if ($orderBy == 'title') {
				$queryArgs['sort'] = [
					'first_name' => $order,
					'last_name'  => $order,
				];
			}

			// this coming from same code as "llms_get_enrolled_students" but with custom args
			// $enrolledStudentsIds  = llms_get_enrolled_students( $course_id, 'enrolled' );
			$enrolledStudentsIds = [];
			$query = new LLMS_Student_Query( $queryArgs );
			if ( $query->results ) {
				$enrolledStudentsIds = wp_list_pluck( $query->results, 'id' );
			}

			if(
				count( $enrolledStudentsIds ) > 0
			) {
		  	$maxAttendanceCount = absint(get_post_meta($course_id, LLMS_AT_MAX_COUNT_META_KEY, true));
				foreach ( $enrolledStudentsIds as $studentId ) {
					$studentId = absint( $studentId );

					$student = new LLMS_Student( $studentId );
					$first = $student->get( 'first_name' );
					$last  = $student->get( 'last_name' );
					if ( $first && $last ) {
			  		$studentName = $first . ' ' . $last;
					} else {
			  		$studentName = $student->get( 'display_name' );
					}

					$count = llms_get_user_postmeta($studentId, $course_id, LLMS_AT_COUNTER_META_KEY, true);
					if (
						$studentId != 0
						&& $count
					) {
						$count = intval( $count );
						$users_array[] = array(
							"id"          => $studentId,
							"title"       => '<b><a href="' . get_author_posts_url( $studentId ) . '"> ' . $studentName . '</a></b>',
							"attendance_count" => $count,
							"attendance_percen"=> round($count/$maxAttendanceCount * 100) . '%',
						);
					}
				}
			}
		}

		return $users_array;
	}

	//prepare_items
	public function prepare_items() {

		$orderby = sanitize_text_field( isset( $_GET['orderby'] ) ? trim( $_GET['orderby'] ): "" );
		$order   = sanitize_text_field( isset( $_GET['order'] ) ? trim( $_GET['order'] ) : "" );


		$search_term  = sanitize_text_field( isset( $_POST['s'] ) ? trim( $_POST['s'] ) : "" );
		if( $search_term == "" ) {

			$search_term  = sanitize_text_field( isset( $_GET['s'] ) ? trim( $_GET['s'] ) : "" );
		}

		$datas        = $this->list_table_data_fun( $orderby, $order, $search_term );


		$per_page     = 30;
		$current_page = $this->get_pagenum();
		$total_items  = count($datas);

		$this->set_pagination_args( array( "total_items"=> $total_items,
			"per_page" => $per_page ) );

		$this->items = array_slice( $datas, ( ( $current_page - 1 )* $per_page ), $per_page );

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );
	}

	//get_columns
	public function get_columns() {

		$columns = array(
			"cb"                => "<input type='checkbox'/>",
			"id"                => __( "ID", LLMS_At_TEXT_DOMAIN ),
			"title"             => __( "Enrolled Students", LLMS_At_TEXT_DOMAIN  ),
			"attendance_count"  => __( "Attendance Count", LLMS_At_TEXT_DOMAIN ),
			"attendance_percen" => __( "Attendance Percentage", LLMS_At_TEXT_DOMAIN ),
		);

		return $columns;
	}

	public function get_hidden_columns() {
		return array("");
	}

	public function get_sortable_columns() {
			return array (
			"title" => array( "title", true ),
			"id"    => array( "id", true ),
		);

	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {

		// REMOVED NONCE -- INTERFERING WITH SAVING POSTS ON METABOXES
		// Add better detection if this class is used on meta box or not.
		/*
		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		*/

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear"/>
		</div>
	<?php
	}

	//column_default
	public function column_default( $item, $column_name ){
		switch ( $column_name ) {
			case 'id':

			case 'title':

			case 'attendance_count':

			case 'attendance_percen':
			return $item[ $column_name ];

			default:
				return "no value";

		}

	}

}

/**
 * Shows the List table
 *
 * @return void
 */
function llms_at_list_table_layout() {
	$myRequestTable = new LLMS_Attendance_List_Table_Class();
	global $pagenow;
	?>
	<form method="get">
	<input type="hidden" name="page" value="<?php echo $pagenow ?>" />
	<?php if( isset( $myRequestTable ) ) : ?>
		<?php $myRequestTable->prepare_items();  ?>
		<?php $myRequestTable->search_box( __( 'Search students by name or email' ), 'students' ); //Needs To be called after $myRequestTable->prepare_items() ?>
		<?php $myRequestTable->display(); ?>
	<?php endif; ?>
	</form>

	<div class="llms-metabox-section">
		<h2><?php _e( 'Make attendance for students' ); ?></h2>

		<div class="llms-metabox-field d-all">
			<select id="llmsat-add-student-select" multiple="multiple" name="_llmsat_add_student"></select>
		</div>

		<div class="llms-metabox-field d-all d-right">
			<button class="llms-button-primary" id="llmsat-enroll-students" type="button"><?php _e( 'Present Students' ); ?></button>
			<div id="llmsat-ajax-response-id" class="llmsat-ajax-response"><span></span></div>
		</div>

		<div class="clear"></div>
	</div><?php

}

llms_at_list_table_layout();
