<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage MetaBox on the Edit course Page
 */
class LLMS_AT_Metabox {

	/**
	 * Constructor
	 */
	public function __construct() { 

        $this->hooks();
    }
    
    private function hooks() {

        add_action( 
            'add_meta_boxes', 
            [ $this, 'register_attendance_meta_boxes' ]
        );

        add_action( 
            'save_post',      
            [ $this, 'save_meta_box' ]
        );
        
        add_action( 
            'save_post', 
            [ $this, 'llms_attendance_add_query_string'], 
            100, 
            3 
        );

        add_action( 
            'post_updated', 
            [ $this, 'llms_attendance_add_query_string' ], 
            10, 
            3 
        ); 
    }

    /**
     * Adds query string to the course,topic & lesson edit url
     *
     * @param [type] $post_id
     * @param [type] $post
     * @param [type] $update
     * @return void
     */
    public function llms_attendance_add_query_string( $post_id, $post, $update ) {
		$post_type    = get_post_type($post);
        $search_term  = sanitize_text_field( isset( $_POST['s'] ) ? trim( $_POST['s'] ) : "" );
		if ( $search_term == "" ) {

            $search_term  = sanitize_text_field( isset( $_GET['s'] ) ? trim( $_GET['s'] ) : "" );
        }

		if ( ( $post_type == 'course' )  && $search_term != "" ) {

			wp_safe_redirect( add_query_arg( 's', $search_term, sanitize_text_field( $_POST['_wp_http_referer'] ) ) );
			exit;
        }
        return;
    }
    
    /**
     * Register the meta box for the attendance management system
     * @param void
     * @return void
     */
    public function register_attendance_meta_boxes() {
        $disallow_attendance_text = __( 'DisAllow Attendance ', LLMS_At_TEXT_DOMAIN );
        $disallow_attendance_text = apply_filters( 'llmsat_disallow_attendance_text', $disallow_attendance_text );
        $students_information_text = __( 'Students Attendance Information ', LLMS_At_TEXT_DOMAIN );
        $students_information_text = apply_filters( 'llmsat_students_attendance_information_text', $students_information_text );
        add_meta_box( 
            'llmsat-metabox-id', 
            $disallow_attendance_text,          
            [ $this, 'show_attendance_meta_box' ], 
            'course', 
            'side', 
            'high' 
        );

        add_meta_box( 
            'llmsat-students-metabox-id',
            $students_information_text, 
            [ $this, 'show_student_listing_meta_box' ], 
            'course', 
            'advanced', 
            'high' 
        );
    }

    public function show_student_listing_meta_box () {
        $course_id = get_the_ID();
        $course    = llms_get_post( $course_id );
        $students  = llms_get_enrolled_students( $course->get( 'id' ), 'enrolled' );
        $disallow  = get_post_meta( $course_id, 'llmsatck1', true );
        if ( $disallow == 'on' ) {
            echo '<div class="llmsat-error"><h2>'.__( 'Turn off the disallow attendance option to enlist enrolled students attendance information.', LLMS_At_TEXT_DOMAIN ).' </h2></div>';
            return;
        }
        do_action( 'llmsat_student_dashboard_before_my_attendance' );

        if( file_exists( LLMS_At_INCLUDES_DIR . 'integration/llmsat-views.php' ) ) {
            require_once ( LLMS_At_INCLUDES_DIR . 'integration/llmsat-views.php' );
        }
        do_action( 'llmsat_student_dashboard_after_my_attendance' );
    }

    /**
     * Display the Meta box the Course Edit page
     * @param void
     */
    public function show_attendance_meta_box() {

        $post_id  = absint( sanitize_text_field( $_REQUEST['post'] ) );
        $metaData = get_post_meta( $post_id );
        $disallow = $metaData['llmsatck1'][0];
        $maxCount = $metaData['llmsat_max_count'][0];
        if ( $disallow == 'on' ) {
            $disallow = true;
        }
        ?>
        <div>
					<input type="checkbox" name="llmsatck1" <?php if( $disallow == true ) { ?>checked="checked"<?php } ?> /> Disallow Attendance
        </div>
				<div>
					Max Attendance
					<input type="number" name="llmsat_max_count" value="<?=$maxCount?>" />
				</div>
        <?php
    }

    /**
     * Saves the meta box post
     * @param $post_id int post_id where metabox is to be saved
     */
    public function save_meta_box( $post_id ) {

        $post_type          = get_post_type( $post_id );
        $meta_field_value_1 = sanitize_text_field( $_POST['llmsatck1'] );
        $meta_field_value_2 = sanitize_text_field( $_POST['llmsat_max_count'] );
        if( trim( $post_type ) == 'course' ) {
            update_post_meta( $post_id, 'llmsatck1', $meta_field_value_1 );
            update_post_meta( $post_id, 'llmsat_max_count', $meta_field_value_2 );
        }
    }
}

return new LLMS_AT_Metabox();
