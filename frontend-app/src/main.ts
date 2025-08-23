import "@fontsource/inter/400.css";
import "@fontsource/inter/600.css";
import "@fontsource/noto-sans-arabic/400.css";
import "@fontsource/noto-sans-arabic/600.css";

import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import i18n from "./i18n";
import { initTheme } from "./theme";
import "./assets/main.css";
import "@/assets/theme.css";
import router, { installGuards } from "./router";

initTheme();

const app = createApp(App);
const pinia = createPinia();

app.use(i18n);
app.use(pinia);
installGuards(pinia, router);
app.use(router);

app.mount("#app");
