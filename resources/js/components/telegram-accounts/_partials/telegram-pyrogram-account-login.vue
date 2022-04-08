<template>
  <div class="add">
    <div>
      <h4 class="d-inline-block">Добавить Аккаунт</h4>
      <button class="btn btn-danger"  @click="$emit('close')" style="float: right;">X</button>
    </div>    

    <!-- Phone Form -->
    <div>

      <!-- Form -->
      <div class="login-phone-form">
        <!-- Number input -->
        <juge-form :inputs="[{'name':'phone', 'caption':'Номер'}]" :errors="errors" :button="'Залогинить'" @submit="createAccount" />

        <!-- Exists -->
        <div v-if="accounExistsShow" class="mt-3">
          <p><b>Этот номер кем-то занят</b></p>        
        </div>        
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapActions} from 'vuex';
export default {
data(){return{
  errors:[],
  accounExistsShow:false,
}},
methods:{
  ...mapActions({
    'fetch':'tAcc/fetchData',
  }),
  async createAccount(data){
    // Fetch
    let r = await ax.fetch('/t-acc/create', {'phone':data.phone}, 'post');
    // Errors
    if(!r){if(ax.lastResponse.status == 422){this.errors = ax.lastResponse.data.errors;return;}}
    // Account exists
    if(r == 9){this.accounExistsShow = true;return false;}
    // Success
    if(r == 1){

      let pr = await ax.fetch('/p/to/login', {'phone':data.phone}, 'post');

      this.fetch();
      this.$emit('close');
      Vue.toasted.show("Успех! 🐸",{duration:5000,type:'success',position:'bottom-right'});   
    } 
    
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

  .disabled {
    pointer-events: none;
    opacity: 0.4;
  }

</style>