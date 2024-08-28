// alert('Test...');

// (function($, Drupal, drupalSettings){
// (function($, Drupal, drupalSettings)
// {
//   'use strict';
//   $.fn.consent = function () {
//     var userConfirmed = confirm('Do you want to continue');
//     if(userConfirmed){
//       return 'yes';
//     }
//     else{

//     }
//   }
// })(jQuery, Drupal);

// (function ($, Drupal, drupalSettings) {
//   'use strict';
//   $.fn.consent = function () {
//     console.log($('#student_registration_form').find('[name="op"]'));
//     var userConfirmed = confirm('Do you want to continue?');
//     if (userConfirmed) {
//       $('#success-message').text('yes'); // Update the text of the success-message div
//     } else {
//       $('#success-message').text('no'); // Optionally handle the "no" case
//     }
//   };
// })(jQuery, Drupal);

// (function ($, Drupal, drupalSettings) {
//   'use strict';

//   $(document).ready(function () {
//     // Call the consent function to display the alert
//     consent();
//   });

//   $.fn.consent = function () {
//     var userConfirmed = confirm('Do you want to continue?');
//     if (userConfirmed) {
//       // Trigger form submission
//       $('#student_registration_form').submit();
//     } else {
//       // Handle "no" case if needed
//       // For example, you could redirect the user to a different page
//       // window.location.href = '/some-other-page';
//     }
//   };
// })(jQuery, Drupal);

// (function ($, Drupal) {
//   'use strict';

//   Drupal.ajaxexample = Drupal.ajaxexample || {};

//   Drupal.ajaxexample.confirmAndSubmit = function () {
//     if (confirm('Do you want to continue?')) {
//       // If confirmed, submit the form
//       $('#student-registration-form').find('[name="op"]').click();
//     } else {
//       // Optionally handle the "no" case
//       $('#success-message').text('Action cancelled.');
//     }
//   };

// })(jQuery, Drupal);



// (function($, Drupal, drupalSettings){
//   'use strict';
//   $(document).ready(function() {
//     $('#self-lead-update-form').submit(function(event) {
//       event.preventDefault(); // Prevent the default form submission

//       // Get the selected update field
//       // var updateField = $('#edit-update-field').val();
//       var updateFieldText = $('#edit-update-field option:selected').text();

//       // Show confirmation alert
//       var userConfirmed = confirm('Hello\nUpdate Field: ' + updateFieldText + '\nDo you want to proceed?');

//       if (userConfirmed) {
//         console.log('Form will be submitted');

//         // Reference the form element explicitly
//         $('#self-lead-update-form').submitForm();
//       } else {
//         console.log('Form submission canceled');
//         return false;
//       }
//     });
//   });
// })(jQuery, Drupal, drupalSettings);

