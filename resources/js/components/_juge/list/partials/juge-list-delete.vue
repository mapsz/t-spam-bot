<template>
<div v-if="model && row.id != undefined">

  <!-- ID -->
  <div class="d-flex justify-content-center">
    ID: <b>{{row.id}}</b> 
  </div>

  <!-- Button -->
  <div class="d-flex justify-content-center mt-3">
    <button @click="doDelete" class="btn btn-danger">Удалить</button>
  </div>  
  
</div>
</template>

<script>
export default {
props: ['model','row'],
methods:{
  async doDelete(){
    
    //Vuex
    let r = await this.$store.dispatch(this.model+'/doDelete',this.row.id);
        
    if(r == 'not allowed'){      
      //Toast
      Vue.toasted.show("nope 💂",{type:'info',position:'bottom-right'});
      return;
    }

    if(!r){
      Vue.toasted.show("Delete error 😱",{duration:5000,type:'error',position:'bottom-right'});
      return
    }

    //Success
    Vue.toasted.show("Delete Success 🦀",{duration:5000,type:'success',position:'bottom-right'});
    this.$emit('deleteSuccess');
    return;

  }
},
}
</script>

<style>

</style>