var $j = jQuery.noConflict();

$j(document).ready(function() {
	/* Audio player on product grid layout */
	$j('#audioControl').click(function(){
		var audioSample = document.getElementById('ample');
		audioSample.play();
	});
});

