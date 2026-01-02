import Alpine from "alpinejs";
import exportModal from "./components/exportModal.js";
import axios from "axios";
import flatpickr from "flatpickr";
import { Portuguese } from "flatpickr/dist/l10n/pt.js";
import "flatpickr/dist/flatpickr.min.css";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index.js";
import "flatpickr/dist/plugins/monthSelect/style.css";

flatpickr.localize(Portuguese);
window.flatpickr = flatpickr;

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

window.Alpine = Alpine;
Alpine.data("exportModal", exportModal);

Alpine.directive("monthpicker", (el, { expression }, { evaluate }) => {
    const config = evaluate(expression) || {};

    if (el._flatpickr) el._flatpickr.destroy();

    flatpickr(el, {
        locale: Portuguese,
        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "m/Y",
            }),
        ],
        // (opcional, mas recomendado)
        // altInput: true, // se você quiser que o usuário veja o altFormat em um input separado
        allowInput: true,
        defaultDate: el.value || undefined,
        onChange: () => {
            el.dispatchEvent(new Event("input", { bubbles: true }));
            el.dispatchEvent(new Event("change", { bubbles: true }));
        },
        ...config,
    });
});

Alpine.start();
