jQuery(document).ready(function($) {
    $('#twilio_call').click(function() {
        console.log('Call button clicked');

        var twilioNumber = $('#twilio_number').val();
        var personalNumber = $('#personal_number').val();
        var otherNumber = $('#other_number').val(); // Change this variable name

        // Add the console.log statement after defining the otherNumber variable
        console.log('Other number value from input field:', otherNumber);

        // Send AJAX request
        var data = {
            action: 'twilio_call',
            twilio_number: twilioNumber,
            personal_number: personalNumber,
            other_number: otherNumber // Change this property name
        };

        console.log('Sending AJAX request', data);

        $.post(ajaxurl, data, function(response) {
            console.log('AJAX response received', response);
            if (response.status === 'error') {
                alert('Error: ' + response.message);
            } else {
                alert(response);
            }
        }, 'json');
    });
});
