import Alpine from "alpinejs";
import exportModal from "./components/exportModal.js";
import axios from "axios";
import flatpickr from "flatpickr";
import { Portuguese } from "flatpickr/dist/l10n/pt.js";
import "flatpickr/dist/flatpickr.min.css";

flatpickr.localize(Portuguese);
window.flatpickr = flatpickr;
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
window.Alpine = Alpine;
Alpine.data("exportModal", exportModal);
Alpine.start();
