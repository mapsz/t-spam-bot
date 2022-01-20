class jugeLoader{

    constructor(color = '#fff6003b;', image = false) {
      this.color = color;
      this.image = image;
      this.activeLoaders = [];
    }
  
    start(container = 'body'){
      let id = this.getFreeLoaderId();
      //Make dom
      let dom = this.appendDom(id,container);
      // Keep sizing
      let sizing = this.keepSizing(dom,container,id);
      // Keep sizing
      let one = this.keepOne(id);
      // Push loader 
      this.activeLoaders.push({id,dom,sizing,one});
      
      return id;
    }
  
    stop(id){
      //Get loader
      let i = this.activeLoaders.findIndex(x => x.id == id);
      if(i == -1) return;
      let loader = this.activeLoaders[i];
  
      //stop sizing
      this.stopKeep(loader.sizing);
  
      //stop one
      this.stopKeep(loader.one);
  
      //Remove dom
      loader.dom.remove();
  
      //remove var
      this.activeLoaders.splice(i,1);
    }
  
    getFreeLoaderId(){
      let i = 0;
      do {i++;} while ($('.juge-loader[data-id='+i+']').length > 0);
      return i;
    }
  
    appendDom(id,container){
      let height = $(container).outerHeight();
      let width = $(container).outerWidth();
      let margin ={
        top:    $(container).css('margin-top'),
        rigth:  $(container).css('margin-rigth'),
        bottom: $(container).css('margin-bottom'),
        left:   $(container).css('margin-left'),
      } 
      let border ={
        top:    $(container).css('border-top-width'),
        rigth:  $(container).css('border-rigth-width'),
        bottom: $(container).css('border-bottom-width'),
        left:   $(container).css('border-left-width'),
      } 
      let padding ={
        top:    $(container).css('padding-top'),
        rigth:  $(container).css('padding-rigth'),
        bottom: $(container).css('padding-bottom'),
        left:   $(container).css('padding-left'),
      } 
  
      //Append loader  
      let imageDom = !this.image ? '' : ''+
        '<div '+
          'style="'+
            'animation: spin 1.5s;'+
            'animation-iteration-count: infinite;'+
            'position: absolute;'+
            'top: 50%;'+
            'left: 50%;'+
            'margin-top: -100px;'+
            'margin-left: -100px;'+
          '"'+
        '>'+
          '<img src="'+this.image+'" style="width: 200px;border-radius: 40px;">'+
        '</div>'
      ;
  
  
      $(container).append(''+
        '<div '+ 
          'class="juge-loader d-none" '+
          'data-id="'+id+'"'+
          'style="'+
            'cursor: wait;'+
            'position:fixed;'+
            'top:'+(margin.top)+';'+
            'left:'+(margin.left)+';'+
            'width:'+(width)+'px;'+
            'height:'+(height)+'px;'+
            'background-color:'+this.color+';'+
            'z-index: 99999;'+
          '"'+
        '>'+
          imageDom +       
          '<style>'+      
            '@keyframes spin { '+
              'from {transform:rotate(0deg);}'+
              'to {transform:rotate(360deg);}'+
            '}'+
          '</style>'+
        '</div>'
      );
  
      return $('.juge-loader[data-id="'+id+'"]')
    }
  
    keepSizing(dom,container,id){
      return setInterval(()=>{
        // console.log(id);
        dom.height($(container).outerHeight()); 
        dom.width($(container).outerWidth()); 
      }, 500);
    }
  
    keepOne(id){
      return setInterval(()=>{
        if($('.juge-loader.d-block').length == 0){
          $('.juge-loader[data-id='+id+']').removeClass('d-none').addClass('d-block');
        };
      }, 100);
  
    }
  
    stopKeep(intervalId){
      clearInterval(intervalId);    
    }
  
  }
  
  export default jugeLoader;