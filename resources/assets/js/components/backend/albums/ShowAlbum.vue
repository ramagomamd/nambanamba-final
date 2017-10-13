<template>
	<div>
	<div class="box box-success">
        <ol class="breadcrumb">
            <li v-show="album.category">
                <a :href="`admin/music/categories/ + ${album.category.slug}`">
                    {{ album.category.name }}
                </a>
            </li>
            <li v-show="album.genre">
                <a :href="`admin/music/categories/genres/${album.category.slug}/${album.genre.slug}`">
                    {{ album.genre.name }}
                </a>
            </li>
            <li>
            	<a :href="`admin/music/categories/genres/albums/${album.category.slug}/${album.genre.slug}`">
                    Albums
                </a>
            </li>
            <li class="active">{{ album.full_title }}</li>
        </ol>

        <div class="box-header with-border">
            <h3 class="box-title">
                <span>View Album</span>
                <small>{{ album.full_title }}</small>
            </h3>
            <div class="box-tools pull-right" style="margin-right: 7px">
                <button class="btn btn-info btn-md" @click.prevent="toggle('show')" :disabled="view">
                    <i class="fa fa-eye" data-toggle="tooltip" data-placement="top" 
                        title="View">
                    </i>
                    View
                </button>
                <button class="btn btn-success btn-md" @click="upload = true" :disabled="upload">
                    <i class="fa fa-upload" data-toggle="tooltip" data-placement="top" 
                        title="Upload">
                  	</i> 
                  	Upload
                </button>
                <a href="Albums" class="btn btn-primary btn-md">
                    <i class="fa fa-list-ul" data-toggle="tooltip" data-placement="top" 
                        title="All Album"></i> All Albums
                </a>
                <button class="btn btn-warning btn-md" @click.prevent="toggle('edit')" :disabled="edit || upload">
                    <i class="fa fa-edit" data-toggle="tooltip" data-placement="top" 
                        title="Edit">
                    </i>
                    Edit
                </button>
                {{ album.delete_action }}
            </div>
        </div><!-- /.box-header -->
    </div>

    <div class="row" v-show="upload">
        <div class="col-md-12">
            <div class="pull-right box-tools" style="margin-right: 15px">
                <button class="btn btn-danger btn-sm" title="Remove" @click.prevent="upload = false">
                <i class="fa fa-times"></i></button>
            </div><!-- /. tools -->
            <br>
            <album-upload></album-upload>
        <div class="clearfix"></div>
        </div>
    </div><!-- /.row -->

    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title" v-show="view">Album Tracklist</h3>
            <h3 class="box-title" v-show="edit">Edit Album</h3>
            <div class="box-tools pull-right">
                <button class="btn bg-teal btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                <button class="btn bg-teal btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-12" align="center">
                    <div class="clearfix"></div>
                    <div v-if="album.cover" class="thumbnail">
                        <img :src="album.cover.url" :alt="album.title" 
                            width="265" height="265" class="img-thumbnail">
                    </div>
                    <div v-if="album.zip" class="caption">
                        <p>
                            <a :href="album.zip.url" class="btn btn-primary">
                                <span class="fa fa-download"> Full Zip Download</span>
                            </a>
                        </p>
                    </div>
                    <p class="text-center">{{ album.full_title }}</p>
                    <hr>
                    <div class="clearfix"></div>
                </div>

                <div v-if="album.tracks" class="col-md-12" align="center">
                    <div class="table-responsive">
                        <!-- @include('backend.music.albums.tracks') -->
                        <div class="pull-right" style="margin-right: 45px"> {{ tracks.links }} </div>
                    </div><!--table-responsive-->
                </div>
                <div v-else>
                	<p class="text-center">Upload Album Tracks Above</p>
                </div>
            </div>
        </div>
    </div>

    <div v-show="view" class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">Album Info</h3>
            <div class="box-tools pull-right">
                <button class="btn bg-teal btn-sm" data-widget="collapse"><i class="fa fa-minus"></i></button>
                <button class="btn bg-teal btn-sm" data-widget="remove"><i class="fa fa-times"></i></button>
            </div>
        </div>
        <div class="box-body">
            <div class="panel-body">
                <div class="caption" align="center">
                    <strong>{{ album.full_title }} </strong>
                </div><br>
                <table class="table">
                    <tr>
                        <tr>
                            <td><em>Artists:</em></td>
                            <td>
                                <strong>{{ album.artists_comma }}</strong>
                            </td>
                        </tr>
                    </tr>
                    <tr>
                        <td><em>Title:</em> </td>
                        <td>
                            <strong>{{ album.title }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td><em>Slug:</em> </td>
                        <td>
                            <strong>{{ album.slug }}</strong>
                        </td>
                    </tr>
                    <tr v-if="album.description">
                        <td><em>Description:</em> </td>
                        <td>
                            <strong>{{ album.description }}</strong>
                        </td>
                    </tr>
                    <tr v-if="album.genres">
                        <td>
                            <em v-if="album.genres.lenght == 1">
                                Genres
                            </em>
                            <em v-else>Genres</em>
                        </td>
                        <td>
                            {{ album.genre_links }}
                        </td>
                    </tr>
                    <tr v-if="album.category">
                        <td>
                            <em>
                                Category
                            </em>
                        </td>
                        <td>
                            <a :href="`/admin/music/categories/${album.category.slug}`">
                                <strong>{{ album.category.name }}</strong>
                            </a>
                        </td>
                    </tr>
                </table>

                <div class="panel-footer">
                    <p>Created: <em>{{ album.created_at }}</em></p>
                    <p>Edited: <em>{{ album.updated_at }}</em></p>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script>
	import AlbumUpload from './AlbumUpload'

	export default {
		data() {
			return {
				album: ''
			}
		},
		created() {
			axios.get('/admin/music/albums/album-id').then(response => {
				this.album = response.data
			})
		},	
		components: { 
			AlbumUpload 
		}
	}
</script>