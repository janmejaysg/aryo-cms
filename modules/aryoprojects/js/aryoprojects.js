// (function (Drupal, once) {
//   Drupal.behaviors.aryoprojects = {
//     attach: function (context) {
//       // Use the once library to ensure the behavior only runs once per element
//       const elements = once('aryoprojects', '[data-myfeature]', context);
//       // `elements` is always an Array.
//       elements.forEach(function (element) {
//         processingCallback(element);
//       });
//     }
//   };

//   // Define the processing callback to handle each element
//   function processingCallback(element) {
//     // Show an alert with the element's outer HTML
//     alert('Processing element: ' + element.outerHTML);
//   }

// })(Drupal, once);

// (function (Drupal, once) {
//   Drupal.behaviors.aryoprojects = {
//     attach: function (context, settings) {
//       // Use the once library to ensure the behavior only runs once per element
//       const elements = once('aryoprojects', 'input[type="text"]', context);
//       elements.forEach(function (element) {
//         element.addEventListener('keypress', function (event) {
//           // Check for Enter key (code 13)
//           if (event.code === "Enter" || event.keyCode === 13) {
//             event.preventDefault(); // Prevent the default action
//             // Simulate a change event to trigger AJAX
//             const form = event.target.closest('form');
//             const inputName = event.target.name;
//             const input = form.querySelector(`[name="${inputName}"]`);
//             input.dispatchEvent(new Event('change', { bubbles: true }));
//           }
//         });
//       });
//     }
//   };
// })(Drupal, once);


// (function (Drupal, once) {
//   Drupal.behaviors.aryoprojects = {
//     attach: function (context, settings) {
//       // Use the once library to ensure the behavior only runs once per element
//       const elements = once('aryoprojects', 'input[type="text"]', context);
//       // `elements` is always an Array.
//       elements.forEach(function (element) {
//         element.addEventListener('keypress', function (event) {
//           // Check for Enter key
//           if (event.keyCode === 13) {
//             // Trigger the change event to update the field
//             const input = event.target;
//             const changeEvent = new Event('click', { bubbles: true });
//             input.dispatchEvent(changeEvent);

//             // Optionally, you can submit the form programmatically if needed
//             // to ensure any additional form handling happens.
//             // Uncomment if necessary
//             const form = input.closest('form');
//             console.log('form', form)
//             // form.submit();

//             // Note: The form submission might not be necessary if you
//             // handle everything via AJAX. Ensure your AJAX handler
//             // does not conflict with the default form submission process.
//           }
//         });
//       });
//     }
//   };
// })(Drupal, once);

// (function ($) {
//   Drupal.behaviors.projectInnerDetailsForm = {
//     attach: function (context) {
//       var textfields = $('input[type="text"]', context);

//       // Attach event listeners to each textfield
//       textfields.each(function () {
//         var $this = $(this);

//         $this.on('keydown', function (event) {
//           if (event.keyCode === 13) {
//             // Prevent the default form submission behavior
//             event.preventDefault();

//             // Trigger the AJAX update
//             $this.closest('form').trigger('submit');
//           }
//         });
//       });
//     }
//   };
// })(jQuery);






