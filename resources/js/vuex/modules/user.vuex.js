import jugeVuex from '../juge-vuex.vuex.js'

let user = new jugeVuex('user');

{//State
  user.state.auth = false;
}
{//Getters
  user.getters.getAuth = (state) => {return state.auth}
//   user.getters.isAdmin = (state) => {
//     if(state.auth == undefined || state.auth.roles == undefined) return null;
//     if(state.auth.roles[0] == undefined) return false;

//     let isRoleAdmin = false;

//     $.each(state.auth.roles, (k, role) => {
//       if(role.name == 'admin'){
//         isRoleAdmin = true;
//         return true;
//       } 
//     });

//     return isRoleAdmin;
//   }
}

{//Actions
  user.actions.fetchAuth = async ({commit}) => {
    let r = await ax.fetch('/auth/user');
    commit('mAuth',r); 
    return;
  };
  user.actions.logout = async ({commit}) => {
    let r = await ax.fetch('/logout', {}, 'post');
    if(r === 1) location.reload();
    return;
  };
}

{//Mutations
  user.mutations.mAuth = async (state,d) => {return state.auth = d;};
}



export default user;