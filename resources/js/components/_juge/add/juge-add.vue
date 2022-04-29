<template>
<div v-if="inputs !== false" >

  <!-- Show button -->
  <button v-if="!addShow" @click="addShow=true" class="btn btn-success">Добавить</button>

  <!-- Add container -->
  <div v-if="addShow">
    <div class="add">
      <div>
        <h3 class="d-inline-block">Добавить</h3>
        <button class="btn btn-danger"  @click="addShow=false" style="float: right;">X</button>
      </div>
      <juge-form :inputs="inputs" :errors="errors" :refresh="refresh" @submit="put" />
    </div>
  </div>
  
      
</div>
</template>

<script>
export default {  
props: ['model'],
data(){return{
  addShow:0,
  refresh:0,
}},
computed:{
  inputs(){return this.$store.getters[this.model+'/getInputs'];},
  errors(){return this.$store.getters[this.model+'/getErrors'];},
},
async mounted() {
  //Get inputs
  this.$store.dispatch(this.model + '/fetchInputs');
},
methods:{
  async put(data){
    let put = await this.$store.dispatch(this.model + '/doPut',data);
    if(!put) return false;

    //Close add
    this.refresh++;
    this.addShow = false;
  }
},
}
</script>

<style scoped>
  .add {
    background-color: #e4f9e4;
    padding: 20px;
    border: 1px solid green;
    border-radius: 7px;
    margin: 10px 0px;
  }
</style>