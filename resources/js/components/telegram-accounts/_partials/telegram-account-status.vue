<template>
<div>

  <!-- Status -->
  <div v-if="data.status != undefined" class="d-flex" style="justify-content: space-between;">
    <div>
      <span v-if="data.status == 1" >Логинен 🐢</span>
      <span v-if="data.status == 0" >НЕлогинен ❌😱</span>
      <span v-if="data.status == -1" >бан ⛱️</span>
      <span v-if="data.status == 2" >Логинем 🐤</span>
      <span v-if="data.status == -2" >Стоп 🚏 {{ moment.unix(parseInt(1650554802)).locale("ru").format('LTS') }}</span>
    </div>

    <div>
      <button v-if="data.status == 0 && showLogin" class="btn btn-sm btn-success" @click="login()">залогинить</button>
    </div>
  </div>

  <!-- Code -->
  <div v-if="data.status == 2">

    <div v-if="isCodeSend && !isBadCode">
      <span class="text-info">Проверяем код</span>      
    </div>

    <div v-else-if="(isBadCode || isCode) && showCodeForm" class="pt-2">
      <div class="input-group input-group-sm mb-3">
        <div class="input-group-prepend">
          <span class="input-group-text" id="inputGroup-sizing-sm">Code</span>
        </div>
        <input v-model="code" type="text" class="form-control" aria-label="Small" aria-describedby="inputGroup-sizing-sm">
        <div class="input-group-append">
          <button class="btn btn-outline-secondary" type="button" @click="sendCode()">Отправить</button>
        </div>        
      </div>

      <div v-if="isBadCode" class="text-danger"> неверный код </div>

      <juge-errors :errors="errors" class="juge-form-error" />
    </div>

  </div>

</div>
</template>

<script>
import {mapGetters, mapActions} from 'vuex';
export default {
props: ['data'],
data(){return{
  moment:moment,
  code:"",
  errors:false,
  activateHandle:false,
  showLogin:true,
  showCodeForm:true,
}},
computed:{
  isLoging(){
    if(this.data.status == 2){
      return true
    }
    return false
  },
  isCode(){
    if(this.data.GetCode == undefined){
      return false
    }
    return true
  },
  isBadCode(){
    if(this.data.BadCode == undefined){
      return false
    }
    return true
  },
  isCodeSend(){
    if(this.data.CodeSend == undefined){
      return false
    }
    return true
  },
},
async mounted() {
  this.handle()
},
methods:{
  ...mapActions({
    'refresh':'tAcc/refreshRow',
  }),
  async login(){
    let r = await ax.fetch('/p/to/login', {'phone':this.data.phone}, 'post');
    this.activateHandle = true;
    this.showLogin = false;
  },
  async sendCode(){
    let r = await ax.fetch('/p/send/code',{account:this.data.phone, code:this.code},'post');
    if(!r){if(ax.lastResponse.status == 422){this.errors = ax.lastResponse.data.errors;return;}}
    if(r){this.showCodeForm = false}
  },
  handle() {
    setTimeout(() => {
      if (this.isLoging || this.activateHandle) {
        this.refresh(this.data.id);
        this.$emit('refresh');

        if(this.isBadCode) this.showCodeForm = true
      }
      this.handle()
    }, 5000); 
  }
},
}
</script>

<style>

</style>