<template>
<div>

<!-- Modal -->
<div class="modal fade" :id="cID" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" :id="cID+'Label'"><b>{{title}}</b></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">                
        <template functional>

          <slot/>

        </template>
      </div>
      <div v-if="0" class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>

</div>
</template>

<script>
export default {  
  props: ['title','active', 'id'],
  data(){return{
    //
  }},
  computed:{
    cID(){
      if(this.id == undefined) return 'default-popup';
      return this.id;
    },
  },
  async mounted() {
    if(this.active) $('#'+this.cID).modal('show');
    $('#'+this.cID).on('hidden.bs.modal', () => {
      this.$emit('close');
    })
  },
  watch:{
    active: function (val, oldVal) {
      if(val){
        $('#'+this.cID).modal('show');
      }else{
        $('#'+this.cID).modal('hide');
      }
      
    },
  },
}
</script>

<style>

</style>