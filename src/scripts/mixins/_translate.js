const mixin = {
	methods : {
		t(string, scope = 'gallery') {
			return t(scope, string);
		}
	}
};

const directive = {
	bind (el, binding) {
		el.innerText = t(binding.value, el.innerText.trim());
	}
};

export {
	mixin,
	directive
};