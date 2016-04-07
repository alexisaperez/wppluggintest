jQuery(document).ready(function($) {

	/*user profile idle timer reload tests*/
	var idleTimer = null,
			idleState = false,
			idleWait = 60000 * mmm_timeout_min, //in miliseconds
			idleClassName = "mmm-log-reloaded",
			idleClass = ".mmm-logged-content";

	$(idleClass).removeClass(idleClassName);
	$(idleClass).bind('mousemove keydown scroll', function () {     
    clearTimeout(idleTimer);
 	//alert('active');   
    if (idleState == true) {  
       // Reactivated event
      $(idleClass).removeClass(idleClassName);        
    }
  
    idleState = false;
    idleTimer = setTimeout(function () { 
      // Idle Event	
      $(idleClass).addClass(idleClassName);
    	//if ($(idleClassName).length) {
    	if ($('.mmm-logged-content').hasClass(idleClassName)){
    		//alert('class exists');
    		window.history.go(0);
        location.reload(false);//this loads the page from cache instead of server (true)  	 	
    		//location.reload(true);
    	}else{
    		idleState = false;
    	}
      idleState = true; 
    }, idleWait);
  });

  $(idleClass).trigger("mousemove");
	//end logout

	//clean temporary password
	if($("#password_temp").length > 0){
		$("#password_temp").val("");
	}
	
	/* removed since theme already support tooltip
	var v_s_term_title = "";
	$(".mmmm-tooltip .ui-icon-info").hover(
    function(){
      v_s_term_title = $(this).parent().attr("title");
      $(this).parent().attr("title", "");
    },
    function(){
      $(this).parent().attr("title", v_s_term_title);
      v_s_term_title = "";
    }
  ).on("click", function(e){
    e.preventDefault();

    var posX = e.pageX,
        posY = e.pageY;

    if($(window).width() < 1024){
      is_mobile = true;
    }

    if($(".mmm_tooltip_def").length <= 0){
	    $(this).parent().prepend("<div class=\"mmm_tooltip_def\"><h4>Help<span class=\"mmm_tooltip_def_close\"></span></h4><p>" + v_s_term_title + "</p><div class=\"mmm_tooltip_def_white_arrow\"></div><div class=\"mmm_tooltip_def_blue_arrow\"></div></div>");

	    //attach close event
		  $(".mmm_tooltip_def_close").on("click", function(e){
		    $(this).parent().attr("title", v_s_term_title);
		    v_s_term_title = "";
		  	$(".mmm_tooltip_def").remove();
		  });	    
    }

  });
  */

	//for password tooltip
	$(".mmmm-pwd-tooltip").attr("title", "Password requirements:<br>- Minimum of " + mmm_pwd_length + " characters<br>- Must be a combination of upper and lower case alphabet characters, numbers and special characters (! # $ &amp;, etc)<br>- Cannot contain user’s first or last name");

  var text = $( "#avatars_content" ).text();
	var decoded = $('#decode_temp').html(text).text();

	$( "#avatars_content" ).empty();
	$( "#decode_temp #avatars" ).addClass("hide_avatars");
  $( "#decode_temp #avatars" ).prependTo( ".avatars_chooser" );	
 
	/*user profile change links*/
	$(".ncoa_userprofile_list .link").show();
	$(".ncoa_userprofile_list .logged").show();
	$(".ncoa_userprofile_list .update").hide();

	$(".link .change").click(function(e) {  
		e.preventDefault();
		$(this).hide();
		$(this).next().show();

		$(this).parents('.ncoa_userprofile_list').find('.update').show();
		$(this).parents('.ncoa_userprofile_list').find('.logged').hide();
	});

	$(".link .cancel").click(function(e) {  
		e.preventDefault();
		$(this).hide();
		$(this).prev().show();
		
		//reset fields
		var inputEl = $(this).parents('.ncoa_userprofile_list').find('.update').children();
		$(inputEl).each(function (k, el) {
			if($(el).is("input")){
				$(el).val($(el).attr("data-origin"));
				$(el).removeClass("error");
			}else if($(el).is("select")){
				if($(el).attr("id") == "birth_month"){
					$(el).val($(el).parent().attr("data-origin").split("/")[0]);
				}else{
					$(el).val($(el).parent().attr("data-origin").split("/")[1]);
				}
			}
		})

		$(this).parents('.ncoa_userprofile_list').find('.logged').show();
		$(this).parents('.ncoa_userprofile_list').find('.update').hide();
		
	});

	
	/*new password validation*/
	var password = $('#password');
	var password_re = $('#password_re');
	var pass_check = $('.password-check');
	var pass_re_check = $('.password_re-check');
	var email = $('#email');
	var form_profile = $('#swpm-editprofile-form');
	var helperText = {
			charLength: $('.helper-text .length').get(0),
			lowercase: $('.helper-text .lowercase').get(0),
			uppercase: $('.helper-text .uppercase').get(0),
			special: $('.helper-text .special').get(0),
			number: $('.helper-text .number').get(0),
			last: $('.helper-text .last').val()
	};

	//globally scoped object
	var pattern = {
			charLength: function() {
					if (password.val().length >= mmm_pwd_length) {
							return true;
					}
			},
			lowercase: function() {
					var regex = /^(?=.*[a-z]).+$/; // Lowercase character pattern

					if (regex.test(password.val())) {
							return true;
					}
			},
			uppercase: function() {
					var regex = /^(?=.*[A-Z]).+$/; // Uppercase character pattern

					if (regex.test(password.val())) {
							return true;
					}
			},
			number: function() {
					var regex = /^(?=.*[0-9+]).+$/; // at least one number 

					if (regex.test(password.val())) {
							return true;
					}
			},
			special: function() {
					var regex = /[^\w\s]/; // at least one Special character  

					if (regex.test(password.val())) {
							return true;
					}
			},
			last: function() {
				var first_n = $('#first_name').val(),
						last_n = $('#last_name').val();

				if($(".swpm-login-widget-logged").length > 0 || $(".ncoa-password-reset-widget-form").length > 0){
					first_n = $('#fn-entry').text();
					last_n = $('#ln-entry').text();
				}
				
				first_n = first_n.toLowerCase();
				last_n = last_n.toLowerCase();

				$('.last').addClass('valid').removeClass('notvalid');
				if ( first_n == "" && last_n != ""){
				 	//blank first name
				 	if (password.val().toLowerCase().indexOf(last_n) != -1) {
						$('.last').addClass('notvalid').removeClass('valid');
						return true;
					}
				}else if ( last_n == "" && first_n != ""){
				 	//blank last name
				 	if (password.val().toLowerCase().indexOf(first_n) != -1) {
						$('.last').addClass('notvalid').removeClass('valid');
						return true;
					}
				}else if ( last_n != "" && first_n != ""){
					if ((password.val().toLowerCase().indexOf(last_n) != -1) || (password.val().toLowerCase().indexOf(first_n) != -1)) {
							$('.last').addClass('notvalid').removeClass('valid');
							return true;
					}
				}
			}
	};

	 
	 


	$('.password-error').hide();
	$('#helper-text-dialog').hide();

	$(pass_check , pass_re_check).removeClass('pw_valid pw_notvalid');
 	$('#password, #password_re ').removeClass('error');
	$('.helper-text li').removeClass('notvalid'); 	

	if ($(window).width() < 484) {
		$('#password').on('focus',function(e) { 
			$(window).scrollTop($('#password').offset().top -60);
		});
	}

	//password
	$('#password').on('focus',function(e) { 
		$('#helper-text-dialog').show();
	})
	.on('blur',function(e) { 		
		$('#helper-text-dialog').hide();

		 if ( $('.notvalid').length > 0) {         		
			$('.password-error').show();
			$('#password').addClass('error');
			$(pass_check).removeClass('pw_valid').addClass('pw_notvalid');
		} else if (  $('.notvalid').length < 0 && $( password.length  != 0)  ){
			$('.password-error').hide();
			$('#password').removeClass('error');
			 $(pass_check).removeClass('pw_notvalid').addClass('pw_valid');
		}

		if (password.val() != password_re.val()  ) {
		 	$('#password_re').addClass('error');
	 		$(pass_re_check).removeClass('pw_valid').addClass('pw_notvalid');
	 	}else if(password.val().length > 0){
		 	$('#password_re').removeClass('error');
	 		$(pass_re_check).addClass('pw_valid').removeClass('pw_notvalid');
	 	}
	})
	.on('keyup',function(e) {
		if ( $('.notvalid').length <= 0) {
			$('.password-error').hide();
			$(pass_check).removeClass('pw_notvalid').addClass('pw_valid');
		}
		
		if ( password.val()  === password_re.val()) {
	 		$(pass_check).removeClass('pw_notvalid').addClass('pw_valid');
	 		$('.password-error').hide();
	 	}else{
	 		$(pass_re_check).removeClass('pw_valid').addClass('pw_notvalid');
	 	}
	 	if( ( (e.keyCode == 46) ||  (e.keyCode == 8) ) && (password.val()  !== password_re.val()) ) {
	 		$(pass_check).removeClass('pw_valid').addClass('pw_notvalid');	 		
	 	} else {
	 			$('.password_re-error, .password-error').hide();
	 	}
	 	if( ( (e.keyCode == 46) ||  (e.keyCode == 8) ) && ( $('helper-text li').length === $('helper-text li.valid').length ) ) {
	 		if ($('#password').hasClass('error')) {
				$('#password').removeClass('error');
			}
			if ($('.password-check').hasClass('pw_notvalid')){
				$('.password-check').removeClass('pw_notvalid').addClass('pw_valid');
			}
	 	}

 		if ($(pass_check).hasClass('pw_notvalid')) {
 	 		$('.password-error').show();
 	 		$('#password').addClass('error');
 	 	} else {
 	 		$(pass_check).removeClass('pw_notvalid').addClass('pw_valid');
 	 		$('#password').removeClass('error');
 	 		$('.password-error').hide();	 		
 	 	}

 	 	$('.helper-text li').addClass('notvalid'); 
			// Check that password is a minimum of 8 characters
			patternTest(pattern.charLength(), helperText.charLength);
			// Check that password contains a lowercase letter		
			patternTest(pattern.lowercase(), helperText.lowercase);
			// Check that password contains an uppercase letter
			patternTest(pattern.uppercase(), helperText.uppercase);
			// Check that password contains a special character
			patternTest(pattern.special(), helperText.special);
			// Check that password contains a number 
			patternTest(pattern.number(), helperText.number);
			// Check that password does not contain first last name
			patternTest(pattern.last(), helperText.last);
			// Check that all requirements are fulfilled
			if (hasClass(helperText.charLength, 'valid') &&
					hasClass(helperText.lowercase, 'valid') &&
					hasClass(helperText.uppercase, 'valid') &&
					hasClass(helperText.special, 'valid') &&
					hasClass(helperText.number, 'valid') &&
					hasClass(helperText.last, 'valid')					
			) {
				$('.helper-text li').addClass('valid').removeClass('notvalid');	
			} 
	});

	//password confirmation
	 $('#password_re').on('focus',function(e) { 
	 	if(password_re.val().length > 0){
		 	if (password.val()  != password_re.val()  ) {
		 		$('#password_re').addClass('error');
		 		$(pass_re_check).removeClass('pw_valid').addClass('pw_notvalid');
		 		$('.password_re-error').show();
		 	}
		} 
	 })
	 .on('keyup',function(e) {  
	 	  if (  ( password.val()  === password_re.val() ) && $('.notvalid').length <= 0) {
	 		$(pass_re_check).removeClass('pw_notvalid').addClass('pw_valid');
	 		$('#password_re').removeClass('error');
	 		$('.password_re-error').hide();

	 	} else {
	 		$(pass_re_check).addClass('pw_notvalid').removeClass('pw_valid');
	 		$('#password_re').addClass('error');
	 		$('.password_re-error').show();
	 	}

	 	if ($(pass_re_check).hasClass('pw_notvalid')) {
	 	 		$('.password_re-error').show();
	 	 		$('#password_re').addClass('error');
	 	 	} else {
	 	 		$('.password_re-error').hide();
	 	 		$(pass_re_check).removeClass('pw_notvalid').addClass('pw_valid');
	 	 		$('#password_re').removeClass('error');	 	 		
	 	 	}	
	 	});
	 	
	 	
	//  	$('#password, #password_re' ).on('focus blur keyup',function(e) {
	//  			if( (password.val() === '') && (password_re.val() === '') ) {
	//  				$('#password_re , #password').removeClass('error');
	//  				$('.password_re-error, .password-error').hide();
	//  				$('.password_re-check , .password-check').removeClass('pw_notvalid').removeClass('pw_valid');
	//  				$('.helper-text li').removeClass('notvalid').removeClass('valid');
	//  			}
	//  	});
		

 
	//if user changes first, last name input, recapture name, revalidate pattern match
	$('#last_name,#first_name').on('blur', function() {
		//$('.helper-text li').removeClass('notvalid'); 
	  first_n = $('#first_name').val();//need to capture value again
	  last_n = $('#last_name').val();//need to capture value again
		
		if($("#password").val().length > 0){
		 	// Check that password does not contain first last name
			patternTest(pattern.last(), helperText.last);

			if($('.helper-text .last').hasClass('notvalid')){
	 	 		$('#password').addClass('error');
				$('.password-error').show();
				$('.password-check').removeClass('pw_valid').addClass('pw_notvalid');
			}else{
	 	 		$('#password').removeClass('error');
				$('.password-error').hide();
				$('.password-check').removeClass('pw_notvalid').addClass('pw_valid');
			}
		}
		 /*
		 //if all itenms are ticked valid do this
			if ( $('helper-text li').length === $('helper-text li.valid').length )
			{
				if ($('input#password').hasClass('error')) {
					$('input#password').removeClass('error');
				}
				if ($('.password-check').hasClass('pw_notvalid')){
					 $('.password-check').removeClass('pw_notvalid').addClass('pw_valid');
				}
				if ( $('.password-error').length > 0 ){
					$('.password-error').hide();
				}
			}
			*/


	

	}); 	
		//trim leading, trailing whitespaces from email, password, password re on submit
			$(email).on('blur', function(){
	     	//alert('replaced');
	   	 email = email.replace(/^(0|\+44) */, '');/* for older browsers*/
	   	  email = email.trim();
	   	 return email;
	     });
   		$(password).on('blur', function(){
	     	//alert('replaced');
	      password = password.replace(/^(0|\+44) */, '');
	     password = password.trim();

	     	return password;
	     });
		$(password_re).on('blur', function(){
	     	//alert('replaced');
	     password_re = password_re.replace(/^(0|\+44) */, '');
	     password_re = password_re.trim();

	     	return password_re;
	     });


		$(form_profile).on("submit", function(e){ 
		     e.preventDefault();
		     password_re = password_re.trim();
		     password = password.trim();
		      email = email.trim();
		     $(this.submit();
		});
	/*end new password validation */
		//trim leading, trailing whitespaces from email, password, password re on submit
			$(email).on('blur', function(){
	     	//alert('replaced');
	   	 email = email.replace(/^(0|\+44) */, '');/* for older browsers*/
	   	  email = email.trim();
	   	 return email;
	     });
   		$(password).on('blur', function(){
	     	//alert('replaced');
	      password = password.replace(/^(0|\+44) */, '');
	     password = password.trim();

	     	return password;
	     });
		$(password_re).on('blur', function(){
	     	//alert('replaced');
	     password_re = password_re.replace(/^(0|\+44) */, '');
	     password_re = password_re.trim();

	     	return password_re;
	     });


		$(form_profile).on("submit", function(e){ 
		     e.preventDefault();
		     password_re = password_re.trim();
		     password = password.trim();
		      email = email.trim();
		     $(this.submit();
		});

	/*user profile birthdate*/
	$('#swpm-editprofile-form').submit(function(){
	    $('.current_birthdate').hide();  
	});


	/*user profile avatars area*/
	$(".hide_avatars").removeClass("no_js");
	$(".main_avatar").css("display", "inline-block");

	$(".change_avatar").click(function(e) {
		e.preventDefault();
		$("#avatars").removeClass("hide_avatars").fadeIn("slow");
		$("#change_avatar").hide();
		$(".cancel_link").removeClass("hide_avatars");
	  $(".default").removeClass("show").addClass("hide_avatars").fadeOut("slow");
  });

	$(".avatar input[type=radio]").click(function() {
		$(this).parent(".avatar").removeClass("fade");
		// event.preventDefault();     $(".avatars_chooser").find('avatar.hide_avatars').removeClass("hide_avatars").fadeIn("slow");
	  $(".default").removeClass("show").addClass("hide_avatars").fadeOut("slow");
	  $(this).parent(".avatar").removeClass("fade");
	  //update profile
	}); 

	$(".avatar").addClass("fade");
 	$(".avatar.fade").click(function(){
 		$(this).removeClass("fade");
 		$(".avatar").addClass("fade");
 		$(this).removeClass("fade");
 	});
	
	$(".cancel_link").click(function(e) {
		e.preventDefault();
		$(".avatar").addClass("fade");
		$("#avatars").addClass("hide_avatars").fadeOut("slow");
		$("#change_avatar").show().fadeIn("slow");
		$(".cancel_link").addClass("hide_avatars").fadeOut("slow");
	  $(".default").addClass("show").removeClass("hide_avatars").fadeIn("slow");
	 }); 



	// Questionnaire Submit		 	
	$(document).on('submit','.ncoa_userprofile_form', function(e) {

		if(!$("#submit").hasClass("btn-disabled")){
			var results = new Array();
			var first_error = false;

			//disable button
			$("#submit").attr("disable", true).addClass("btn-disabled");

			if($('#birth_month').length > 0){		
				var month = $('#birth_month').find('option:selected').val();
				var year = $('#birth_year').find('option:selected').val();
				 
				if ( (month == "" || year == "") && !(month == "" && year == "")) {
	  			$('#birth_month').val("");
	  			$('#birth_year').val("");
	  			 results.push( 'Please select both month and year' );
	  			 $('#birth_month, #birth_year').addClass( 'error' );
				 	if ( ! first_error ) { first_error = $('#birth_month'); }            
			 	}			
			}
	 

			// Required Field Validation
			$( this ).find( '.required-field' ).each( function(k, el) {
				if($(el).is(':input[type=text]') || $(el).is(':input[type=password]')){
					if( $(el).val() == '' ) {
						results.push( '&quot;' + $(el).attr("data-label") + '&quot; is required.' );
						$(el).addClass( 'error' );
						if( ! first_error ) { first_error = $(el); }
					} else { $(el).removeClass( 'error' ); }			
				}else if($(el).is(':input[type=checkbox]')){
					if(!(document.getElementById( $(el).attr("id")).checked)) {
						results.push( '&quot;' + $(el).attr("data-label") + '&quot; must be answered.' );
						$(el).next().addClass( 'label_error' );
						if( ! first_error ) { first_error = $(el); }
					} else { $(el).next().removeClass( 'label_error' ); }
				}
			});
			 

			// Optional Email Address Validation
			$( this ).find( '.password' ).each( function(k, el) {
				var field = $(el);
				var error = false;
				var label = $(el).attr("data-label");
				var fn = '';
				var ln = '';

				// Only Proceed If Completed
				if( field.val() == '' ) { return; }
				
				var pattern = new RegExp( /^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\W).*$/g );
				//var repeat_pattern = new RegExp( /(.)\1{2,}/ig ); //no more than 2 repeating letters

				if( ! pattern.test( field.val() ) ) { error = true; }
				// Report Incomplete Length
				if( field.val().length < mmm_pwd_length ) { error = true; }
				//if( repeat_pattern.test( field.val()) ) { error = true; }

				if($("#fn-entry").length == 0){
					//create account
					if($("#first_name").length != 0){
						fn = $("#first_name").val().toLowerCase();
						ln = $("#last_name").val().toLowerCase();
					}
				}else{
					//update
					fn = $("#fn-entry").text().toLowerCase();
					ln = $("#ln-entry").text().toLowerCase();
				}

				if(fn != '' && fn.length > 2){
					var firstname_pattern = new RegExp(fn, 'ig' );
					if( firstname_pattern.test( field.val()) ) { error = true; }
				}
				if(ln != '' && ln.length > 2){
					var lastname_pattern = new RegExp(ln, 'ig' );
					if( lastname_pattern.test( field.val()) ) { error = true; }
				}

				
				// Report Priblems
				if( error ) {
					if(label.indexOf("emporary")>=0){
						results.push( '&quot;' + label + '&quot; is not valid.' );
					}else{
						results.push( '&quot;' + label + '&quot; is not strong, please revise the requirements:<br>- Minimum of ' + mmm_pwd_length + ' characters,<br>- Must be a combination of upper and lower case alphabet characters, numbers and special characters (! # $ &, etc)<br>- Cannot contain user\'s first or last name' );
					}
					field.addClass( 'error' );
					if( ! first_error ) { first_error = field; }
				} else {
					field.removeClass( 'error' );
				}
			});

			// Optional Email Address Validation
			$( this ).find( '.email' ).each( function(k, el) {
				var field = $(el);
				var error = false;
				var label = $(el).attr("data-label");

	 
				// Only Proceed If Completed
				if( field.val() == '' ) { return; }

				var pattern = new RegExp( /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i );

				if( ! pattern.test( field.val() ) ) { error = true;  }
				

				// Report Priblems
				if( error ) {
					results.push( '&quot;' + label + '&quot; must be a valid email address.' );
					field.addClass( 'error' );
					if( ! first_error ) { first_error = field; }
				} else {
					field.removeClass( 'error' );
				}

			});

	 
			//zip code validation
			$( this ).find( '.zipcode' ).each( function(k, el) {
				var field = $(el);
				var error = false;
				var error_ajax = '';
				var label = $(el).attr("data-label");

				// Only Proceed If Completed
				if( field.val() == '' ) { return; }

				// Report Incomplete Length
				var pattern = new RegExp( /^\d{5}(?:[-\s]\d{4})?$/i );
				if( ! pattern.test( field.val() ) ) { error = true; }

				// Report Priblems
				if( error ) {
					results.push( '&quot;' + label + '&quot; is not valid.' );
					field.addClass( 'error' );
					if( ! first_error ) { first_error = field; }
				} else {
					field.removeClass( 'error' );
				}				  

			});

		
			// Check Confirmation Fields
			$( this ).find( '.confirm' ).each( function(k, el) {
				var curr_id = $(el).attr("id");
				var first = $( '#' + curr_id );
				var second = $( '#' + curr_id + '_re');
				var label = $( el ).attr( 'data-label' );

				if(!($(first).hasClass("error")) && !($(second).hasClass("error"))){
					if( $(first).val() != $(second).val() ) {
						//results.push( '&quot;' + label + '&quot; must match the ' + label2 );
						//results.push( '&quot;' + label + '&quot; must match the ' + label + " confirmation");
						$(second).empty();
						results.push('Passwords do not match please re-enter the confirmation password');
						$(second).addClass('error');
						if( ! first_error ) { first_error = $(second); }
					}else{
						$(second).removeClass('error');
					}
				}

			});

			//attache GA click event on embed form
			//ga('send', 'event', l_category, 'Embed form input', l_label);
			
			if( results.length > 0) {
				error_modal(first_error, results);
			  e.preventDefault(); //STOP default action
			}

		}else{
			e.preventDefault(); //STOP default action
		} //end btn-disabled

	} );


	// Zip Code Changes
	$( '.ncoa_userprofile_form input[name=zip]' ).on( 'keyup', function() {
		var field = $( this );
		var submit = $( this ).closest( 'form' ).find( 'input[type=submit]' );

		// Proceed Only When Fully Entered
		if( field.val().length != 5 ) { return; }

		// Disable Submit Until Completed
		submit.attr( 'disabled', 'disabled' );

		// AJAX Validation
		$.ajax( {
			type: 'POST',
			url: jQuery( '#mqc_ajaxurl' ).attr( 'data' ),
			dataType: 'html',
			data: { action: 'mqc_zip', mqc_zip: field.val() },
			success: function( data ) {

				// Display Errors
				if ( data.indexOf( 'ERROR' ) > -1 ) {
					field.val( '' );
					var message = '<ul><li>Please enter a valid zip code for the 50 U.S. States or the District of Columbia.</li></ul>';
					field.addClass( 'error' );
					$( '#ncoa-mmmm-message .message' ).html( message );
					$( '#ncoa-mmmm-message' ).dialog( {
						width: 760,
						modal: true,
						buttons: {
							Close: function() {
								$( this ).dialog( 'close' );
								field.focus();
							}
						}
					} );
				}else{
					field.removeClass( 'error' );
				}

				// Enable Submit
				submit.removeAttr( 'disabled' );
			}
		} );
	} );


});

function error_modal(first_error, results){
	if( first_error ) { first_error.focus(); }
	var message = '<ul><li>' + results.join( '</li><li> ' ) + '</li></ul>';
	jQuery( '#ncoa-mmmm-message .message' ).html( message );
	jQuery( '#ncoa-mmmm-message' ).dialog( {
		width: 760,
		modal: true,
		buttons: {
			Close: function() {
				jQuery( this ).dialog( 'close' );
				if( first_error ) { first_error.focus(); }
				jQuery("#submit").attr("disable", false).removeClass("btn-disabled");
			}
		}
	} );
}

function patternTest(pattern, response) {
	if (pattern) {
			addClass(response, 'valid');
			removeClass(response, 'notvalid');
	} else {
			removeClass(response, 'valid');
			addClass(response, 'notvalid');
	}
}

function addClass(el, className) {
	jQuery(el).addClass(className);
}

function removeClass(el, className) {
	jQuery(el).removeClass(className);
}

function hasClass(el, className) {
	if (el.classList) {		
		return el.classList.contains(className);
	} else {
		new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
	}
}
