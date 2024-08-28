// (function($, Drupal, drupalSettings){
//   'use strict';
//   $(document).ready(function() {
//     alert('Hello');
//   })
// })(jQuery, drupalSettings);
// (function ($, Drupal, drupalSettings) {
//   "use strict";

//   Drupal.behaviors.selfLeadsUpdateForm = {
//     attach: function (context, settings) {
//       // Apply the behavior to the form
//       $("#self-lead-update-form", context).each(function () {
//         var $form = $(this);

//         $form.submit(function (event) {
//           event.preventDefault(); // Prevent the default form submission

//           // Get the selected update field
//           var updateField = $("#edit-update-field").val();
//           var updateFieldText = $("#edit-update-field option:selected").text();

//           // Show confirmation alert
//           var userConfirmed = confirm(
//             "Hello\nUpdate Field: " +
//               updateFieldText +
//               "\nDo you want to proceed?"
//           );

//           if (userConfirmed) {
//             console.log("Form will be submitted");

//             // Add hidden input field to preserve 'op' value
//             var $hiddenInput = $("<input>").attr({
//               type: "hidden",
//               name: "op",
//               value: "Update"
//             });

//             // Append hidden input to the form
//             $form.append($hiddenInput);

//             // Trigger the form submission manually
//             $form[0].submit();
//           } else {
//             console.log("Form submission canceled");
//             return false;
//           }
//         });
//       });
//     }
//   };
// })(jQuery, Drupal, drupalSettings);


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
//       $('#self-lead-update-form').submit();
//     } else {
//     }
//   };
// })(jQuery, Drupal);
