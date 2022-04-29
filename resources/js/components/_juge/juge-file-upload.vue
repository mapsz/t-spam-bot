<template>
  <div>
    <!-- Image preview -->
    <div class="pb-2" style="display:flex">        
      <span 
        v-for='(v,i) in previewFiles' :key='i'
        style="border: black 1px solid;"
        class="mx-1"
      >
        <img :src="v" style="max-height:150px">
        <!-- Buttons -->
        <div class="image-buttons" style="display:flex;justify-content:center;border-top: 1px solid black;">
          <span v-if="i > 0" class="image-button-delete">⬅️</span>
          <span v-b-modal.image-delete-modal @click="imageToDelete = v" class="image-button-delete px-2">❌</span>
          <span v-if="previewFiles.length != i+1" class="image-button-delete">➡️</span>
        </div>            
      </span>        
    </div>
    <!-- Delete Dialog -->
    <b-modal v-model="imageToDeleteShow" id="image-delete-modal" title="Удалить фото?">
      <div style="display: flex;justify-content: center;">
        <img :src="imageToDelete" class="pl-2" style="max-height:150px">      
      </div>        
      <template v-slot:modal-footer>
        <div class="w-100" style="display: flex;justify-content: flex-end;">
          <b-button class="mx-2" variant="secondary" @click="imageToDeleteShow=false">Отмена</b-button>
          <b-button variant="danger" @click="fileToDelete()">Удалить</b-button>
        </div>
      </template>          
    </b-modal>
    <!-- File Input -->
    <file-pond
      name="file"
      ref="pond"
      label-idle="Drop files here..."
      :allow-multiple="vMultiple"
      :data-max-files="vMaxFileCount"
      :accepted-file-types="vFileType"
      :server="server"
      :files="myFiles"
      allowReorder="true"
      @init="handleFilePondInit"
      @warning="fpWarning"
      @processfile="fpFileUploaded"
      @removefile="fpFileUploaded"
      @reorderfiles="fpFileUploaded"
    />
 
  </div>
</template>
 
<script>
// Import Vue FilePond
import vueFilePond from 'vue-filepond'; 

// Import styles
import "filepond/dist/filepond.min.css";
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'; 

// Import image preview and file type validation plugins
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview'; 

// Create component
const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginImagePreview);

export default {
  props:['max-file-size','max-file-count','file-type','value'],
  model: {
    event: 'blur'
  },
  data: function() {
    return { 
      server: {
        url: '/juge/file/upload',
        process: {
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
          }       
        }
      },
      myFiles: [],
      vFileType:['image/*'],
      vMaxFileCount:1,
      vMultiple:false,
      //Files
      imageToDeleteShow:false,
      imageToDelete:false,
      //
      previewFiles:[],
    };
  },
  mounted(){
    
    //Set file type
    if(this.fileType == undefined){
      this.vFileType = ['image/*'];
    }else{
      this.vFileType = this.fileType;
    }

    if(this.maxFileCount == undefined){
      this.vMaxFileCount = 1;
      this.vMultiple = false;
    }else{

      if(this.maxFileCount < 2){
        this.vMaxFileCount = 1;
        this.vMultiple = false;
      }else{
        this.vMaxFileCount = this.maxFileCount;
        this.vMultiple = true;
      }

    }

    //Set preview files
    this.previewFiles = this.value;
    
  },
  methods: {
    handleFilePondInit: function() {
      console.log('FilePond has initialized');
      // FilePond instance methods are available on `this.$refs.pond`
    },
    fpWarning(payload){
      console.log(payload); // @@@
    },
    fpFileUploaded(){
      let getFiles = this.$refs.pond.getFiles();
      let encFiles = [];
      $.each( getFiles, ( key, value ) => {
        encFiles.push(value.serverId);
      });
      this.$emit('blur', encFiles);
    },
    async fileToDelete(){
      //Delete
      let r = await ax.fetch('/juge/file/delete',{'file':this.imageToDelete}, 'delete');
      
      if(!r) return;
        
      //Close modal
      this.imageToDeleteShow = false;

      //Refresh preview files
      let i = this.previewFiles.findIndex(x => x == this.imageToDelete);
      this.previewFiles.splice(i,1);
      

    }
  },
  components: {
    FilePond
  }
};
</script>

<style>

</style>