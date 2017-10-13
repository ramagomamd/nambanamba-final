<script>
	export default {
		data() {
			return {
				title: "All Genres",
				dataSet: false,
				genres: {}
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
                this.genres = data.data;

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