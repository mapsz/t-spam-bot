<template>
  <paginate
    v-if="pages != undefined && pages.last_page > 1"
    v-model="page"
    :page-count="pages.last_page"
    :container-class="'pagination m-0'"
    :page-class="'page-item'"
    :prev-class="'page-item'"
    :next-class="'page-item'"
    :page-link-class="'page-link'"
    :prev-link-class="'page-link'"
    :next-link-class="'page-link'"
    :prevText="'&laquo;'"
    :nextText="'&raquo;'"
    :click-handler="change"
    @change="change"
  >
  </paginate>
</template>

<script>
export default {
  props: ['pages'],
  data(){return{
    page:false,
  }},
  watch: {
    pages: {
      handler: function (newVal, oldVal) {
        this.setPage();
      },
      deep: true
    }
  },
  mounted(){
    this.setPage();
  },
  methods:{
    setPage(){
      let val = false;
      if(this.pages != undefined && this.pages.current_page != undefined){
        val = this.pages.current_page;
      }

      if(!val && this.pages) val = true;

      this.page = val;

      if(this.page) this.setQueryString(val);

    },
    setQueryString(val){
      let query = Object.assign({}, this.$route.query);
      if(query.page != undefined && query.page == val) return;
      if(query.page != undefined && val === true) return;
      query.page = val;

      this.$router.push({ query });
      return;
    },
    change(page){
      this.setQueryString(page);
      this.$emit('change',page);
    }
  },
}
</script>

<style>

</style>