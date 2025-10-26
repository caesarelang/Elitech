import '../css/app.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router/ppic'
import axios from 'axios'

axios.defaults.baseURL = '/api'
const token = localStorage.getItem('token')
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

const app = createApp(App)
app.use(router)
app.mount('#app')