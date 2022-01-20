<template>
<div>
  <form @submit.prevent="doId()">
    <div class="input-group input-group-sm"  style="width: auto;">
      <input v-model="id" type="text" class="form-control" aria-label="Small" aria-describedby="inputGroup-sizing-sm" style="width:75px">
      <div class="input-group-append">        
        <button class="btn btn-primary" type="submit">
          <b>ID</b>
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
      id:"",
    }
  },
  methods:{
    doId(){
      //Set vuex
      if(this.model != undefined){
        this.$store.dispatch(this.model+'/clearFilters');
        this.$store.dispatch(this.model+'/addFilter',{id:this.id});
      }
      //Emit
      this.$emit('doId',this.id);
    }
  }
}
</script>

<style>

</style>