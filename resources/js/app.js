// import './bootstrap';

import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import App from "./App.vue";
import i18n from "./plugins/localization/i18n";
import Swal from "sweetalert2";
import globalComponents from "./components/global";

window.Swal = Swal;

const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    iconColor: "white",
    customClass: {
        popup: "colored-toast",
    },
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
});
window.Toast = Toast;

const pinia = createPinia();
const app = createApp(App);

globalComponents.init(app);
app.use(pinia);
app.use(router);
app.use(i18n);

app.mount("#app");
// import { createApp } from 'vue';

// import ExampleComponent from './components/ExampleComponent.vue';
// import Room from './components/Room.vue';
// import VideoCall from './components/VideoCall.vue';  // Updated component import
// import VideoParticipant from './components/VideoParticipant.vue';
// import 'vue3-toastify/dist/index.css';


// const app = createApp({});
// app.component('example-component', ExampleComponent);
// app.component('room', Room);
// app.component('video-call', VideoCall);  // Updated component registration
// app.component('video-participant', VideoParticipant);
// app.mount('#app');
