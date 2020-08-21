let $ = jQuery;
$(document).ready(function(){
    if ( llmsat_block_editor.block_editor_active == "yes" ) {
        $('#search-submit').on('click', function() {
            var href = window.location.href.substring(0, window.location.href.indexOf('?'));
            var qs = window.location.href.substring(window.location.href.indexOf('?') + 1, window.location.href.length);
            var newParam = "s" + '=' +  $('#students-search-input').val();

            if (qs.indexOf('s'+ '=') == -1) {
                if (qs == '') {
                    qs = '?'
                } else {
                    qs = qs + '&'
                }
                qs = qs + newParam;

            } else {
                var start = qs.indexOf('s'+ "=");
                var end = qs.indexOf("&", start);
                if (end == -1) {
                    end = qs.length;
                }
                var curParam = qs.substring(start, end);
                qs = qs.replace(curParam, newParam);
            }
            window.location.replace(href + '?' + qs);
        });
    }

    var uri = window.location.toString();
    if ( uri.indexOf( "&s=" ) > 0 ) {
        var clean_uri = uri.substring( 0, uri.indexOf( "&s=" ) );
        window.history.replaceState( {}, document.title, clean_uri );
    }

    // init students attendance meta box for admin
    let screens = [ 'course' ];
    if ( window.llms && window.llms.post.post_type && -1 !== screens.indexOf( window.llms.post.post_type ) ) {

        // bind
        const courseId = window.llms.post.id || '';
        $( '#llmsat-add-student-select' ).llmsStudentsSelect2( { multiple: true, enrolled_in: courseId } );

        $( '#llmsat-enroll-students' ).on( 'click', function() {
            present_students();
        } );
    }

    present_students = function() {

        let select    = $( '#llmsat-add-student-select' ),
          ids        = select.val(),
          courseId   = window.llms.post.id || '';

        $.ajax({
            url : LLMS.Ajax.url,
            type : 'post',
            data : {
                action : 'llmsat_attendance_btn_ajax_action',
                pid : courseId,
                uids : ids.join(',')
            },
            success : function( response ) {

                let suffix = response.match(/\d+/); // 123
                console.log(suffix[0]);
                if ( suffix[0] == "2" ) {
                    $("#llmsat-ajax-response-id span").addClass( 'llmsat-error' );
                } else if( suffix[0] == "1" || suffix[0] == "3") {
                    $("#llmsat-ajax-response-id span").addClass( 'llmsat-success' );
                }
                $("#llmsat-ajax-response-id span").html( response.replace(/\d+/g, '') );

                select.val( null ).trigger( 'change' );
            }
        });
    };

    return;
});
