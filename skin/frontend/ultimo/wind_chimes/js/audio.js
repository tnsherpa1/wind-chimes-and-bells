var $j = jQuery.noConflict();

$j(document).ready(function() {
	/*Audio Player Begins*/
		$j('#product-play-btn').click(function() {
		var $audioSampleLg = $j(".playAction").attr("id");
		/*console.log($audioSampleLg);*/
		var $playAudioSample = document.getElementById($audioSampleLg);
		if ($playAudioSample.paused) {
			$j("#audioControl2").removeClass('fa fa-volume-up');
			$j("#audioControl2").addClass('fa fa-pause-circle');
			$playAudioSample.play();
		} 
		else {
			$j("#audioControl2").removeClass('fa fa-pause-circle');
			$j("#audioControl2").addClass('fa fa-volume-up');
			$playAudioSample.pause();
		}
	});
	/*Audio Player Ends*/
	});



