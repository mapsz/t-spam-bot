<template>  
  <div>
    <template v-for='(errorz) in errors'>
      <div v-for='(error,j) in errorz' :key='j' style="color:tomato; margin-bottom:5px;">
        ❗{{error}}
      </div>
    </template>
  </div>
</template>

<script>
export default {
  props: ['errors'],


    //Catch errors
    // if(!r){if(ax.lastResponse.status == 422){this.errors = ax.lastResponse.data.errors;return;}}

}
</script>

<style>

</style>