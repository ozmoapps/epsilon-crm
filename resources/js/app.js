import './bootstrap';

import Alpine from 'alpinejs';
import toast from './toast';

window.Alpine = Alpine;

toast(Alpine); // Init toast store synchronously

Alpine.start();
