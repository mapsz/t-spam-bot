<template>
  <juge-layout>
    <div class="container-fluid">
      <h1>Спам</h1>

      <juge-add :model="'spam'" />
      
      <juge-list :data="'spam'" :edit="true" :delete="true" :pages="true" />
    </div>
  </juge-layout>  
</template>

<script>
import {mapGetters, mapActions} from 'vuex';
import jugeFileUpload from '../_juge/juge-file-upload.vue';
export default {
  components: { jugeFileUpload },

}
</script>

<style>

</style>