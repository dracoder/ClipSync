import Loading from "./Loading.vue";
import ValidationMessage from "./ValidationMessage.vue";
import LanguageSwitcher from "./LanguageSwitcher.vue";
import ColorPicker from './ColorPicker.vue';
import SliderCard from "./SliderCard.vue";
import NormalCard from "./NormalCard.vue";

const globalComponents = {
    init: async function (app) {
        app.component("Loading", Loading);
        app.component("ColorPicker", ColorPicker);
        app.component("ValidationMessage", ValidationMessage);
        app.component("LanguageSwitcher", LanguageSwitcher);
        app.component("SliderCard", SliderCard);
        app.component("NormalCard", NormalCard);
    },
};

export default globalComponents;