<template>
<juge-layout>
  <h1>Аккаунты</h1>
  
  <!-- Add -->
  <div>
    <!-- Show button -->
    <button v-if="!loginShow" @click="loginShow=true" class="btn btn-success">Залогинить</button>
    <telegram-account-login v-if="loginShow" @close="loginShow=false"/>
  </div>
  
  <!-- List -->
  <juge-list :data="'tAcc'" :edit="false" :delete="false" :pages="true" />

</juge-layout>
</template>

<script>
export default {
  data(){return{
    loginShow:0,
  }},  
}
</script>

<style scoped>
/*  */
</style>