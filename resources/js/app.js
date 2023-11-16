/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';

import { OhVueIcon, addIcons } from "oh-vue-icons";
import { IoCloseOutline, CoTrash, IoWarning, GiGolfTee, HiPlusSm } from "oh-vue-icons/icons"
addIcons(IoCloseOutline, CoTrash, IoWarning, GiGolfTee, HiPlusSm);

import GolfersList from './components/golfers/GolfersList.vue';

const app = createApp({});

app.component('golfers-list', GolfersList);
app.component("v-icon", OhVueIcon);

app.directive('phone-format', {
    beforeUpdate(el, binding) {
        if (binding.value && (binding.value !== binding.oldValue)) {
            var x = binding.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/)
            el.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '')
        }
    }
})




app.mount('#app');
