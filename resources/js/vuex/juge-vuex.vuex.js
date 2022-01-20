class jugeVuex {
  constructor(modelName, $edit = false, $delete = false) {
    //Params
    this.modelName = modelName;
    this.$edit = $edit;
    this.$delete = $delete;

    //Vuex
    this.namespaced = true;
    //SGMA
    this.state = {
      //Sigle
      id:false,
      row:[],
      //Multi
      rows:[],
      pages:false,
      infinite:false,
      waterfall:false,
      waterfallId:1,
      waterfalling:false,
      filters:{},
      //Keys      
      keys:null,
      keysModel:false,
      //Inputs
      inputs:null,
      postInputs:null,
      //Other
      didFetch:false,
      didInputsFetch:false,
      didPostInputsFetch:false,
      firstListFetch:false,
      errors:false,
    }
    this.getters = {
      //Single
      getOne: (state) => {return state.row;},
      //Multi
      get: (state) => {return state.rows;},      
      getKeys: (state) => {return state.keys;},
      getPages: (state) => {return state.pages;},
      getInfinite: (state) => {return state.infinite;},
      getFilters: (state) => {return state.filters;},
      isFirstListFetch: (state) => {return state.firstListFetch;},
      isFetched: (state) => {return state.didFetch;},
      isInputsFetched: (state) => {return state.didInputsFetch;},
      isPostInputsFetched: (state) => {return state.didPostInputsFetch;},
      isWaterfalling: (state) => {return state.waterfalling;},
      getWaterfall: (state) => {return state.waterfall;},
      getWaterfallId: (state) => {return state.waterfallId;},
      //Other      
      getErrors: (state) => {return state.errors;},
      getInputs: (state) => {
        if(state.inputs == null) return null;
        state.inputs.forEach(function (v, k) {
          if(undefined != state.row[v.name]){      
            state.inputs[k].value = state.row[v.name];
          }   
        });
        
        return state.inputs;
      
      },
      getPostInputs: (state) => {
        if(state.postInputs == null) return null;
        state.postInputs.forEach(function (v, k) {
          if(undefined != state.row[v.name]){      
            state.postInputs[k].value = state.row[v.name];
          }   
        });
        
        return state.postInputs;
      
      },
      getParams:(state) => {
        let params = JSON.parse(JSON.stringify(state.filters));
        params.model = modelName;
        return params;
      },
    }
    this.actions = {
      //SINGLE
      async fetchOne({state,commit},id = false){
        if(id){
          commit('mId',id);
        }else{
          id = state.id;
        }
        
        let params = JSON.parse(JSON.stringify(state.filters));
        params.model = modelName;    
        params.id = id;    
        
        let r = await ax.fetch('/juge',params);  
        commit('mRow',r);
      },      
      async fetchInputs({commit,state}){
        //Inputs
        let model = state.keysModel;
        if(!model) model = modelName;
        await commit('mDidInputsFetch',true);   
        let inputs = await ax.fetch('/juge/inputs',{'model':model});
        //Commit
        commit('mInputs',inputs);  
      },
      async fetchPostInputs({commit,state}){
        //Inputs
        let model = state.keysModel;
        if(!model) model = modelName;
        await commit('mDidPostInputsFetch',true);   
        let inputs = await ax.fetch('/juge/post/inputs',{'model':model});
        //Commit
        commit('mPostInputs',inputs);  
      },
      //MULTI
      //Fetch
      async listFetch({commit,dispatch}){
        await commit('mFirstListFetch',true);     
        dispatch('fetchKeys');
        dispatch('fetchData');
      },
      async fetchKeys({commit,state}){
        //KEYS
        let model = state.keysModel;
        if(!model) model = modelName;
        let keys = await ax.fetch('/juge/keys',{'model':model});
        //Commit
        commit('mKeys',keys);  
      },
      async fetchData({commit,state,dispatch}){
        
        let loaded = true;
        //Set params
        let params = JSON.parse(JSON.stringify(state.filters));
        params.model = modelName;    
        
        //Infinite
        if(state.infinite){
          params.page = state.infinite;
        }

        //Waterfall
        if(state.waterfall){
          commit('mWaterfallId',state.waterfallId + 1)
          dispatch('WaterfallFetch',{'waterfallId':state.waterfallId,'page':1,params});
          return;
        }

        //DATA 
        let r = await ax.fetch('/juge',params,'get',loaded);  
        
        await commit('mDidFetch',true);   
        
        // By ID
        let data = [];
        let rPages = false;
        if(state.filters.id != undefined && state.filters.id){
          data = [r];
        }else{
          // Get pages          
          if(r.current_page != undefined){
            rPages = JSON.parse(JSON.stringify(r));
            rPages.data = null;
          }
          //Get data
          data = r;
          if(r.current_page != undefined){
            data = r.data;
          }
        }

        if(rPages){
          await commit('mPages',rPages);
        }   

        //Commit
        if(state.infinite || state.waterfall){
          //Infinite
          if(state.infinite) await commit('mRowsInfinite',data);
          
          //Waterfall
          if(state.waterfall){

            if(rPages.current_page != state.waterfall){
              return;
            }
            
            if (rPages.current_page > rPages.last_page){
              await commit('mWaterfall',1);
              state.waterfalling = false;
              return;
            }

            if(state.waterfall == 1){
              await commit('mRows',data);
              await commit('mWaterfall',rPages.current_page+1);
              await dispatch('fetchData');              
              return;
            }

            if (state.waterfall > 1 && rPages.current_page < rPages.last_page){
              await commit('mWaterfall',rPages.current_page+1);
              await commit('mRowsInfinite',data);
              await dispatch('fetchData');      
              return;
            }

            if (rPages.current_page == rPages.last_page){
              await commit('mWaterfall',1);
              state.waterfalling = false;
              return;
            }
          }
        }

        await commit('mRows',data);
        await commit('mDidFetch',true);
      
        
        return;
      },
      async setInfinite({commit},infinite){
        await commit('mInfinite',infinite);
        return;
      },
      async addInfinite({commit,state,dispatch}){
        await commit('mInfinite',state.infinite+1);
        await dispatch('fetchData');
        return;
      },
      async setWaterfall({commit}){
        await commit('mWaterfall',1);
        return;
      },
      async WaterfallFetch({commit,getters,dispatch}, d){

        await commit('mWaterfalling',true);
        //Stop current waterfall is other active
        if(getters.getWaterfallId != d.waterfallId) return;

        //Set params
        let params = d.params;
        params.paginate = 9;
        params.page = d.page;

        // console.log(params);
        
        //Fetch
        if(getters.getWaterfallId != d.waterfallId) return;
        let r = await ax.fetch('/juge',params,'get',false);  
        if(getters.getWaterfallId != d.waterfallId) return;

        // Get pages          
        let rPages = JSON.parse(JSON.stringify(r));
        rPages.data = null;
        await commit('mPages',rPages);
        //Get data
        let data = r.data;
        //Mutate data
        if(rPages.current_page == 1){
          // console.log('mu');
          await commit('mRows',data);
        }else{
          // console.log('muInf');
          await commit('mRowsInfinite',data);
        }

        //Stop if last page
        if(d.page >= rPages.last_page){
          await commit('mWaterfalling',false);          
          await commit('mDidFetch',true);   
          return;
        }

        //Stop if another waterfall
        if(getters.getWaterfallId != d.waterfallId) return;

        //Continue
        dispatch('WaterfallFetch', {'waterfallId':d.waterfallId,'page':d.page+1,'params':d.params});

        return;

        // params.paginate = 9;
        // params.page = state.waterfall;
        // loaded = false;
        // state.waterfalling = true;

      },
      //Filters
      async addFilter({commit,state,dispatch},filter){
        //Get filter name        
        let key = Object.keys(filter)[0];
        //Exit if no change
        if(state.filters[key] == filter[key]) return;
        //Set filter
        state.filters[key] = filter[key];
        //Set first page
        if(state.pages) await commit('mPages',{current_page:1});

        //Trigger
        let tempFilt = state.filters;
        state.filters = false;
        state.filters = tempFilt;

        //Refresh infinite
        if(state.infinite){
          await commit('mInfinite',1);
          await commit('mPages',false);
          await commit('mRows',[]);
        }

        //Refresh Watefall
        if(state.waterfall){ 
          await commit('mWaterfall',1);
          await commit('mRows',[]);
          await commit('mPages',false);
        }

        //Fetch
        if(state.firstListFetch){
          dispatch('fetchData');
        }
        
      },  
      async clearFilters({commit}){
        commit('mFilters',{});
      },
      //Keys
      async setKeysModel({commit},model){
        commit('mKeysModel',model);  
      },
      //Edits
      async doEdit({state,commit},data){
        if(!$edit) return 'not allowed';

        //Refresh errors
        commit('mErrors',[]);

        //Post
        let row = await ax.fetch('/juge',{'model':modelName,data},'post');

        //Fail
        if(!row){
          //Catch error
          if(ax.lastResponse.status != undefined){if(ax.lastResponse.status == 422){commit('mErrors',ax.lastResponse.data.errors);}}
          return false;
        }

        //Edit local
        //Single
        if(state.row.id != undefined && state.row.id == row.id){
          console.log('single');
        }
        //Multi
        if(state.rows.length > 0){
          console.log('multi exists');
          let i = state.rows.findIndex(x => x.id == row.id);
          if(i > -1){
            let edits = state.rows[i].edits;
            row.edits = edits;
            let rows = state.rows;
            rows[i] = row;
            commit('mRows',rows);
          }
        }
        

        return row;
      },
      async doDelete(id){
        if(!$delete) return 'not allowed';        
        
        //Refresh errors
        commit('mErrors',[]);

        //Delete
        let row = await ax.fetch('/juge',{'model':modelName,id},'delete');

        //Fail
        if(!row){
          //Catch error
          if(ax.lastResponse.status != undefined){if(ax.lastResponse.status == 422){commit('mErrors',ax.lastResponse.data.errors);}}
          return false;
        }

        //Edit local
        //Single
        // if(state.row.id != undefined && state.row.id == row.id){
        //   console.log('single');
        // }
        //Multi
        // if(state.rows.length > 0){
        //   console.log('multi exists');
        //   let i = state.rows.findIndex(x => x.id == row.id);
        //   if(i > -1){
        //     let edits = state.rows[i].edits;
        //     row.edits = edits;
        //     let rows = state.rows;
        //     rows[i] = row;
        //     commit('mRows',rows);
        //   }
        // }        

        return row;

      }
    }   
    this.mutations = {
      mId: (state,d) => {return state.id = d;},
      mRow: (state,d) => {return state.row = d;},
      mFirstListFetch: (state,d) => {return state.firstListFetch = d;},
      mFilters: (state,d) => {return state.filters = d;},
      mKeys: (state,d) => {return state.keys = d;},
      mInputs: (state,d) => {return state.inputs = d;},
      mPostInputs: (state,d) => {return state.postInputs = d;},
      mKeysModel: (state,d) => {return state.keysModel = d;},
      mRows: (state,d) => {return state.rows = d;},
      mRowsInfinite: (state,d) => {return state.rows = state.rows.concat(d);},
      mPages: (state,d) => {return state.pages = d;},
      mInfinite: (state,d) => {return state.infinite = d;},
      mWaterfall: (state,d) => {return state.waterfall = d;},
      mWaterfallId: (state,d) => {return state.waterfallId = d;},
      mWaterfalling: (state,d) => {return state.waterfalling = d;},
      mErrors: (state,d) => {return state.errors = d;},
      mDidFetch: (state,d) => {return state.didFetch = d;},
      mDidInputsFetch: (state,d) => {return state.didInputsFetch = d;},
      mDidPostInputsFetch: (state,d) => {return state.didPostInputsFetch = d;},
    }    
  }


}

export default jugeVuex;