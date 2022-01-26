<template>
<div>
  <div>
    <div class="add">
      <div>
        <h4 class="d-inline-block">Залогярница</h4>
        <button class="btn btn-danger"  @click="$emit('close')" style="float: right;">X</button>
      </div>

      <!-- Phone Form -->
      <div :class="sendCodeShow ? 'disabled' : ''">

        <!-- Form -->
        <div class="login-phone-form">
          <!-- Number input -->
          <juge-form :inputs="[{'name':'phone', 'caption':'Номер'}]" :errors="errors" :button="'Залогинить'" @submit="sendPhone" />
        </div>

        <!-- Already login -->
        <div v-if="alreadyLoginShow" class="mt-3">
          <p><b>Номер уже залогинен</b></p>        
          <iframe src="https://giphy.com/embed/5zsa1yJd15mWMIA0wB" width="300" height="300" frameBorder="0" class="giphy-embed" allowFullScreen style="max-width:100%"></iframe>
        </div>

        <!-- Flood -->
        <div v-if="floodShow" class="mt-3">
          <p><b>Аккаунт временно забанен</b></p>        
          <iframe src="https://giphy.com/embed/5zsa1yJd15mWMIA0wB" width="300" height="300" frameBorder="0" class="giphy-embed" allowFullScreen style="max-width:100%"></iframe>
        </div>

        <!-- Exists -->
        <div v-if="accounExistsShow" class="mt-3">
          <p><b>Этот номер кем-то занят</b></p>        
        </div>

      </div>

      <!-- Code Form -->
      <div v-if="sendCodeShow" class="mt-3">
        <!-- Form -->
        <div class="login-phone-form">
          <!-- Number input -->
          <juge-form :inputs="[{'name':'code', 'caption':'Код'}]" :errors="errors" :button="'Подтвердить'" @submit="sendCode" />

          
          <!-- Bad code -->
          <div v-if="badCodeShow" class="mt-3">
            <p><b>Не верный код</b></p>        
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</template>

<script>
export default {
data(){return{
  errors:[],
  floodShow:false,
  accounExistsShow:false,
  alreadyLoginShow:false,
  sendCodeShow:false,
  badCodeShow:false,
  phone:false,
}},
methods:{
  async sendPhone(data){
    this.errors = [];
    this.alreadyLoginShow = false;
    this.sendCodeShow = false;
    this.floodShow = false;
    this.accounExistsShow = false;
    this.phone = data.phone;

    let r = await ax.fetch('/account/login', {'phone':data.phone}, 'post');

    if(!r){if(ax.lastResponse.status == 422){this.errors = ax.lastResponse.data.errors;return;}}

    if(r == 5){
      this.alreadyLoginShow = true;
      return false;
    }

    if(r == 4){
      this.sendCodeShow = true;
      return false;
    }
    if(r == 3){
      this.floodShow = true;
      return false;
    }
    if(r == 9){
      this.accounExistsShow = true;
      return false;
    }
    
    console.log(r);
  },

  async sendCode(data){

    this.badCodeShow = false;

    let r = await ax.fetch('/send/code', {'phone':this.phone, 'code':data.code}, 'post');

    
    if(r == 8){
      this.badCodeShow = true;
      return false;
    }
    
    if(r == 1){
      Vue.toasted.show("Успех! 🐸",{duration:5000,type:'success',position:'bottom-right'});
      location.reload();
    }

    console.log(r);

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