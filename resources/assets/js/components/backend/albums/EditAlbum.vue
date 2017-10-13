<template>
	<div class="row">
		<form method="POST" enctype="multipart/form-data" @submit.prevent="update">
			<div class="col-md-11">
		        <div class="box-body">
		            <div class="form-group">
		                <label for="cover" class="col-lg-2 control-label">
		                    Album Cover
		                </label>

		                <div class="col-lg-6">
		                    <input type="file" name="cover" data-vv-as="Album Cover" 
		                        v-validate="'image|size:15000'">
		                    <span class="text-danger" v-if="errors.has('album.cover')" 
		                    	v-text="errors.first('album.cover')">
		                    </span>
		                </div><!--col-lg-10-->
		            </div><!--form control-->
		            
		            <div class="form-group">
		                <label for="artist" class="col-lg-2 control-label">
		                    Artists:
		                </label>

		                <div class="col-lg-10 control">
		                    <multiselect v-model="album.artists" tag-placeholder="Add an Artist" 
		                        placeholder="Search or add an artist" :max="5" :allow-empty="false" 
		                        class="form-control" :hide-selected="true" style="width: 97.5%" @tag="addArtist"
		                        :options="artists" :show-labels="true" :multiple="true" :taggable="true">
		                    </multiselect>
		                </div><!--col-lg-10-->
		            </div><!--form control-->

		            <div class="form-group">
		                <label for="title" class="col-lg-2 control-label">
		                    Title:
		                </label>

		                <div class="col-lg-10">
		                    <input type="text" name="title" class="form-control" data-vv-as="Album Title"
		                        v-validate="'required|min:2|max:190'" value="album.title"
		                        placeholder="Album Title">
		                    <span class="text-danger" v-if="errors.has('album.title')" 
		                        v-text="errors.first('album.title')">
		                    </span>
		                </div><!--col-lg-10-->
		            </div><!--form control-->

		            <div class="form-group">
		                <label for="categories" class="col-lg-2 control-label">
		                    Categories:
		                </label>
		                <div class="col-lg-10" style="display: inline-block;">
		                    <multiselect v-model="category" tag-placeholder="Add this as new category" 
		                        placeholder="Search or add a category" :max="5" :allow-empty="false" 
		                        class="form-control" 
		                        :options="categories" :show-labels="true" :taggable="true" @tag="addCategory" 
		                        :hide-selected="true" style="width: 97.5%">
		                    </multiselect>
		                </div>
		            </div>

		            <div class="form-group">
		                <label for="genres" class="col-lg-2 control-label">
		                    Genres:
		                </label>
		                <div class="col-lg-10" style="display: inline-block;">
		                    <multiselect v-model="genres" tag-placeholder="Add this as new genre" 
		                        placeholder="Search or add a genre" :max="5" :allow-empty="false" 
		                        class="form-control" 
		                        :options="genres" :show-labels="true" :multiple="true" :taggable="true" 
		                        @tag="addGenre" 
		                        :hide-selected="true" style="width: 97.5%">
		                    </multiselect>
		                </div>
		            </div>

		           <div class="form-group" v-if="genres.length">
		                <label for="genre" class="col-lg-2 control-label">
		                    Main Genre:
		                </label>

		                <div class="col-lg-2" v-for="genre in genres">
		                     <div class="btn btn-primary btn-xs">
		                        <input type="radio" class="radio-inline" name="genre" :value="genre" checked> 
		                        <label for="genre">{{ genre }}</label>
		                     </div>
		                </div> <!--col-lg-2-->
		            </div><!--form control-->

		            <div class="form-group">
		                <label for="description" class="col-lg-2 control-label">
		                    Description:
		                </label>

		                <div class="col-lg-10">
		                    <textarea rows="4" cols="50" name="description" maxlength="500"
		                        class="form-control" data-vv-as="Album Description" v-validate="'min:2|max:190'"
		                        placeholder="Album Description">
		                        {!! $album->description !!}
		                    </textarea>
		                    <span class="text-danger" v-if="errors.has('album.description')" 
		                        v-text="errors.first('album.description')">
		                    </span>
		                </div><!--col-lg-10-->
		            </div><!--form control-->

		        </div><!--/.box-body-->
		    </div>

		    <div class="col-md-12">
		        <div class="box-footer with-border">
		            <div class="pull-left">
		                <button class="btn btn-danger btn-md" @click.prevent="toggle('edit')">
		                    <i class="fa fa-close" data-toggle="tooltip" data-placement="top" 
		                        title="Cancel">
		                    </i>
		                    Cancel
		                </button>
		            </div><!--pull-left-->

		            <div class="pull-right">
		                <button class="btn btn-success btn-md" 
		                    :disabled="errors.any() || isFormInvalid">
		                    <i class="fa fa-pencil" data-toggle="tooltip" data-placement="top" 
		                        title="Edit">
		                    </i> 
		                    Edit
		                </button>
		            </div><!--pull-right-->
		            <div class="clearfix"></div>
		        </div> 
		    </div>
	    </form>
    </div>
</template>

<script>
	export default {
		data() {
			return {

			}
		},
		methods: {
			toggle(page) {
				this.$emit('toggled', page)
			}
		},
		created() {

		}
	}
</script>