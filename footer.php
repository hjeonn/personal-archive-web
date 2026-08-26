</div><!-- .main-area -->

<?php
// PJAX 요청은 교체할 sidebar/main-area만 반환한다. BGM DOM은 최초 문서에 계속 남는다.
if (is_pjax_request()) return;

$bgmOn = (defined('BGM_PLAYLIST_ID') && BGM_PLAYLIST_ID !== '') || (defined('BGM_VIDEO_ID') && BGM_VIDEO_ID !== '');
if ($bgmOn):
?>
<div class="bgm-player">
  <div class="bgm-panel" id="bgmPanel">
    <div class="bgm-panel-header"><span>BGM</span><button class="bgm-panel-close" onclick="toggleBgm()">&times;</button></div>
    <div id="ytPlayerWrap" style="width:240px;height:135px;overflow:hidden;background:#000;"><div id="ytPlayer"></div></div>
    <div class="bgm-controls">
      <button id="pp" onclick="ppBgm()">&#9654;</button>
      <button onclick="if(typeof ytPlayer!=='undefined'&&ytPlayerReady)ytPlayer.previousVideo()">&#9198;</button>
      <button onclick="if(typeof ytPlayer!=='undefined'&&ytPlayerReady)ytPlayer.nextVideo()">&#9197;</button>
      <input type="range" class="bgm-volume" id="bgmVol" min="0" max="100" value="<?= BGM_DEFAULT_VOLUME ?>">
    </div>
    <div class="bgm-now-playing" id="bgmNP">-</div>
  </div>
  <button class="bgm-toggle" id="bgmBtn" onclick="toggleBgm()">&#9835;</button>
</div>
<script>
var tag=document.createElement('script');tag.src='https://www.youtube.com/iframe_api';document.head.appendChild(tag);
var ytPlayerReady=false,ytPlayer,bgmP=false;
function onYouTubeIframeAPIReady(){var pv={autoplay:<?=BGM_AUTOPLAY?'1':'0'?>,controls:0,disablekb:1,fs:0,modestbranding:1,rel:0};
<?php if(BGM_PLAYLIST_ID):?>pv.listType='playlist';pv.list=<?=json_encode(BGM_PLAYLIST_ID)?>;<?php endif;?>
ytPlayer=new YT.Player('ytPlayer',{height:'135',width:'240',<?php if(BGM_VIDEO_ID&&!BGM_PLAYLIST_ID):?>videoId:<?=json_encode(BGM_VIDEO_ID)?>,<?php endif;?>playerVars:pv,events:{onReady:function(){ytPlayerReady=true;ytPlayer.setVolume(<?=(int)BGM_DEFAULT_VOLUME?>);try{var v=localStorage.getItem('bgm_v');if(v!==null){ytPlayer.setVolume(+v);document.getElementById('bgmVol').value=v;}}catch(x){}},onStateChange:function(e){if(e.data===YT.PlayerState.PLAYING){bgmP=true;document.getElementById('pp').innerHTML='&#9646;&#9646;';document.getElementById('bgmBtn').classList.add('playing');try{var d=ytPlayer.getVideoData();if(d&&d.title)document.getElementById('bgmNP').textContent=d.title;}catch(x){}}else{bgmP=false;document.getElementById('pp').innerHTML='&#9654;';document.getElementById('bgmBtn').classList.remove('playing');}}}});}
function toggleBgm(){document.getElementById('bgmPanel').classList.toggle('open');}
function ppBgm(){if(!ytPlayerReady)return;bgmP?ytPlayer.pauseVideo():ytPlayer.playVideo();}
document.getElementById('bgmVol').addEventListener('input',function(){if(ytPlayerReady){ytPlayer.setVolume(+this.value);try{localStorage.setItem('bgm_v',this.value);}catch(x){}}});
</script>
<?php endif; ?>

<footer class="site-footer">&nbsp;</footer>
</body>
</html>
