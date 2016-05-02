var $j = jQuery.noConflict();

$j(document).ready(function() {
	/* Audio player on product grid layout */
	$j('#audioControl').click(function() {
		var audioSample = document.getElementById('ample');
		if (audioSample.paused) {
			$j("#audioControl").removeClass('fa-play-circle-o');
			$j("#audioControl").addClass('fa-pause-circle-o');
			audioSample.play();
		} 
		else {
			$j("#audioControl").removeClass('fa-pause-circle-o');
			$j("#audioControl").addClass('fa-play-circle-o');
			audioSample.pause();
		}
	});
});

