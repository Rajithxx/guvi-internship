

$(document).ready(function () {

  
  const sessionToken = localStorage.getItem('session_token');
  const userEmail    = localStorage.getItem('user_email');
  const userName     = localStorage.getItem('user_name');

  if (!sessionToken) {
    window.location.href = 'login.html';
    return;
  }

  
  $('#profileName').text(userName  || 'User');
  $('#profileEmail').text(userEmail || '');

  function showAlert(message, type) {
    $('#alertBox')
      .removeClass('d-none alert-success alert-danger alert-warning')
      .addClass('alert-' + type)
      .text(message);
    setTimeout(function () {
      $('#alertBox').addClass('d-none');
    }, 4000);
  }

  function setLoading(isLoading) {
    if (isLoading) {
      $('#btnText').text('Saving...');
      $('#btnSpinner').removeClass('d-none');
      $('#updateBtn').prop('disabled', true);
    } else {
      $('#btnText').text('Update Profile');
      $('#btnSpinner').addClass('d-none');
      $('#updateBtn').prop('disabled', false);
    }
  }

  
  $.ajax({
    url: 'php/profile.php',
    type: 'GET',
    data: { token: sessionToken, email: userEmail },
    dataType: 'json',
    success: function (response) {
      if (response.status === 'success' && response.profile) {
        const p = response.profile;
        $('#age').val(p.age     || '');
        $('#dob').val(p.dob     || '');
        $('#contact').val(p.contact || '');
        $('#city').val(p.city   || '');
        $('#bio').val(p.bio     || '');
      }
    },
    error: function () {
      
    }
  });

  
  $('#updateBtn').on('click', function () {

    const age     = $('#age').val().trim();
    const dob     = $('#dob').val();
    const contact = $('#contact').val().trim();
    const city    = $('#city').val().trim();
    const bio     = $('#bio').val().trim();

    if (!age || !dob || !contact) {
      showAlert('Age, Date of Birth and Contact are required.', 'warning');
      return;
    }

    if (isNaN(age) || age < 1 || age > 120) {
      showAlert('Please enter a valid age.', 'warning');
      return;
    }

    setLoading(true);

    $.ajax({
      url: 'php/profile.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        token:   sessionToken,
        email:   userEmail,
        age:     parseInt(age),
        dob:     dob,
        contact: contact,
        city:    city,
        bio:     bio
      }),
      dataType: 'json',
      success: function (response) {
        setLoading(false);
        if (response.status === 'success') {
          showAlert('Profile updated successfully!', 'success');
        } else {
          showAlert(response.message || 'Update failed.', 'danger');
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

  
  $('#logoutBtn').on('click', function () {
    $.ajax({
      url: 'php/login.php',
      type: 'DELETE',
      contentType: 'application/json',
      data: JSON.stringify({ token: sessionToken }),
      dataType: 'json',
      complete: function () {
        localStorage.removeItem('session_token');
        localStorage.removeItem('user_email');
        localStorage.removeItem('user_name');
        window.location.href = 'login.html';
      }
    });
  });

});