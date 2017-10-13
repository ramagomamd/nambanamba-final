<template>
	<div>
		<add-album v-if="create"></add-album>
		<edit-album v-if="edit"></edit-album>
		<show-album v-if="show"></show-album>
	</div>
</template>

<script>
	import AddAlbum from './AddAlbum'
	import EditAlbum from './EditAlbum'
	import ShowAlbum from './ShowAlbum'

	export default {
		data() {
			return {
				all: true,
				create: false,
				show: false,
				edit: false
			}
		},
		methods: {
			toggle(page) {
				this.all = (page == "all" ? true : false)
				this.create = (page == "create" ? true : false)
				this.show = (page == "show" ? true : false)
				this.edit = (page == "edit" ? true : false)
			}
		}, 
		created() {
			this.$on('toggled', data => {
				this.toggle(data)
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
		components: {
			AddAlbum, EditAlbum, ShowAlbum
		}
	}
</script>