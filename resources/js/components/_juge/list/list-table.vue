<template>
<div>
       
  <!-- Total / Paginator / Settings -->
  <div class="d-flex mb-2" style="align-items: center;">
    <div v-if="total > 0 && getData.length > 0">
      <span>Всего: {{total}}</span>
    </div>  
    <!-- Paginator -->
    <div class="mr-2" style="margin-left: auto;flex: 555;display: flex;justify-content: flex-end;">
      <paginator :model="dataModelName"/>
    </div>
    <!-- List settings button -->
    <div style="margin-left: auto; flex:1;">
      <!-- <font-awesome-icon class="list-seetings-button" icon="cog" size="2x" data-toggle="modal" :data-target="'#list-settings-modal-'+model"/> -->
      <span></span>
    </div>
  </div>

  <!-- List table   -->
  <table class="table table-sm" style="border-bottom: 1px solid #343a40;">
    <!-- List Header -->
    <thead class="thead-dark">
      <tr>
        <th 
          @click="doSort(k.name)"
          v-for="k in activeKeys" :key="k.name"
        >
          <span>{{k.caption}} </span>
          <!-- <span>⬆️</span> -->
        </th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(d,i) in getData" :key="i">
        <td v-for="k in activeKeys" :key="k.name">
          <template v-if="dataExists(d,k) || k.type == 'custom'"> 
            <!-- Simple value -->
            <span v-if="k.type == undefined">
              {{getValue(d,k)}}
            </span>
            <!-- Custom -->
            <span v-else-if="k.type == 'custom'">
              <component :is="k.component" :data="d" @success="success()"></component>
            </span>              
            <!-- Link -->
            <span v-else-if="k.type == 'link'">
              <a :href="getLink(d,k)">
                {{getValue(d,k)}}
              </a>
            </span>
            <!-- Count -->
            <span v-else-if="k.type == 'count'">
              {{getCount(d,k)}}
            </span>
            <!-- List -->
            <span v-else-if="k.type == 'list'">
              <juge-list-list :data="d[k.name]"></juge-list-list>
            </span>
            <!-- Int To Str -->
            <span v-else-if="k.type == 'intToStr'">
              {{getIntToStr(d,k)}}
            </span>  
          </template>        
        </td>
      </tr>
    </tbody>
  </table>  

  <!-- Total / Paginator-->
  <div class="d-flex mb-2" style="align-items: center;">
    <div v-if="total > 0 && getData.length > 0">
      <span>Всего: {{total}}</span>      
    </div>
    <!-- Paginator -->
    <div class="mr-2" style="margin-left: auto;flex: 555;display: flex;justify-content: flex-end;">
      <paginator :model="dataModelName"/>
    </div>
  </div>

  <!-- List settings modal -->
  <list-settings :model="model" :p-keys="keys"></list-settings>

</div>
</template>

<script>
// import {mapGetters} from 'vuex';
export default {
  props: ['data','model','p-data-model-name'],
  data(){return{
    keys:[],
  }},
  computed:{    
    activeKeys: function(){
      let out = [];
      $.each( this.keys, ( k, v ) => {
        //Add if active
        if(v.active){
          out.push(v);
        }
      });
      return out;
    },
    modelMulti: function(){
      return this.model[0].toUpperCase() + this.model.substr(1) + 's';
    },
    dataModelName(){
      if(this.pDataModelName != undefined) {
        return this.pDataModelName[0].toUpperCase() + this.pDataModelName.substr(1);
      }else{
         return this.modelMulti;
      }
    },
    getData () {
      if(this.data != undefined) return this.data;     

      if(this.$store.getters[this.model+'/get'] != undefined) return this.$store.getters[this.model+'/get']

      if(this.$store.getters['get'+this.dataModelName] != undefined) return this.$store.getters['get'+this.dataModelName];
            
      return false;
    },
    total(){
      return this.$store.getters['get'+this.dataModelName+'Pages'] != undefined ? (
        this.$store.getters['get'+this.dataModelName+'Pages'].total != undefined ? (
          this.$store.getters['get'+this.dataModelName+'Pages'].total
        ) : (
          this.getData.length
        )
      ) : (
        this.getData.length
      )    
    },
  },
  mounted(){
    this.getKeys();
  },
  methods:{
    async getKeys(){
      let r = await this.jugeAx('/config/',{model:this.model});
      if(r) this.keys = r;
    },    
    dataExists(data, k){
      let pos = k.name.indexOf(".");
      let k2 = false;
      let k1 = false;
      if(pos > 0){
        k1 = k.name.substring(0, pos);
        k2 = k.name.substring(pos+1);
      }else{
        k1 = k.name;
      }
      
      if(data[k1] == undefined) return false;
      if(k2 && data[k1][k2] == undefined) return false;

      return true;

    },
    getLink(data, k){
      let linkKey = k.link.substring(k.link.indexOf('{')+1, k.link.indexOf('}'));
      let link = k.link.replace(
        k.link.substring(k.link.indexOf('{'), k.link.indexOf('}')+1),
        data[linkKey]
      );
      return link;
    },
    getValue(data, k){
      //Set value
      let value;
      let pos = k.name.indexOf(".");
      if(pos > 0){
        let f = k.name.substring(0, pos);
        let s = k.name.substring(pos+1);
        value = data[f][s];
      }else{
        value = data[k.name];
      }
      return value;
    },
    getCount(data, k){
      return data[k.name].length;
    },
    getIntToStr(data, tab){
      return tab.intToStr[data[tab.name]];
    },
    success(){
      this.$emit('success');
    },
  },
}
</script>

<style scoped>
  .list-seetings-button{
    color: #ff9800;
    cursor: pointer;
  }
  .list-seetings-button:hover{
    color: #a6ff36;
  }
</style>