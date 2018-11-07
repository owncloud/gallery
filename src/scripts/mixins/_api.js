const api = {
	methods : {
		apiEndpoint(path = '') {
			return OC.generateUrl('apps/gallery/' + path);
		}
	}
};

export default api;