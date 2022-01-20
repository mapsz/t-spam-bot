
require('./bootstrap');

window.Vue = require('vue').default;


//Router
import VueRouter from 'vue-router';
import routes from './router.js';
Vue.use(VueRouter);

const files = require.context('./', true, /\.vue$/i)
files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))


const app = new Vue({
    el: '#app',    
    router: new VueRouter(routes),
});
