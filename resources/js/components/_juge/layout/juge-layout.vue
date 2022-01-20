<template>
  <div class="container-fluid">
    <juge-layout-header />
      <template functional>
        <slot/>
      </template>
    <juge-layout-footer />
  </div>
</template>

<script>
import {mapActions} from 'vuex';
export default {

  async mounted() {
    this.fetchAuth();
  },
  methods:{    
    ...mapActions({fetchAuth:'user/fetchAuth'}),
  },
}
</script>

<style>
  /* Mobile */
  @media screen and (max-width: 992px){
    /*  */
  }

  /* Desktop */
  @media screen and (min-width: 992px){
    /*  */
  }
</style>