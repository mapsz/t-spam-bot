
require('./bootstrap');

window.Vue = require('vue').default;

// Bootstrap vue
import { BootstrapVue } from 'bootstrap-vue'
import 'bootstrap-vue/dist/bootstrap-vue.css'
Vue.use(BootstrapVue)

//Paginate
import Paginate from 'vuejs-paginate'
Vue.component('paginate', Paginate)

//Router
import VueRouter from 'vue-router';
import routes from './router.js';
Vue.use(VueRouter);

//Vuex
import Vuex from 'vuex'
import store from './vuex/store.vuex.js';
Vue.use(Vuex)

//jQuery
import $ from "jquery";
window.$ = $;

//Juge more Ax
import ax from './_juge/juge-more-axios.js';
window.ax = new ax;
window.terror= function(){console.log('error xzz')};

//Juge load
import load from './_juge/juge-loader.js';
window.load = new load('#8ac2a73b','/img/nurik-loader.jpgz')

//Draggable
import draggable from 'vuedraggable';
Vue.component("draggable", draggable);

const files = require.context('./', true, /\.vue$/i)
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))


const app = new Vue({
    el: '#app',    
    router: new VueRouter(routes),
    store: new Vuex.Store(store),  
});
