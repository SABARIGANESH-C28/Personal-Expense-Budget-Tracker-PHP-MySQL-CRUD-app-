// Small bits of vanilla JavaScript used across the app.

// 1) Ask before deleting. Any <form class="js-confirm" data-message="..."> gets a confirm() dialog.
document.querySelectorAll('form.js-confirm').forEach(function (form) {
  form.addEventListener('submit', function (event) {
    var message = form.dataset.message || 'Are you sure?';
    if (!confirm(message)) {
      event.preventDefault();   // stop the form from being submitted
    }
  });
});

// 2) Auto-hide success alerts after 4 seconds (setTimeout), using Bootstrap's own close animation.
setTimeout(function () {
  document.querySelectorAll('.alert-success.flash').forEach(function (alertEl) {
    var instance = bootstrap.Alert.getOrCreateInstance(alertEl);
    instance.close();
  });
}, 4000);

// 3) Default any date input marked data-default-today to today's date.
var today = new Date();
var iso = today.getFullYear() + '-' +
          String(today.getMonth() + 1).padStart(2, '0') + '-' +
          String(today.getDate()).padStart(2, '0');
document.querySelectorAll('input[type=date][data-default-today]').forEach(function (input) {
  if (!input.value) { input.value = iso; }
});
