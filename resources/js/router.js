let routes = [];

//Accounts
routes.push({
  path: '/accounts',
  alias: ['/', '/home'],
  component: require('./components/telegram-accounts/telegram-accounts.vue').default
});

//Spam
routes.push({path: "/spams", component: require('./components/spams/spams.vue').default});

//404
routes.push({path: "*", component: require('./components/_juge/juge-404.vue').default});

export default {
  'mode':'history',
  'routes':routes
}