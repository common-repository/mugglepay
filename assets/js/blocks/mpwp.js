(() => {
	const { wcSettings, wcBlocksRegistry } = window.wc;

	const data = wcSettings.getSetting("mpwp_data");

	console.log('data', data)

    const mpwpTitle = wp.htmlEntities.decodeEntities(data.title || "");
	const decodeDescription = () => wp.htmlEntities.decodeEntities(data.description || "");

	const mpwpPaymentMethod = {
		name: "mpwp",
		ariaLabel: mpwpTitle,
		label: window.React.createElement(
			() => {
				return window.React.createElement(() => mpwpTitle);
			},
			null
		),
		content: window.React.createElement(decodeDescription, null),
		edit: window.React.createElement(decodeDescription, null),
		canMakePayment: () => {
			console.log("canMakePaymentcanMakePayment");
			console.log("canMakePayment");
			return true;
		},
		supports: {
			showSavedCards: false,
			showSaveOption: false,
			features: data.supports,
		},
	};

	wcBlocksRegistry.registerPaymentMethod(mpwpPaymentMethod);
})();