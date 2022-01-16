let routes = [];


//Telegram Accounts
import telegramAccounts from './components/telegram-accounts/telegram-accounts.vue';
routes.push(
  {path: '/accounts', component:telegramAccounts},
)

export default {
  'mode':'history',
  'routes':routes
}