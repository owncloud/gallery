import { Base64 } from 'js-base64';

const base64 = {
	methods : {
		base64Encode (string) {
			return Base64.encode(string);
		},
		base64Decode (string) {
			return Base64.decode(string);
		}
	}
};

export default base64;