import Alpine from "alpinejs";
import exportModal from "./components/exportModal.js";

window.Alpine = Alpine;
Alpine.data("exportModal", exportModal);
Alpine.start();
