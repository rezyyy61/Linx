import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import './assets/main.css'
import router, { installGuards } from './router'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
installGuards(pinia, router)
app.use(router)

app.mount('#app')
