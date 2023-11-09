/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';

import { OhVueIcon, addIcons } from "oh-vue-icons";
import { IoCloseOutline, CoTrash, IoWarning, GiGolfTee } from "oh-vue-icons/icons"
addIcons(IoCloseOutline, CoTrash, IoWarning, GiGolfTee);

import GolfersList from './components/golfers/GolfersList.vue';

const app = createApp({});

app.component('golfers-list', GolfersList);
app.component("v-icon", OhVueIcon);




app.mount('#app');
