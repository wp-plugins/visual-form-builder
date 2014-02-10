/*
 * Translated default messages for the jQuery validation plugin.
 * Locale: HU (Hungarian; Magyar)
 */
(function($) {
	$.extend($.validator.messages, {
		required: "Kötelező megadni.",
		remote: "Kérem javítsa ki ezt a mezőt.",
		email: "Érvényes e-mail címnek kell lennie.",
		url: "Érvényes URL-nek kell lennie.",
		date: "Dátumnak kell lennie.",
		dateISO: "Kérem írjon be egy érvényes dátumot (ISO).",
		number: "Számnak kell lennie.",
		digits: "Csak számjegyek lehetnek.",
		creditcard: "Érvényes hitelkártyaszámnak kell lennie.",
		equalTo: "Meg kell egyeznie a két értéknek.",
		accept: "Adjon meg egy értéket, megfelelő végződéssel.",
		maxlength: $.validator.format("Legfeljebb {0} karakter hosszú legyen."),
		minlength: $.validator.format("Legalább {0} karakter hosszú legyen."),
		rangelength: $.validator.format("Legalább {0} és legfeljebb {1} karakter hosszú legyen."),
		range: $.validator.format("{0} és {1} közé kell esnie."),
		max: $.validator.format("Nem lehet nagyobb, mint {0}."),
		min: $.validator.format("Nem lehet kisebb, mint {0}."),
		maxWords: $.validator.format("Kérjük, adja meg {0} szó, vagy kevesebb."),
		minWords: $.validator.format("Kérjük, adja meg legalább {0} szó."),
		rangeWords: $.validator.format("Kérjük, adja meg {0} {1} szavakat."),
		alphanumeric: "Betűk, számok és aláhúzás csak kérjük",
		lettersonly: "Letters csak kérlek",
		nowhitespace: "No white space kérlek"
	});
}(jQuery));
