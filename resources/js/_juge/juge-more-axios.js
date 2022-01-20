class jugeMoreAxios{

    constructor() {
      this.lastResponse = {};
      this.lastQuery = {};
    }
  
    async get(url,params = {},loader = true){this.fetch(url,params,'get',loader)};
    async post(url,params = {},loader = true){this.fetch(url,params,'get',loader)};
    async put(url,params = {},loader = true){this.fetch(url,params,'get',loader)};
    async delete(url,params = {},loader = true){this.fetch(url,params,'get',loader)};
  
    async fetch(url,params = {},method = 'get',loader = true,anyResult = false){
  
      this.lastQuery = {url,params,method};
      //Start loading
      let l; if(loader){l = load.start();}
      //Axios
      let r = false;
      switch (method) {
        case 'get' || 'GET':
          r = await this.getFetch(url, params);
          break;
        case 'put' || 'PUT':
          r = await axios.put(url, params)
            .then((r) => {return {e:0,r:r.data};})
            .catch((error) => {this.catch(error);return {e:1,r:error.response};});
          break;
        case 'post' || 'POST':
          r = await axios.post(url, params)
            .then((r) => {return {e:0,r:r.data};})
            .catch((error) => {this.catch(error);return {e:1,r:error.response};});
          break;
        case 'delete' || 'DELETE':
          r = await axios.delete(url, {data:params})
            .then((r) => {return {e:0,r:r.data};})
            .catch((error) => {this.catch(error);return {e:1,r:error.response};});
          break;
        default:
          return false;
      }
  
        //Stop loading
        if(loader) load.stop(l);
        
        //Get bad string error
        if(anyResult == false && typeof(r.r) == 'string'){
          if(
            r.r != 1 &&
            url != '/file/upload'
          ){
            console.log(url);
            this.error(r.r);
            return false;
          }
        }      
      
        //Save response
        this.lastResponse = r.r;
        //Return data
        return r.e ? false : r.r;
  
    }
  
    async getFetch(url,params){
      
      //Get query string
      let queryString = this.getQueryString();
  
      //Add pages
      if(params.page == undefined){
        if(queryString.page != undefined){
          this.jugeAxPages = queryString.page;
        }
        if(this.jugeAxPages){
          params.page = this.jugeAxPages;
          params.limit = 100;
        }
      }
  
      let r;
      r = await axios.get(url, {params})
        .then((r) => {return {e:0,r:r.data};})
        .catch((error) => {this.catch(error);return {e:1,r:error.response};});
  
      return r;
    }
  
    catch(error){
      if(error.response.status == 422) return false;    
  
      // console.log(error);
      // return false;   
      // if(error.response.status == 422) return false;    
          
      this.error(error.response);
  
      // if(error.response.config.url != "/error"){
      //   this.saveError(error.response.data);
      // }
      
      terror();
      // console.log(error.response);    
    }
  
    error(response){
      console.log('_______ERROR________');
      console.log(response);
      console.log('````````````````````');    
  
      terror();
    }
  
    getQueryString(){
      return decodeURI(window.location.search)
        .replace('?', '')
        .split('&')
        .map(param => param.split('='))
        .reduce((values, [ key, value ]) => {
          values[ key ] = value
          return values
        }, {})    
    }
  
  }
  
  export default jugeMoreAxios;