jQuery(document).ready(function ($) {
	$('.widefat.mmpu-membership-levels.ui-sortable').css('opacity','.6');
	$('.add-new-h2').click(function() { return false; }).css('color','gray');
	$('a.add-new-h2:hover').click(function() { return false; }).css('background-color','gray');
	$('#posts-filter input').prop('disabled', true);
	$('.button-primary').addClass('disabled');
	$('.button-secondary').addClass('disabled');
	$('.level_name a').removeAttr('href').css({'text-decoration':'none', 'color':'gray' });
	$('a.button-primary.disabled').removeAttr('href');
	$('a.button-secondary.disabled').removeAttr('href');
	$('a.add-new-h2').removeAttr('href').addClass('disabled');
});