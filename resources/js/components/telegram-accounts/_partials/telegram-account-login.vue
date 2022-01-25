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
        <div v-if="alreadyLogin" class="mt-3">
          <p><b>Номер уже залогинен</b></p>        
          <iframe src="https://giphy.com/embed/5zsa1yJd15mWMIA0wB" width="300" height="300" frameBorder="0" class="giphy-embed" allowFullScreen style="max-width:100%"></iframe>
        </div>

      </div>

      <!-- Code Form -->
      <div v-if="sendCodeShow" class="mt-3">
        <!-- Form -->
        <div class="login-phone-form">
          <!-- Number input -->
          <juge-form :inputs="[{'name':'code', 'caption':'Код'}]" :errors="errors" :button="'Подтвердить'" @submit="sendCode" />
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
  alreadyLogin:false,
  sendCodeShow:false,
  phone:false,
}},
methods:{
  async sendPhone(data){
    this.errors = [];
    this.alreadyLogin = false;
    this.sendCodeShow = false;
    this.phone = data.phone;

    let r = await ax.fetch('/account/login', {'phone':data.phone}, 'post');

    if(!r){if(ax.lastResponse.status == 422){this.errors = ax.lastResponse.data.errors;return;}}

    if(r == 5){
      this.alreadyLogin = true;
      return false;
    }

    if(r == 4){
      this.sendCodeShow = true;
      return false;
    }
    
    console.log(r);
  },

  async sendCode(data){

    let r = await ax.fetch('/send/code', {'phone':this.phone, 'code':data.code}, 'post');

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