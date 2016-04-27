var $j = jQuery.noConflict();

$j(document).ready(function() {
	/* Audio player on product grid layout */
	$j('#audioControl').click(function() {
		var audioSample = document.getElementById('ample');
		if (audioSample.paused) {
			$j("#audioControl").removeClass('fa-play-circle');
			$j("#audioControl").addClass('fa-pause');
			audioSample.play();
		} 
		else {
			$j("#audioControl").removeClass('fa-pause');
			$j("#audioControl").addClass('fa-play-circle');
			audioSample.pause();
		}
	});
});

