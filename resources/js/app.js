<<<<<<< HEAD
require('./bootstrap');
=======
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

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
>>>>>>> ba80c40ccdd1335d4d221ef9d46ee7e305b2210f
