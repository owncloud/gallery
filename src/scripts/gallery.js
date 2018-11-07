// Components

import FolderView from './components/folder-view.vue';
import SliderView from './components/slider-view.vue';

// Libs

import Vue       from 'vue/dist/vue.js';
import VueRouter from 'vue-router';

Vue.use(VueRouter);

// --- Global Components

// Vue.component('loading-spinner', require('./loading-spinner.vue'));
import { mixin as t_mixin, directive } from './mixins/_translate.js';
import api from './mixins/_api.js';
import base64 from './mixins/_base64.js';

Vue.mixin(t_mixin);
Vue.mixin(api);
Vue.mixin(base64);
Vue.directive('translate', directive);

const router = new VueRouter({
	routes : [
		{
			path: '/',
			component: {
				template : '<div>No folder selected</div>'
			},
		},
		{
			path: '/:path',
			component: FolderView,
		},
		{
			path: '/view/:path/',
			component: SliderView,
			name : 'Slider View'
		},
		{
			path: '/view/:path/:image',
			component: SliderView,
			name : 'Slider View me'
		}
	]
});

// --------------------------------------------------------------- app setup ---

const gallery = new Vue({
	router,
	template : '<router-view></router-view>',
	data : {
		name : 'Gallery'
	}
});

// Japp … we need to wait for a ready DOM
$(document).ready(() => {
	gallery.$mount('#gallery');
});