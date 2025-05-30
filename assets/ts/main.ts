import "@css/styles.css";
import {Navbar} from "@/modules/navbar.ts";
import "flowbite";

function initializeApp(): void {
    console.log("Dart Scorer Frontend geladen!");
    Navbar();
}

// Stelle sicher, dass das DOM vollständig geladen ist, bevor du DOM-Operationen ausführst
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

