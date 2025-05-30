import {Collapse, type CollapseInterface, Dropdown, type DropdownInterface, type DropdownOptions} from "flowbite";

export function Navbar() {

    // set the target element that will be collapsed or expanded (eg. navbar menu)
    const $targetEl: HTMLElement | null = document.getElementById('navbar-user');

    // optionally set a trigger element (eg. a button, hamburger icon)
    const $triggerEl: HTMLElement | null = document.getElementById('navbar-hamburger-toggle');

    /*
     * $targetEl: required
     * $triggerEl: optional
     * options: optional
     * instanceOptions: optional
     */
    const collapse: CollapseInterface = new Collapse(
        $targetEl,
        $triggerEl
    );


    // set the dropdown menu element
    const $dropdownTarget: HTMLElement | null = document.getElementById('user-dropdown');

    // set the element that trigger the dropdown menu on click
    const $dropdownTrigger: HTMLElement | null = document.getElementById('user-menu-button');

    // options with default values
    const dropdownOptions: DropdownOptions = {
        placement: 'bottom',
        triggerType: 'click',
        offsetSkidding: 0,
        offsetDistance: 10
    };

    /*
     * targetEl: required
     * triggerEl: required
     * options: optional
     * instanceOptions: optional
     */
    const dropdown: DropdownInterface = new Dropdown(
        $dropdownTarget,
        $dropdownTrigger,
        dropdownOptions
    );
}