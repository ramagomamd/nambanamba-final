<template>
	<div class="box-body">
		<form method="POST" enctype="multipart/form-data" @submit.prevent="store">
		    <div class="clearfix" style="padding-top: 15px"></div>
		    <div class="col-md-11">
			    <div class="form-group">
			        <label for="artists" class="col-lg-2 control-label">
			            Artists:
			        </label>

			        <div class="col-lg-10 mb-1">
			            <multiselect v-model="album.artists" tag-placeholder="Add this an artist" 
			                class="form-control"
			                placeholder="Search or add a artist" :max="4" :allow-empty="false" 
			                :options="artists" :show-labels="true" :multiple="true" 
			                :taggable="true" @tag="addArtist"
			                :hide-selected="true" style="width: 97.5%" data-vv-as="Album Artists">
			            </multiselect>
			        </div><!--col-lg-10-->
			    </div><!--form control-->

			    <div class="form-group">
			        <label for="title" class="col-lg-2 control-label">
			            Title:
			        </label>

			        <div class="col-lg-10 mb-1">
			            <input type="text" name="title" class="form-control" data-vv-as="Album Title"
			                v-validate="'required|min:2|max:190'"  v-model="album.title"
			                placeholder="Album Title">
			            <span class="text-danger" v-if="errors.has('album.title')" 
			                v-text="errors.first('album.title')">
			            </span>
			        </div><!--col-lg-10-->
			    </div><!--form control-->

			    <div class="form-group">
			        <label for="description" class="col-lg-2 control-label">
			            Description:
			        </label>

			        <div class="col-lg-10 mb-1">
			            <textarea rows="4" cols="50" name="description" maxlength="500"
			                class="form-control" data-vv-as="Album Description" v-validate="'min:2|max:190'"
			                placeholder="Album Description" v-model="album.description">
			            </textarea>
			            <span class="text-danger" v-if="errors.has('description')" 
			                v-text="errors.first('description')">
			            </span>
			        </div><!--col-lg-10-->
			    </div><!--form control-->

			    <div class="form-group">
			        <label for="category" class="col-lg-2 control-label">
			            Categories:
			        </label>
			        <div class="col-lg-10 mb-1">
			            <multiselect v-model="album.category" tag-placeholder="Add this as new category" 
			                placeholder="Search or add a category" :max="5" :allow-empty="false" class="form-control"
			                :options="categories" :show-labels="true" :taggable="true" @tag="addCategory" 
			                :hide-selected="true" style="width: 97.5%">
			            </multiselect>
			        </div>
			    </div>

			    <div class="form-group">
			        <label for="genres" class="col-lg-2 control-label">
			            Genres:
			        </label>
			        <div class="col-lg-10 mb-1">
			            <multiselect v-model="album.genres" tag-placeholder="Add this as new genre" 
			                class="form-control"
			                placeholder="Search or add a genre" :max="4" :allow-empty="false" 
			                :options="genres" :show-labels="true" :multiple="true" 
			                :taggable="true" @tag="addGenre"
			                :hide-selected="true" style="width: 97.5%" data-vv-as="Album Main Category">
			            </multiselect>
			        </div>
			    </div>

			   <div class="form-group" v-if="album.genres.length">
			        <label for="genre" class="col-lg-2 control-label">
			            Main Genre:
			        </label>

			        <div class="col-lg-2 mb-1" v-for="genre in album.genres">
			             <div class="btn btn-primary btn-xs">
			                 <input type="radio" class="radio-inline" v-model="album.genre" :value="genre" checked> 
			                 <label for="genre">{{ genre }}</label>
			             </div>
			        </div> <!--col-lg-2-->
			    </div><!--form control-->
			   
		    </div>
			
			<div class="col-md-12">
			    <div class="box-footer with-border mb-1">
			        <div class="pull-left">
			            <button class="btn btn-danger btn-md" @click.prevent="broadcast">
			                <i class="fa fa-close" data-toggle="tooltip" data-placement="top" 
			                    title="Cancel">
			                </i>
			                Cancel
			            </button>
			        </div><!--pull-left-->

			        <div class="pull-right">
			            <button class="btn btn-success btn-md" 
			                :disabled="errors.any() || ! isFormDirty || isFormInvalid">
			                <i class="fa fa-pencil" data-toggle="tooltip" data-placement="top" 
			                    title="Create">
			                </i> 
			                Create
			            </button>
			        </div><!--pull-right-->
			    </div>
		        <div class="clearfix"></div>
		    </div> 
		</form>
	</div>
</template>

<script>
	import Multiselect from 'vue-multiselect'

	export default {
		components: { Multiselect },
		data() {
			return {
				album: {
					artists: [],
					title: '',
					category: '',
					genres: [],
					genre: '',
					description: ''

				},
				artists: [],
				genres: [],
				categories: []
			}
		},
		methods: {
			store() {
				axios.post('/admin/music/albums', {
					artists: this.album.artists,
					title: this.album.title,
					category: this.album.category,
					genres: this.album.genres,
					genre: this.album.genre,
					description: this.album.description
				})
			},
            onLoad(cover) {
                this.cover = cover.src;

                this.persist(cover.file);
            },

            persist(cover) {
                let data = new FormData();

                this.album.cover = cover
                // data.append('cover', cover);

                /*axios.post(`/api/users/${this.user.name}/cover`, data)
                    .then(() => flash('Avatar uploaded!'));*/
            },
            addArtist(newArtist) {
	          this.artists.push(newArtist)
	          this.album.artists.push(newArtist)
	        },
	        addGenre(newGenre) {
	          this.genres.push(newGenre)
	        },

	        addCategory(newCategory) {
	          this.categories.push(newCategory)
	          this.album.category = newCategory
	        }
        },
		created() {
			axios.get('/admin/music/artists').then(response => {
				this.artists = _.keysIn(response.data)
			})
			axios.get('/admin/music/categories').then(response => {
				this.categories = _.keysIn(response.data)
			})
			axios.get('/admin/music/genres').then(response => {
				this.genres = _.keysIn(response.data)
			})
		},
		computed: {
		    isFormDirty() {
		      return Object.keys(this.fields).some(key => this.fields[key].dirty);
		    },
		    isFormInvalid() {
	    	return Object.keys(this.fields).some(key => this.fields[key].invalid);
	    	}
		 },
	}
</script>

<style scoped>
	@import '~vue-multiselect/dist/vue-multiselect.min.css';
    .mb-1 { margin-bottom: 1em; }
    [v-cloak] { display: none; }
</style>