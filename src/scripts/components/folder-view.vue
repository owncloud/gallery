<template>
    <div id="gallery" class="gallery-folder-view">
        <p>
            Hi there
        </p>
    </div>
</template>
<script>
export default {
    name : 'Folder View',
    data () {
        return {
            albums : {},
            files : []
        }
    },
    mounted () {
        this.fetchImageList();
    },
    methods : {
        fetchImageList () {
            $.ajax({
                url : this.apiEndpoint('files/list'),
                method : 'get',
                data : {
                    location : this.path,
                    mediatypes : 'image/png;image/jpeg;image/gif;image/x-xbitmap;image/bmp'
                }
            }).done( (data) => {
                this.albums = data.albums;
                this.files  = data.files;
            });
        }
    },
    computed : {
        path () {
            return this.base64Decode(this.$route.params.path)
        }
    }
}
</script>
