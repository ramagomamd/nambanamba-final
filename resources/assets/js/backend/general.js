import Multiselect from 'vue-multiselect'
import SingleUpload from '../components/backend/SingleUpload'

var app = new Vue({
	el: '#app',
  components: {
    Multiselect, SingleUpload
  },
	data: {
      albums: false,
      singles: true,
      view: true,
      add: false,
      uploadZip: false,

      album: {
          artists: [],
          title: '',
          description: ''
        },
      artists: [],
    },

    methods: {
        toggleMain() {
          this.albums = !this.albums
          this.singles = !this.singles
        },
        toggleViewAdd() {
          this.view = !this.view
          this.add = !this.add
        },
        addArtist(newArtist) {
          this.album.artists.push(newArtist)
          this.artists.push(newArtist)
        },
	},
  created() {
      axios.get('/admin/music/artists').then(response => {
        this.artists = _.keysIn(response.data)
      })
  },
	computed: {
	    isFormDirty() {
	      return Object.keys(this.fields).some(key => this.fields[key].dirty);
	    },
	    isFormInvalid() {
    	return Object.keys(this.fields).some(key => this.fields[key].invalid);
    	}
	 }
})