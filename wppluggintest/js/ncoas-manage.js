jQuery(document).ready( function(){
  $ = jQuery;
  animationSpeed = 'fast';

  // When the page loads, make sure we hide the editable forms.
  $('[data-editableForm]').hide();

  /**
   * Toggles a form view and a normal view section.
   */
  $('[data-toggle="forms"]').click(function(event){
    event.preventDefault();
    event.stopPropagation();
    tgt = $(event.currentTarget);
    targetData = tgt.data('target');

    content = $('[data-editableContent="' + targetData + '"]');
    form = $('[data-editableForm="' + targetData + '"]');


    if (content.is(':visible')) {
      content.slideUp(animationSpeed);
      form.slideDown(animationSpeed);
    } else {
      content.slideDown(animationSpeed);
      form.slideUp(animationSpeed);
    }
  });


  /**
   * When the "first time loging in" option is selected, this will be triggered
   * and will toggle the password field's visibility and the existance of a
   * hidden field used in the POST to indicate that it's the user's first login.
   */
  $('[href="#first-time-login"]').click(function(event) {
    event.preventDefault();
    event.stopPropagation();
    $tgt = $(event.currentTarget);
    $tgt.data('hidden', !$tgt.data('hidden'));
    $submitBtn = $('[name="login"]');
    $flagField = $('[name="first-time-login"]');

    if (!$submitBtn.data('originaltext')) {
      $submitBtn.data('originaltext', $submitBtn.text());
      $tgt.data('originaltext', $tgt.text());
    }

    $('[data-container="form-chunk"]').slideToggle(animationSpeed);
    if ($tgt.data('hidden')) {
      $tgt.text('Login Again');
      $submitBtn.text('Next');
      $flagField.val('true');
      $('input[type=password]').removeClass('required-field').val('');
    } else {
      $tgt.text($tgt.data('originaltext'));
      $submitBtn.text($submitBtn.data('originaltext'));
      $flagField.val('false');
      $('input[type=password]').addClass('required-field');
    }
  });
});
