var $k = jQuery.noConflict();

$k(document).ready(function() {
	/* Audio player on product grid layout */
	$k('#list-play-button').click(function() {
		alert("you clicked me!")
		var $audioSample = $k(".playAction").attr("id");
		console.log($audioSample);
		var $playAudioSample = document.getElementById($audioSample);
		if ($playAudioSample.paused) {
			$k("#audioControl").removeClass('fa-play-circle-o');
			$k("#audioControl").addClass('fa-pause-circle-o');
			$playAudioSample.play();
		} 
		else {
			$k("#audioControl").removeClass('fa-pause-circle-o');
			$k("#audioControl").addClass('fa-play-circle-o');
			$playAudioSample.pause();
		}
	});


});

