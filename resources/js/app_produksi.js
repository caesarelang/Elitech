import '../css/app.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router/produksi' 
import axios from 'axios'

axios.defaults.baseURL = '/api'

const token = localStorage.getItem('produksi_token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

const app = createApp(App)
app.use(router)
app.mount('#app')
