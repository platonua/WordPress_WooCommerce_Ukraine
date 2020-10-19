jQuery('.platon_connect').click(function(){
	jQuery('.platon-popup').fadeIn(200)
	return false;
})
jQuery('input[name="your-tel"]').mask('+99 (999)-99-99-999');
jQuery('.closer span').click(function(){
	jQuery('.platon-popup').fadeOut(200)
	jQuery('#succ').slideUp(100) 
})

jQuery('#platon-form').submit(function(){
	formurl=jQuery('#platon-form').attr('action');
	jQuery(this).addClass('load')
    jQuery.ajax({
      type: "POST",
      url: formurl,
      data: jQuery(this).serialize(),
     // dataType : 'json',
      success: function (data) {
      	jQuery('#platon-form').removeClass('load')
      	//jQuery('#succ').html(data);
           jQuery('#succ').slideDown(100) 
       }
    })
   return false;
})
