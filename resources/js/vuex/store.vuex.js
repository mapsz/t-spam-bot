import jugeVuex from './juge-vuex.vuex.js'

// import cart from './modules/site/cart'



let store = {  
  modules:{
    user: require('./modules/user.vuex').default,
    spam: new jugeVuex('spam', true, true),
    tAcc: new jugeVuex('tAcc', true, true),

  }
};

export default store;