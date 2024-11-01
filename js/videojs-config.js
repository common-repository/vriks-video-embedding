//video-js custom config
$( document ).ready(function() {
    console.log( "ready!" );
	var player = videojs('my-vjs', {});
	player.qualityLevels();
	player.hlsQualitySelector();
});