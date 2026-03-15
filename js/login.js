$(document).ready(function () {

  
  if (localStorage.getItem('session_token')) {
    window.location.href = 'profile.html';
    return;
  }

  function showAlert(message, type) {
    $('#alertBox')
      .removeClass('d-none alert-success alert-danger alert-warning')
      .addClass('alert-' + type)
      .text(message);
  }

  function setLoading(isLoading) {
    if (isLoading) {
      $('#btnText').text('Logging in...');
      $('#btnSpinner').removeClass('d-none');
      $('#loginBtn').prop('disabled', true);
    } else {
      $('#btnText').text('Login');
      $('#btnSpinner').addClass('d-none');
      $('#loginBtn').prop('disabled', false);
    }
  }

  $('#loginBtn').on('click', function () {

    const email    = $('#email').val().trim();
    const password = $('#password').val();

    if (!email || !password) {
      showAlert('Please fill in all fields.', 'warning');
      return;
    }

    setLoading(true);

    $.ajax({
      url: 'php/login.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ email, password }),
      dataType: 'json',
      success: function (response) {
        setLoading(false);
        if (response.status === 'success') {

          
          localStorage.setItem('session_token', response.session_token);
          localStorage.setItem('user_email',    response.email);
          localStorage.setItem('user_name',     response.name);

          showAlert('Login successful! Redirecting...', 'success');
          setTimeout(function () {
            window.location.href = 'profile.html';
          }, 1000);

        } else {
          showAlert(response.message || 'Invalid credentials.', 'danger');
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