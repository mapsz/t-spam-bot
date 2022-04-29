<template>
  <juge-layout>
    <div class="container-fluid">
      <h1>Спам</h1>

      <juge-file-upload />

      <juge-add :model="'forward'" />
      
      <juge-list :data="'forward'" :edit="true" :delete="true" :pages="true" />
    </div>
  </juge-layout>  
</template>

<script>
import {mapGetters, mapActions} from 'vuex';
export default {

}
</script>

<style>

</style>