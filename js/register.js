$(document).ready(function () {

  function showAlert(message, type) {
    $('#alertBox')
      .removeClass('d-none alert-success alert-danger alert-warning')
      .addClass('alert-' + type)
      .text(message);
  }

  function setLoading(isLoading) {
    if (isLoading) {
      $('#btnText').text('Registering...');
      $('#btnSpinner').removeClass('d-none');
      $('#registerBtn').prop('disabled', true);
    } else {
      $('#btnText').text('Register');
      $('#btnSpinner').addClass('d-none');
      $('#registerBtn').prop('disabled', false);
    }
  }

  $('#registerBtn').on('click', function () {

    const name            = $('#name').val().trim();
    const email           = $('#email').val().trim();
    const password        = $('#password').val();
    const confirmPassword = $('#confirmPassword').val();

    if (!name || !email || !password || !confirmPassword) {
      showAlert('All fields are required.', 'warning');
      return;
    }

    if (password.length < 6) {
      showAlert('Password must be at least 6 characters.', 'warning');
      return;
    }

    if (password !== confirmPassword) {
      showAlert('Passwords do not match.', 'danger');
      return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      showAlert('Please enter a valid email address.', 'warning');
      return;
    }

    setLoading(true);

    $.ajax({
      url: 'php/register.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ name, email, password }),
      dataType: 'json',
      success: function (response) {
        setLoading(false);
        if (response.status === 'success') {
          showAlert('Registration successful! Redirecting to login...', 'success');
          setTimeout(function () {
            window.location.href = 'login.html';
          }, 1500);
        } else {
          showAlert(response.message || 'Registration failed.', 'danger');
        }
      },
      error: function (xhr) {
        setLoading(false);
        let errMsg = 'Server error. Please try again.';
        try {
          const res = JSON.parse(xhr.responseText);
          errMsg = res.message || errMsg;
        } catch (e) {}
        showAlert(errMsg, 'danger');
      }
    });

  });

});