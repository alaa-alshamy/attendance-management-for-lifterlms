<?php
defined( 'ABSPATH' ) || exit;

/**
 * Manage MetaBox on the Edit course Page
 */
class LLMS_AT_Metabox {

	private $enableSelectOptions = [
		'' => 'Global setting',
		'yes' => 'Enable',
		'no' => 'Disable',
	];

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
        $disallow_attendance_text = __( 'Attendance', LLMS_At_TEXT_DOMAIN );
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

        if (!llmsat_is_enabled($course_id)) {
            echo '<div class="llmsat-error"><h2>'.__( 'Enable attendance option to enlist enrolled students attendance information.', LLMS_At_TEXT_DOMAIN ).' </h2></div>';
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
				$enableAttendanceValue = $metaData[LLMS_AT_ENABLE_META_KEY][0] ?? '';
				$enableAttendanceForStudentsValue = $metaData[LLMS_AT_ENABLE_FOR_STUDENTS_META_KEY][0] ?? '';
				$maxCount = $metaData[LLMS_AT_MAX_COUNT_META_KEY][0] ?? 0;

				$isGlobalEnableAttendanceValue = ('yes' === get_option( LLMS_AT_GLOBAL_ENABLE_OPTION_KEY, 'yes' ));
				$isGlobalEnableAttendanceForStudentsValue = ('yes' === get_option( LLMS_AT_GLOBAL_ENABLE_FOR_STUDENTS_OPTION_KEY, 'yes' ));

				$enabledText = __('Enabled', LLMS_At_TEXT_DOMAIN);
				$disabledText = __('Disabled', LLMS_At_TEXT_DOMAIN);
				$enableAttendanceSelectArray = $this->enableSelectOptions;
				$enableAttendanceSelectArray[0] .= '(' . ($isGlobalEnableAttendanceValue ? $enabledText : $disabledText) . ')';

				$enableAttendanceForStudentsSelectArray = $this->enableSelectOptions;
				$enableAttendanceForStudentsSelectArray[0] .= '(' . ($isGlobalEnableAttendanceForStudentsValue ? $enabledText : $disabledText) . ')';
        ?>
        <div class="llmsat-field">
					<?php
					_e('Enable Attendance', LLMS_At_TEXT_DOMAIN);
					$this->get_select(LLMS_AT_ENABLE_META_KEY, $enableAttendanceSelectArray, $enableAttendanceValue);
					?>
        </div>
				<div class="llmsat-field">
					<?php
					_e('Enable For Students', LLMS_At_TEXT_DOMAIN);
					$this->get_select(LLMS_AT_ENABLE_FOR_STUDENTS_META_KEY, $enableAttendanceForStudentsSelectArray, $enableAttendanceForStudentsValue);
					?>
        </div>
				<div class="llmsat-field">
					<?php _e('Max Attendance', LLMS_At_TEXT_DOMAIN); ?>
					<input type="number" name="<?=LLMS_AT_MAX_COUNT_META_KEY?>" value="<?=$maxCount?>" />
				</div>
        <?php
    }

    public function get_select($name, $arrayOfOptions, $currentValue) {
			?>
				<select name="<?=$name?>">
					<?php foreach ($arrayOfOptions as $key => $value) {?>
							<option value="<?=$key?>" <?=($key === $currentValue) ? 'selected' : ''?>><?=$value?></option>
					<?php }?>
				</select>
			<?php
		}

    /**
     * Saves the meta box post
     * @param $post_id int post_id where metabox is to be saved
     */
    public function save_meta_box( $post_id ) {

        $post_type          = get_post_type( $post_id );
        if( trim( $post_type ) == 'course' ) {
						$meta_field_value_1 = sanitize_text_field( $_POST[LLMS_AT_ENABLE_META_KEY] );
						$meta_field_value_2 = sanitize_text_field( $_POST[LLMS_AT_ENABLE_FOR_STUDENTS_META_KEY] );
						$meta_field_value_3 = sanitize_text_field( $_POST[LLMS_AT_MAX_COUNT_META_KEY] );

						if (!in_array($meta_field_value_1, array_keys($this->enableSelectOptions))) {
							$meta_field_value_1 = '';
						}
						if (!in_array($meta_field_value_2, array_keys($this->enableSelectOptions))) {
							$meta_field_value_2 = '';
						}
						$meta_field_value_3 = absint($meta_field_value_3);

            update_post_meta( $post_id, LLMS_AT_ENABLE_META_KEY, $meta_field_value_1 );
						update_post_meta( $post_id, LLMS_AT_ENABLE_FOR_STUDENTS_META_KEY, $meta_field_value_2 );
            update_post_meta( $post_id, LLMS_AT_MAX_COUNT_META_KEY, $meta_field_value_3 );
        }
    }
}

return new LLMS_AT_Metabox();
