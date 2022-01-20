<template>
<div>
  <form @submit.prevent="doSearch()">
    <div class="input-group input-group-sm"  style="width: auto;">
      <input v-model="search" type="text" class="form-control" aria-label="Small" aria-describedby="inputGroup-sizing-sm" style="width: 125px;">
      <div class="input-group-append">        
        <button class="btn btn-primary" type="submit">
          🔎
        </button>      
      </div>    
    </div>
  </form>
</div>
</template>

<script>
export default {
  props: ['model'],
  data() {
    return {
      search:"",
    }
  },
  methods:{
    doSearch(){
      //Set vuex
      if(this.model != undefined){
        this.$store.dispatch(this.model+'/addFilter',{search:this.search});
      }
      //Emit
      this.$emit('doSearch',this.search);
    }
  }
}
</script>

<style>

</style>