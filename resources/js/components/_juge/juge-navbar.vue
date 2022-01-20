<template>  
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="/">👺</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
    <!-- Links -->
    <ul  class="navbar-nav">
      <li v-for='(link,i) in links' :key='i' :class="current == link.link ? 'active' : ''" class="nav-item">
        <a class="nav-link" :href="link.link">{{link.caption}}</a>
      </li>
    </ul>
    <!-- Auth -->
    <div v-if="user" class="w-100 d-flex" style="justify-content:flex-end">
      <!-- Profile -->
      <div class="align-self-center mx-2">
        <span>{{user.name}}</span>
      </div>
      <!-- Logout -->
      <div>
        <button @click="logout()" class="btn btn-sm btn-outline-danger" type="button">Выход</button>
      </div>
    </div> 
  </div>
</nav>
</template>

<script>
import {mapGetters, mapActions} from 'vuex';
export default {
  data(){return{
    current:this.$route.path,
    adminPrefix:'/admin',
    links:[],
  }},
  computed:{
    ...mapGetters({user:'user/getAuth'}),
  },
  mounted(){
    this.links = [
      {
        link: "/accounts",
        caption:"Аккаунты",
      },
      {
        link: "/spams",
        caption:"Спам",
      },
    ];
  },
  methods:{    
    ...mapActions({logout:'user/logout'}),
  },
}
</script>

<style scoped>
.navbar-nav{  
  font-size: 9pt;
}
</style>