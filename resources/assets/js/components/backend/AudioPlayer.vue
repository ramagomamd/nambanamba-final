<script>
  import VueHowler from 'vue-howler'

  export default {
    mixins: [VueHowler],

    methods: {
        /**
       * Format the time from seconds to M:SS.
       * @param  {Number} secs Seconds to format.
       * @return {String}      Formatted time.
       */
      formatTime: function(secs) {
        var minutes = Math.floor(secs / 60) || 0;
        var seconds = (secs - minutes * 60) || 0;

        return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
      }
    }
  }
</script>

<template>
  <div class="container">
    <div class="well">
      <span>Total duration: {{ this.formatTime(Math.round(duration)) + ' min' }} </span>
      <!-- <span>Progress: {{ parseInt(progress * 100) }}%</span> -->
      <div class="progress progress-striped active">
        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" 
          aria-valuemax="100" :style="{width: (progress * 100) + '%'}"><span class="sr-only">40% Played</span>
        </div>
      </div>
      <button v-if="!playing" @click.prevent="play" class="btn btn-success"><i class="fa fa-play"></i> Play</button>
      <button v-else @click.prevent="pause" class="btn btn-warning"><i class="fa fa-pause"></i> Pause</button>
      <button v-if="playing" @click.prevent="stop" class="btn btn-danger"><i class="fa fa-stop"></i> Stop</button>
    </div>
    <div id="audio-player" class="audio-player-wrapper">
  <div class="audio-player-image">
    <span class="audio-player-song-name"></span>
  </div>

  <div class="audio-player-controls">
    <span class="audio-player-progress">
      <span class="audio-player-progress-bar"></span>
    </span>
    <span class="audio-player-button-wrappers">
      <a role="button" class="audio-player-button small icon-backward"></a>
      <a role="button" class="fa fa-play"></a>
      <a role="button" class="audio-player-button small icon-forward"></a>
    </span>
  </div>
</div>
  </div>
</template>