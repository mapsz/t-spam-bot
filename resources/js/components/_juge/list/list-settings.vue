<template>
<div>
  <!-- Button -->
  <button @click="$bvModal.show('list-settings-modal-'+model)" class="btn btn-info btn-sm">
    <div class="list-seetings-button">
      🔧
    </div>
  </button>

  <!-- <b-modal :id="'list-settings-modal-'+model" title="Настройки таблицы" ok-only> -->
  <b-modal :id="'list-settings-modal-'+model" title="Настройки таблицы" ok-only>

    <!-- <b-form-checkbox  class="m-3" switch size="lg" style="cursor: pointer;">Мобильная версия</b-form-checkbox> -->

    <draggable 
      :list="keys"
      @end="save()"
      handle=".handle"
    >
      <div v-for="(k, i) in keys" :key="i" class="">
        <div class="d-flex m-2 p-2 border" style="align-items: center;">
          <span class="px-3 handle" style="cursor: move;">📌</span>
          <div class="list-settings-row" style="cursor: pointer;">
            <b-form-checkbox v-model="keys[i].active" @change="save()" switch size="lg" style="padding:0px">
              {{k.label != undefined ? k.label : k.key}}
            </b-form-checkbox>
          </div>
        </div>
      </div>
    </draggable>

  </b-modal>
  <!-- </b-modal> -->
</div>
</template>

<script>
export default {
  props: ['p-keys','model'],
  data(){return{
    keys:[],
    positions:[],
  }},
  computed:{
    cKeys: function(){
      let keys = [];
      let key = null;
      $.each(this.keys, ( k, v ) => {
        key = v;
        key.position = k;
        keys.push(key);
      });
      return keys;
    }
  },
  watch: {
    pKeys: {
      handler: function (val, oldVal) {
        this.keys = val;
      },
      deep: true
    }
  },
  methods:{
    async save(){
      let r = await ax.fetch('/juge/crud/settings',{model:this.model,keys:this.cKeys},'post');
      if(!r) return;      
    }
  },
}
</script>

<style>
  .list-settings-row input, .list-settings-row label{
    cursor:pointer;
  }

  .list-settings-row input{
    margin-right:5px;
  }

  .list-seetings-button{
    color: #ff9800;
    cursor: pointer;
    font-size: 14pt;

    transition: all 0.3s ease-in-out 0s;
  }
  .list-seetings-button:hover{
    transform: rotate(180deg);
    transition: all 0.3s ease-in-out 0s;
  }
</style>