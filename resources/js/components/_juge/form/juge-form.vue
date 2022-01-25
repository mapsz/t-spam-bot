<template>
<form v-if="cid != undefined && cid > 0" class="juge-form" @submit.prevent="submit()">
   
  <template v-for="(input,i) in inputs">

    <!-- Label -->
    <span 
      class="juge-form-label-required" 
      :key="i"
      :style="input.type == 'textEditor' ? 'grid-column: 1 / 2;' : ''"
    >      
      <!-- Required -->
      <span v-if="input.required != undefined && input.required" style="color:red">*</span>
      <!-- Label -->
      <label 
        :for="input.multi == undefined || !input.multi ? input.name + '-input-' + cid : ''"          
      >
        {{input.caption != undefined ? input.caption : input.name}}:
      </label>
    </span>

    <!-- Single Input -->
    <template v-if="input.multi == undefined || input.multi == false">
      <juge-input 
        v-model="data[input.name]" 
        :cid="cid"
        :input="input" 
        :key="i+'a'"
        :style="input.type == 'textEditor' ? 'grid-column: 1 / 4;' : ''"
      ></juge-input>
      <!-- info -->
      <span 
        v-if="input.multi == undefined || !input.multi" :key="i+'b'"
        :style="input.type == 'textEditor' ? 'grid-column: 1 / 4;' : ''"
      >
        <small v-if="input.info != undefined" :id="input.name + '-info'" class="">
          {{input.info}}
        </small>
      </span>  
    </template>

    <!-- Multi Input -->
    <span v-if="input.multi != undefined && input.multi" class="juge-form-multi" :key="i+'c'">
      <div v-for="(mInput,j) in input.inputs" :key="j">
        <div class="juge-form-multi-input">
          <!-- Label Required -->
          <div class="juge-form-label-required" :key="j">      
            <!-- Required -->
            <span v-if="mInput.required != undefined && mInput.required" style="color:red">*</span>
            <!-- Label -->
            <label 
              :for="mInput.multi == undefined || !mInput.multi ? mInput.name + '-input-' + cid : ''"          
            >
              {{mInput.caption != "" ? mInput.caption+':' : ''}}
            </label>
          </div>       
          <!-- Input -->
          <juge-input v-model="data[mInput.name]" :input="mInput" :cid="cid"></juge-input>  
        </div>
      </div>
    </span>
  </template>


  <!-- Errors -->  
  <juge-errors :errors="errors" class="juge-form-error" />

  <button class="juge-form-save btn btn-success" type="submit">{{buttonCaption}}</button>

</form>  
</template>

<script>
export default {
  props: ['inputs','errors','button','refresh'],
  data(){return{
    data:{},
  }},
  computed:{
    buttonCaption: function(){
      if(this.button == undefined) return "Сохранить";
      else return this.button;
    },
    cid(){return this._uid}
  },
  watch:{
    refresh: function (val, oldVal) {
      if(this.refresh > 0){
        this.data = {};
      } 
    },
  },
  mounted(){
    //
  },
  methods:{
    submit(){
      this.$emit('submit',this.data);
    }
  },
}
</script>

<style scoped>
  .juge-form {
    display:grid;
    grid-template-columns:auto 200px 1fr;
    grid-gap: 10px;
  }
  .juge-form-label-required label{
    margin:0px;
  }
  .juge-form-label-required{
    justify-self: end;
    align-self: center;
    font-weight: 600;
  }
  .juge-form-input{
    border: 1px solid #ced4da;
    padding: 0.25rem 0.5rem;
    font-size: 0.7875rem;
    line-height: 1.5;
    border-radius: 0.2rem;  
    width:200px;
  }
  .juge-form-multi{
    grid-column: span 2;
    display: flex;
  }
  .juge-form-multi-input{
    margin:0 20px 0 0;
  }
  .juge-form-multi-input .juge-form-label-required{
    font-weight: 100;
  }
  .juge-form-save{
    grid-column-start:2;
  }
  .juge-form-error{
    grid-column-start: 2;
    grid-column-end: 4;
    color: red;
  }
</style>