import Alpine from 'alpinejs';

// Page-level UI state only. Persistent state (cart, wishlist, auth) lives in Laravel.
document.addEventListener('alpine:init', () => {
    Alpine.store('ui', {
        mobileMenu: false,
        miniCart: false,
        toggle(key) { this[key] = !this[key]; },
    });
});
