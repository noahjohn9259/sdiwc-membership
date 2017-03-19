(function($) {

  jQuery.validator.addMethod("lettersSpaceOnly", function(value, element) {
  return this.optional(element) || /^[a-z\s]+$/i.test(value);
  }, "Only alphabetical characters");

  $.validator.setDefaults({
    highlight: function(element) {
      $(element).closest('.form-group').addClass('has-error');
    },
    unhighlight: function(element) {
      $(element).closest('.form-group').removeClass('has-error');
    },
    errorElement: 'span',
    errorClass: 'help-block',
    errorPlacement: function(error, element) {
      // console.log(error);
      // error.remove();
      if (element.parent('.input-group').length) {
        error.insertAfter(element.parent());
      } else {
        if (element.closest('.form-group').find('.help-block').length) {
          element.closest('.form-group').find('.help-block').remove();
        }
        error.insertAfter(element);
      }
    }
  });
  $(document).ready(function() {
    $("#membershipForm").validate({
      rules: {
        firstName: {
          required: true,
          maxlength: 100,
          lettersSpaceOnly: true
        },
        lastName: {
          required: true,
          maxlength: 100,
          lettersSpaceOnly: true
        },
        email: {
          required: true,
          maxlength: 100,
          email: true
        },
        work: {
          maxlength: 100
        }
      },
      messages: {
        firstName: {
          lettersSpaceOnly: "This field can only contain letters and space."
        }
      },
      submitHandler: function(form) {
        form.submit();
      }
    });
    $("#updateMembershipForm").validate({
      rules: {
        email: {
          required: true,
          maxlength: 100,
          email: true
        }
      },
      submitHandler: function(form) {
        form.submit();
      }
    });
  });

  $('#newsletter').on('click', function(e) {
    if ($(this).is(':checked')) {
      $('.interestedIn').show();
    } else {
      $('.interestedIn').hide();
    }
  });
})(jQuery);