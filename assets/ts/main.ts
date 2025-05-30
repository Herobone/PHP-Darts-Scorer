import "@css/styles.css";
import {Navbar} from "@/modules/navbar.ts";
import "flowbite";
import {CreateGamePage} from "@/pages/create_game.ts";

function initializeApp(): void {
    console.log("Dart Scorer Frontend geladen!");
    Navbar();
    CreateGamePage();
}

// Stelle sicher, dass das DOM vollständig geladen ist, bevor du DOM-Operationen ausführst
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

