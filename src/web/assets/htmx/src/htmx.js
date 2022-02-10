htmx.defineExtension('craft-cp', {
    onEvent: function(name, evt) {
        switch (name) {
            case 'htmx:configRequest':
                this.configureRequest(evt);
                break;
            case 'htmx:load':
                this.onLoad(evt);
                break;
        }
    },

    configureRequest: function(evt) {
        // Add the standard Craft headers
        Object.assign(evt.detail.headers, Craft._actionHeaders());
    },

    onLoad: function(evt) {
        if (evt.detail.elt === document.body) {
            return;
        }

        const headHtml = evt.detail.elt.querySelectorAll("template.hx-head-html");
        const bodyHtml = evt.detail.elt.querySelectorAll("template.hx-body-html");

        for (let i = 0; i < headHtml.length; i++) {
            const headHtml = headHtml[i].innerHTML;
            if (headHtml) {
                Craft.appendHeadHtml(headHtml);
            }
        }

        for (let i = 0; i < bodyHtml.length; i++) {
            const bodyHtml = bodyHtml[i].innerHTML;
            if (bodyHtml) {
                Craft.appendBodyHtml(bodyHtml);
            }
        }

        Craft.initUiElements(evt.detail.elt);
    }
});
