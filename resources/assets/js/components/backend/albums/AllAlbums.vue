<template>
	<div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">{{ title }}</h3>

            <div class="box-tools">
                <div class="pull-right mb-10 hidden-sm hidden-xs">
                    <button class="btn btn-primary btn-xs" @click="toggle('all')">
                        {{ title }}
                    </button>
                    <button class="btn btn-success btn-xs" @click="toggle('create')">
                        Create
                    </button>
                </div><!--pull right-->
            </div><!--box-tools-->
        </div><!-- /.box-header -->
    
        <div class="box-body">
            <div class="table-responsive" v-if="albums.length"> 
            	<table id="users-table" class="table table-striped table-condensed table-hover">
				    <thead>
				        <tr>
				            <th>ID</th>
				            <th>Title</th>
				            <th>Slug</th>
				            <th>Genres</th>
				            <th>Category</th>
				            <th>Tracks Count</th>
				            <th>Actions</th>
				        </tr>
				    </thead>

				    <tbody>
				        <tr v-for="album in albums">
				            <td>{{ album.id }}</td>
				            <td>{{ album.title }}</td>
				            <td>{{ album.slug }}</td>
				            <td>{{ album.genre_names }}</td>
				            <td>{{ album.category.name }}</td>
				             <td>{{ album.tracks_count }}</td>
				            <td>{!! album.action_buttons !!}</td>
				        </tr>
				    </tbody>
				</table> 
				<paginator :dataSet="dataSet" @changed="fetch"></paginator>            
            </div><!--table-responsive-->
            <div v-else>
            	<p class="lead">No Albums Yet</p>
            </div>
        </div><!-- /.box-body -->  
    </div>
</template>

<script>
	export default {
		data() {
			return {
				title: "All Albums",
				dataSet: false,
				albums: {}
			}
		},
		methods: {
			fetch(page) {
                axios.get(this.url(page)).then(this.refresh);
            },

            url(page) {
                if (! page) {
                    let query = location.search.match(/page=(\d+)/);

                    page = query ? query[1] : 1;
                }

                return `${location.pathname}?page=${page}`;
            },

            refresh({data}) {
                this.dataSet = data;
                this.albums = data.data;

                window.scrollTo(0, 0);
            },

			toggle(page) {
				this.$emit('toggled', page)
			}
		},
		created() {
			this.fetch();
		}
	}
</script>