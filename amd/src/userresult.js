define(['jquery', 'core/notification', 'core/ajax'], function ($, notification, ajax) {
    return {
        init: function () {
            $(document).ready(function () {
                $('#select_user_result').on('change', function () {
                    var userid = $('#select_user_result').val();
                    if (!userid){
                        $('#user_rating').html('');
                        $('#criteria_render').html('');
                        $('#all_result_render').html('');

                        if(!$('#assessment_form').hasClass('d-none')){
                            $('#assessment_form').addClass('d-none');
                        }
                        if ($('#select_user option:selected')){
                            $('#select_user').val("");
                        }
                        alert('Please select a valid user to show data');
                    }
                    else {
                        if(!$('#assessment_form').hasClass('d-none')){
                            $('#assessment_form').addClass('d-none');

                        }
                        $('#all_result_render').removeClass('d-none');

                        if ($('#select_user option:selected')){
                            $('#select_user').val("");
                        }
                        var cmid = $('#courseid').val();
                        $('#user_rating').html('');
                        $('#criteria_render').html('');
                        // $('#assessment_form').removeClass('d-none');
                        $('#all_result_render').html('');
                        render_result(userid, cmid);
                    }

                });
            });
            function generate_rating_star(stars) {
                var htm = '';
                for (var i = 0; i < stars; i++) {
                    htm += '<svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                        '<path d="M3.58266 18.5634C3.48541 19.1178 4.03237 19.5517 4.51542 19.3034L10.002 16.4835L15.4885 19.3034C15.9715 19.5517 16.5185 19.1178 16.4212 18.5634L15.3841 12.6516L19.787 8.45578C20.1985 8.06366 19.9855 7.34671 19.4342 7.2684L13.3111 6.39856L10.581 0.990381C10.3351 0.503206 9.66885 0.503206 9.42291 0.990381L6.69276 6.39856L0.569668 7.2684C0.0184315 7.34671 -0.194569 8.06366 0.216907 8.45578L4.61982 12.6516L3.58266 18.5634ZM9.71342 15.1033L5.10623 17.4712L5.97405 12.5246C6.01495 12.2915 5.93803 12.0527 5.77061 11.8931L2.13706 8.4305L7.20245 7.71092C7.41184 7.68117 7.59468 7.54743 7.69346 7.35176L10.002 2.77884L12.3104 7.35176C12.4092 7.54743 12.5921 7.68117 12.8015 7.71092L17.8668 8.4305L14.2333 11.8931C14.0659 12.0527 13.989 12.2915 14.0299 12.5246L14.8977 17.4712L10.2905 15.1033C10.1085 15.0097 9.89541 15.0097 9.71342 15.1033Z" fill="#9CA4B6"/>' +
                        '</svg>';
                }
                return htm;
            }
            function render_result(userid, cmid) {

                var wsfunction = 'interview_assessment_results';
                var params = {
                    'cmid': cmid,
                    'userid': userid
                };

                var request = {
                    methodname: wsfunction,
                    args: params
                };

                try {
                    ajax.call([request])[0].done(function (data) {

                        var html = '';

                        if (data.result === 'success') {
                            console.log(data.interviewers);
                            console.log(data.reviews);
                            data.interviewers.forEach(function (interviewer) {

                                html += '<label for="">'+interviewer.firstname+'</label>';

                                data.reviews.forEach(function (review){
                                    if (review.interviewer_id === interviewer.interviewer_id){

                                        console.log('review', review);
                                        html += '<div class="col-md-4">\n' +
                                            '            <div class="rating">\n' +
                                            '                <label for="">'+review.name+'</label>\n' + generate_rating_star(review.grade) +
                                            '            </div>\n' +
                                            '        </div>';
                                    }


                                });

                            });
                            $('#all_result_render').html(html);

                        } else {

                            notification.addNotification({
                                message: 'No Result Found',
                                type: 'error'
                            });
                        }
                    }).fail(notification.exception);
                } catch (e) {
                    return false;
                }

            }

            function generate_rating_star(stars) {
                var htm = '';
                for (var i = 0; i < stars; i++) {
                    htm += '<svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                        '<path d="M3.58266 18.5634C3.48541 19.1178 4.03237 19.5517 4.51542 19.3034L10.002 16.4835L15.4885 19.3034C15.9715 19.5517 16.5185 19.1178 16.4212 18.5634L15.3841 12.6516L19.787 8.45578C20.1985 8.06366 19.9855 7.34671 19.4342 7.2684L13.3111 6.39856L10.581 0.990381C10.3351 0.503206 9.66885 0.503206 9.42291 0.990381L6.69276 6.39856L0.569668 7.2684C0.0184315 7.34671 -0.194569 8.06366 0.216907 8.45578L4.61982 12.6516L3.58266 18.5634ZM9.71342 15.1033L5.10623 17.4712L5.97405 12.5246C6.01495 12.2915 5.93803 12.0527 5.77061 11.8931L2.13706 8.4305L7.20245 7.71092C7.41184 7.68117 7.59468 7.54743 7.69346 7.35176L10.002 2.77884L12.3104 7.35176C12.4092 7.54743 12.5921 7.68117 12.8015 7.71092L17.8668 8.4305L14.2333 11.8931C14.0659 12.0527 13.989 12.2915 14.0299 12.5246L14.8977 17.4712L10.2905 15.1033C10.1085 15.0097 9.89541 15.0097 9.71342 15.1033Z" fill="#9CA4B6"/>' +
                        '</svg>';
                }
                return htm;
            }
        }

    };
});